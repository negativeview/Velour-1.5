<?php

require_once('ObjectType.php');
require_once('DB.php');
require_once('htdocs/database.php');
require_once('htdocs/core.php');

/**
 * Base object for all ARGTech top-level-objects.
 *
 * All top-level objects extend from this class. This makes sure that we have some core
 * functionality present on all of the objects. So far these include the ability to be
 * constructed via a type/id pair, becoming a front-page featured object, having
 * comments able to be put on the object, being able to favorite an object, etc.
 */

class BaseObject
{
	/** @var integer */
	protected $_id;	
	
	/** @var array */
	protected $_fetched;
	
	/** @var boolean */
	protected $_has_updated;
	
	/** @var array */
	protected $_subscribers;
	
	/** @var array */
	protected $_functions;
	
	static protected $_staticSubscribers = array();
	
	static protected $_byId = array();
	
	public static function getById($id)
	{
		if (!$id)
			die('Trying to load from id ' . $id . ' which is obviously bad.');
			
		if (isset(self::$_byId['a' . $id]))
			return self::$_byId['a' . $id];
		
		self::$_byId['a' . $id] = new BaseObject($id);
		return self::$_byId['a' . $id];
	}
	
	public static function destroyCache()
	{
		self::$_byId = array();
	}
	
	public function toString()
	{
		return $this->_id;
	}
	
	/**
	 * Constructor
	 *
	 * @param integer $id The id of this row in the obj_static table.
	 * @return BaseObject
	 */
	protected function __construct($id)
	{
		$this->_id = $id;
		$this->_fetched = array();
		$this->_subscribers = array();
		$this->_has_updated = false;
		
		self::staticDispatch('create', $this);
	}
	
	public function addFunction($funcName, $cb)
	{
		$this->_functions[$funcName] = $cb;
	}
	
	public function __call($name, $arguments)
	{
		// There are many classes that add functions in response to
		// loading our data. So let's load it and let those functions
		// lazy load if we haven't already.
		
		$this->_fetchRaw();
		
		if (isset($this->_functions[$name]))
			return call_user_func($this->_functions[$name], $this, $arguments);
		
		throw new Exception('Method ' . $name . ' does not exist.');
	}
	
	public static function staticSubscribe($event, $callback)
	{
		if (!isset(self::$_staticSubscribers[$event])) {
			self::$_staticSubscribers[$event] = array();
		}
		
		foreach (self::$_staticSubscribers[$event] as $cb) {
			if ($callback === $cb)
				return;
		}
		
		self::$_staticSubscribers[$event][] = $callback;
	}
	
	public static function staticDispatch($event, $data = null)
	{
		if (!isset(self::$_staticSubscribers[$event]))
			return;
		
		foreach (self::$_staticSubscribers[$event] as $cb) {
			call_user_func($cb, $data);
		}
	}
	
	public function getSubscriberList()
	{
		return $this->_subscribers;
	}
	
	public function subscribe($event, $callback)
	{
		if (!isset($this->_subscribers[$event]))
			$this->_subscribers[$event] = array();
		
		foreach ($this->_subscribers[$event] as $cb) {
			if ($callback === $cb)
				return;
		}
		$this->_subscribers[$event][] = $callback;
	}
	
	public function unsubscribe($event, $callback)
	{
		if (!isset($this->_subscribers[$event]))
			$this->_subscribers[$event] = array();
		
		for ($i = 0; $i < count($this->_subscribers[$event]); $i++) {
			$cb = $this->_subscribers[$event][$i];
			if ($callback === $cb) {
				array_splice($this->_subscribers[$event], $i, 1);
				break;
			}
		}
	}
	
	public function dispatch($event, $data = null)
	{
		if (!isset($this->_subscribers[$event]))
			return;
		
		foreach ($this->_subscribers[$event] as $cb) {
			call_user_func($cb, $this, $data);
		}		
	}
	
	protected function _fetchWrapper($key, $function)
	{
		if (isset($this->_fetched[$key]))
			return $this->_fetched[$key];
		
		if (!is_callable($function))
			die('_fetchWrapper called with something not callable: ' . print_r($function, true));

		$this->_fetched[$key] = call_user_func($function);
		$this->_has_updated = false;
		
		$this->dispatch('data:' . $key, $this->_fetched[$key]);
		return $this->_fetched[$key];
	}
	
	protected function _fetchRaw()
	{
		return $this->_fetchWrapper('raw', array($this, '_realFetchRaw'));
	}
	
	protected function _realFetchRaw()
	{
		$db = DB::getInstance();
		
		$db->addTable('obj_static')
		   ->addColumns('obj_static',
		   		array(
		   			'current',
		   			'id',
		   			'type',
		   			'views',
		   		)
		   	);
		   
		$db->addJoin('obj_types', 'obj_static', 'id', 'type')
		   ->addColumns('obj_types',
		   		array(
		   			'id',
		   			'menu_title',
		   			'privacy_setting',
		   			'slug',
		   		)
		   	);
		   
		$db->addJoin('base_object', 'obj_static', 'id', 'current')
		   ->addColumns('base_object',
		   		array(
		   			'buzz',
		   			'buzz_date',
		   			'created',
		   			'creator',
		   			'description',
		   			'id',
		   			'parent',
		   			'project',
		   			'title',
		   		)
		   	);
		   
		$db->addWhereEquals('obj_static', 'id', $this->_id);
		$db->addStringField('base_object', 'title');
		$db->addTextField('base_object', 'description');
		
		return $db->getSingleResult();
	}
	
	public function getRaw()
	{
		return $this->_fetched;
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	public function getTitle()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['title']['value'];
	}
	
	public function getDescription()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['description']['value'];
	}
	
	public function getType()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_static']['type']['value'];
	}
	
	public function getSlug()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['slug']['value'];
	}
	
	public function getMenuTitle()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['menu_title']['value'];
	}
	
	public function getPrivacySetting()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['privacy_setting']['value'];
	}
	
	public function getCreator()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['creator']['value'];
	}
	
	public function getParent()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['base_object']['parent']['value'])
			return BaseObject::getById($this->_fetched['raw']['base_object']['parent']['value']);
		
		return null;
	}
	
	public function setParent($parent)
	{
		$this->_fetchRaw();
		
		if (is_object($parent))
			$parent = $parent->getId();
			
		$this->_fetched['raw']['base_object']['parent']['value'] = $parent;
		$this->_has_updated = true;
	}
	
	public function getProject()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['base_object']['project']['value'])
			return BaseObject::getById($this->_fetched['raw']['base_object']['project']['value']);
		
		return null;
	}
	
	public function getCreated()
	{
		$this->_fetchRaw();
		return new DateTime($this->_fetched['raw']['base_object']['created']['value']);
	}
	
	public function hasBeenUpdated()
	{
		return $this->_has_updated;
	}
}