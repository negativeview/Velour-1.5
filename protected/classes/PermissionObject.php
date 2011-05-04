<?php

require_once('BaseObject.php');

/**
 * Base object for all ARGTech top-level-objects.
 *
 * All top-level objects extend from this class. This makes sure that we have some core
 * functionality present on all of the objects. So far these include the ability to be
 * constructed via a type/id pair, becoming a front-page featured object, having
 * comments able to be put on the object, being able to favorite an object, etc.
 */

class PermissionObject extends BaseObject
{
	private $_cachedRaw;
	
	private static $_byId;
	public static function getById($id)
	{
		if (!PermissionObject::$_byId) {
			PermissionObject::$_byId = array();
		}
		
		if (!isset(PermissionObject::$_byId['a' . $id])) {
			PermissionObject::$_byId['a' . $id] = new PermissionObject($id);
		}
		
		return PermissionObject::$_byId['a' . $id];
	}
	
	/**
	 * Constructor
	 *
	 * @param integer $id The id of this row in the obj_static table.
	 * @return BaseObject
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->_cachedRaw = array();
	}
	
	protected function _realFetchRaw()
	{
		$raw = parent::_realFetchRaw();
		$this->_cachedRaw = $raw;
		return $raw;
	}
	
	public function __destruct()
	{
		$raw = parent::_fetchRaw();
		
		$ak = array_keys($this->_cachedRaw);
		foreach($ak as $key) {
			if ($this->_cachedRaw[$key] != $raw[$key]) {
				
				db_do("UPDATE obj_static SET views = views + 1 WHERE id = '" . $this->_id . "'");
			}
		}
	}
	
	public function isPublic()
	{
		$raw = $this->_fetchRaw();
		
		$this->_fetched['raw']['title'] = 'foo';
		switch ($raw['privacy_setting']) {
			case 'complex':
				return false;
			case 'parent':
				$parent = $this->getParent();
				if (!$parent)
					return false;
				return $parent->isPublic();
			case 'project':
				$project = $this->getProject();
				if (!$project)
					return false;
				return $project->isPublic();
			case 'public':
				return true;
			default:
				die('Unknown privacy setting: ' . $raw['privacy_setting']);
		}
	}
	
	public function getParent()
	{
		$raw = $this->_fetchRaw();

		if (!$raw['parent'])
			return null;
		
		return PermissionObject::getById($raw['parent']);
	}
	
	public function getProject()
	{
		$raw = $this->_fetchRaw();
		
		if (!$raw['project'])
			return null;
		
		return PermissionObject::getById($raw['project']);
	}
	
	public function toURL()
	{
		$raw = $this->_fetchRaw();
		return '/' . $raw['slug'] . '/' . $raw['id'] . '/';
	}
	
	public function toLink()
	{
		return '<a href="' . $this->toURL() . '">' . $this->getName() . '</a>';
	}
	
	public function getTypeName()
	{
		$raw = $this->_fetchRaw();
		return $raw['menu_title'];
	}
	
	public function getName()
	{
		$raw = $this->_fetchRaw();
		return $raw['title'];
	}
	
	public function getImage()
	{
		return '';
	}
	
	public function getCreated()
	{
		$raw = $this->_fetchRaw();
		return $raw['created'];
	}
	
	public function getOwner()
	{
		$raw = $this->_fetchRaw();
		$creator = $raw['creator'];
		if (!$creator)
			return null;
		
		return PermissionObject::getById($creator);
	}
	
	public function getBraggable()
	{
		$raw = $this->_fetchRaw();
		return $raw['description'];
	}
}