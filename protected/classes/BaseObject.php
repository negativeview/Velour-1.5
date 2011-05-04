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
	
	/**
	 * Constructor
	 *
	 * @param integer $id The id of this row in the obj_static table.
	 * @return BaseObject
	 */
	public function __construct($id)
	{
		$this->_id = $id;
		$this->_fetched = array();
		$this->_has_updated = false;
	}
	
	protected function _fetchWrapper($key, $function)
	{
		if (isset($this->_fetched[$key]))
			return $this->_fetched[$key];
		
		if (!is_callable($function))
			die('_fetchWrapper called with something not callable: ' . print_r($function, true));

		$this->_fetched[$key] = call_user_func($function);
		$this->_has_updated = false;
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
		return $this->_fetched['raw']['parent'];
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
		return $this->_fetched['raw']['project'];
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