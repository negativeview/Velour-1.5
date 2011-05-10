<?php

require_once('ObjectType.php');
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
		$res = db_one("SELECT obj_static.id, obj_static.type, obj_types.slug, obj_types.menu_title, " .
			"obj_types.privacy_setting, base_object.creator, base_object.parent, " .
			"base_object.project, base_object.created, " .
			"(SELECT value FROM obj_string WHERE obj_string.id = base_object.title) AS title, " .
			"(SELECT value FROM obj_text t WHERE t.id = base_object.description) AS description " .
			"FROM obj_static " .
			"LEFT JOIN obj_types ON (obj_types.id = obj_static.type) " .
			"LEFT JOIN base_object ON (base_object.id = obj_static.current) " .
			"WHERE obj_static.id = " . $this->_id);
		return $res;
	}
	
	public function getRaw()
	{
		return $this->_fetched;
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	public function getType()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['type'];
	}
	
	public function getSlug()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['slug'];
	}
	
	public function getMenuTitle()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['menu_title'];
	}
	
	public function getPrivacySetting()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['privacy_setting'];
	}
	
	public function getCreator()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['creator'];
	}
	
	public function getParent()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['parent'])
			return BaseObject::getById($this->_fetched['raw']['parent']);
		
		return null;
	}
	
	public function setParent($parent)
	{
		$this->_fetchRaw();
		
		if (is_object($parent))
			$parent = $parent->getId();
			
		$this->_fetched['raw']['parent'] = $parent;
		$this->_has_updated = true;
	}
	
	public function getProject()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['project'])
			return BaseObject::getById($this->_fetched['raw']['project']);
		
		return null;
	}
	
	public function getCreated()
	{
		$this->_fetchRaw();
		return new DateTime($this->_fetched['raw']['created']);
	}
	
	public function hasBeenUpdated()
	{
		return $this->_has_updated;
	}
}