<?php

require_once('ObjectType.php');
require_once('htdocs/core.php');

/**
 * Base object for all ARGTech top-level-objects.
 *
 * All top-level objects extend from this class. This makes sure that we have some core
 * functionality present on all of the objects. So far these include the ability to be
 * constructed via a type/id pair, becoming a front-page featured object, having
 * comments able to be put on the object, being able to favorite an object, etc.
 */

class CoreObject
{
	/** @var integer */
	protected $_id;	
	
	/** @var array */
	protected $_subscribers;
	
	protected $_extenders;
	
	static protected $_staticSubscribers = array();
	
	static protected $_byId = array();
	
	public static function getById($id)
	{
		if (!$id)
			die('Trying to load from id ' . $id . ' which is obviously bad.');
			
		if (isset(self::$_byId['a' . $id]))
			return self::$_byId['a' . $id];
		
		self::$_byId['a' . $id] = new CoreObject($id);
		return self::$_byId['a' . $id];
	}
	
	public static function destroyCache()
	{
		self::$_byId = array();
	}
	
	public function getId()
	{
		return $this->_id;
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
		$this->_subscribers = array();
		$this->_extenders = array();
		
		self::staticDispatch('CoreObject.create', $this);
	}
	
	public function addExtender($ob)
	{
		$this->_extenders[] = $ob;
	}
	
	public function __call($name, $arguments)
	{
		$found = false;
		$val = null;
		foreach ($this->_extenders as $extender) {
			if (method_exists($extender, $name)) {
				$found = true;
				$val = call_user_func(array($extender, $name), $this, $val, $arguments);
			}
		}
		
		if (!$found)
			throw new Exception('Method ' . $name . ' does not exist.');
		
		return $val;
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
	
	public function __destruct()
	{
		$this->dispatch('destroyed');
	}
}