<?php

require_once('ObjectType.php');

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
	protected $_id;
	protected $_hasFetchedRaw = false;
	protected $_rawData;
	protected $_tableName;
	protected $_hasFetchedPermissions = false;
	protected $_permissionInfo;
	
	public static function getAllByType($type)
	{
		require_once('ObjectType.php');
		$res = new ObjectType($type);
		
		return $res->getAll();
	}
	
	public static function getByTypeAndRow($type, $row)
	{
		require_once('classes/ObjectType.php');
		
		$res = new ObjectType($type);
		if (!$res)
			die("There is no top level object with id " . $type);
		$className = $res->getName() . 'Object';

		require_once('classes/' . $className . '.php');
		return $className::getWithRow($row);
	}
	
	public static function getByTypeAndId($type, $id)
	{
		require_once('classes/ObjectType.php');
		$res = new ObjectType($type);
		if (!$res)
			die("There is no top level object with id " . $type);
		$className = $res->getName() . 'Object';
		
		require_once('classes/' . $className . '.php');
		return $className::getById($id);
	}
	
	public function __construct($type, $id, $tableName)
	{
		$this->_type = new ObjectType($type);
		$this->_id = $id;
		$this->_tableName = $tableName;
	}
	
	public function getType()
	{
		return $this->_type;
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	private $_hasDoneCanSee = false;
	private $_canSee = false;
	public function canSee()
	{
		if ($this->_hasDoneCanSee)
			return $this->_canSee;
		
		$this->_hasDoneCanSee = true;
		
		global $user;
		
		// If they aren't logged in, the logic is the same as the public function.
		// Skip a lot of work in that case.
		if (!$user) {
			$this->_canSee = $this->isPublic();
			return $this->_canSee;
		}
		
		// Even if we're logged in, if they're public, we can see it.
		if ($this->isPublic()) {
			$this->_canSee = true;
			return true;
		}

		if ($this->userIsUnder()) {
			$this->_canSee = true;
			return true;
		}

		$res = db_one("SELECT * FROM obj_to_obj WHERE obj_type_left = 1 AND obj_id_right = '" . $user->getId() . 
			"' AND obj_type_right = '" . $this->_type->getID() . "' AND obj_id_right = '" . $this->getId() . "'");
		if ($res) {
			$this->_canSee = true;
			return true;
		}

		$this->_canSee = false;
		return false;
	}
	
	public function getDiscussURL()
	{
		return '/' . $this->_type->getSlug() . '/' . $this->_id . '/comments/';
	}
	
	public function getLogURL()
	{
		return '/' . $this->_type->getSlug() . '/' . $this->_id . '/log/';
	}
	
	public function getSubscribeURL()
	{
		return '/' . $this->_type->getSlug() . '/' . $this->_id . '/subscribe/';
	}
	
	public function getToDoURL()
	{
		return '/' . $this->_type->getSlug() . '/' . $this->_id . '/todo/';
	}
	
	public function toLink()
	{
		return '<a href="' . $this->toURL() . '">' . $this->getName() . '</a>';
	}
	
	public function toURL()
	{
		return '/' . $this->_type->getSlug() . '/' . $this->_id . '/';
	}
	
	public function userIsUnder()
	{
		global $user;
		
		if (!$user)
			return false;
		
		$this->_fetchObjectsUnder();
		
		foreach ($this->_subObjects as $subObject) {
			if ($subObject->getTypeId() == 1 && $subObject->getId() == $user->getId())
				return true;
		}
		return false;
	}
	
	protected $_isOwned;
	protected $_hasFetchedOwned;
	
	public function addComment($body)
	{
		global $user;
		
		db_do("INSERT INTO comment(body, whenit) VALUES('$body', now())");
		$comment_id = mysql_insert_id();
		
		db_do("INSERT INTO obj_to_obj(obj_type_left, obj_id_left, obj_type_right, obj_id_right) VALUES(1, '" . $user->getID() . "', 11, $comment_id)");
		db_do("INSERT INTO obj_to_obj(obj_type_left, obj_id_left, obj_type_right, obj_id_right) VALUES('" . $this->getType()->getID() . "', '" . $this->_id . "', 11, $comment_id)");
	}
	
	public function isOwned()
	{
		if ($this->_hasFetchedOwned)
			return $this->_isOwned;
		$this->_hasFetchedOwned = true;
		
		global $user;
		
		if (!$user) {
			$this->_isOwned = false;
			return false;
		}
		
		if ($user == $this)
			return true;
		
		$res = db_one("SELECT * FROM obj_to_obj WHERE obj_type_left = 1 AND obj_id_left = '" . $user->getID() .
			"' AND obj_type_right = '" . $this->getTypeId() . "' AND obj_id_right = '" . $this->_id . "'");
		if ($res) {
			$this->_isOwned = true;
			return true;
		}
		
		$this->_isOwned = false;
		return false;
	}
	
	protected function _fetchPermissions()
	{
		if ($this->_hasFetchedPermissions)
			return;
		$this->_hasFetchedPermissions = true;
		
		$this->_permissionInfo = db_one(
			"SELECT * FROM object_permissions WHERE obj_type = " . $this->_type->getID() . " AND obj_id = " . $this->_id);
	}

	protected $_subObjects;
	protected $_haveFetchedSubObjects = false;
	
	protected function _fetchObjectsUnder()
	{
		if ($this->_haveFetchedSubObjects)
			return;
		$this->_haveFetchedSubObjects = true;
		
 		$res = db_many("SELECT * FROM obj_to_obj WHERE obj_type_left = '" . $this->getTypeId() .
			"' AND obj_id_left = '" . $this->getId() . "'");
		$this->_subObjects = array();
		foreach ($res as $object) {
			$ob = BaseObject::getByTypeAndId($object['obj_type_right'], $object['obj_id_right']);
			$this->_subObjects[] = $ob;			
		}
	}

	public function getObjectsUnder()
	{
		$this->_fetchObjectsUnder();
		return $this->_subObjects;
	}
	
	public function getSubObjects($type, $autoExpand = false)
	{
		$this->_fetchObjectsUnder();
		
		$ret = array();
		foreach($this->_subObjects as $subObject) {
			if ($subObject->getTypeId() == $type)
				$ret[] = $subObject;
		}
		
		if ($autoExpand) {
			$real_ret = array();
			$ids = array();
			foreach($ret as $r) {
				if ($r->deflated())
					$ids[] = $r->getId();
			}
			
			if (count($ids) == 0)
				return $ret;
			
			$type = new ObjectType($type);
			
			$res = db_many("SELECT * FROM " . $type->getSlug() . " WHERE id IN('" . implode("', '", $ids) . "')");
			foreach($res as $row) {
				$real_ret[] = BaseObject::getByTypeAndRow($type->getID(), $row);
			}
			
			return $real_ret;
		}
		
		return $ret;
	}
	
	public function getTypeId()
	{
		return $this->_type->getID();
	}
	
	public function getTypeName()
	{
		return $this->_type->getName();
	}
	
	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['created'];
	}
	
	public function getOwner()
	{
		$this->_fetch();
		if (isset($this->_rawData['user_id']))
			return UserObject::getById($this->_rawData['user_id']);
		return null;
	}
	
	/**
	 * A convenience function for lazy loading.
	 *
	 * This probably does not need to be overriden, though you're free to either do so or to not use this
	 * function. The idea is that in all your getFoo functions, you'd $this->_fetch() first then $this->_rawData['foo']
	 */
	protected function _fetch()
	{
		if ($this->_hasFetchedRaw)
			return;
		
		$this->_hasFetchedRaw = true;
		$this->_rawData = db_one("SELECT * FROM " . $this->_tableName . " WHERE id = '" . $this->_id . "'");
	}
	
	public function setRaw($row)
	{
		$this->_hasFetchedRaw = true;
		$this->_rawData = $row;
	}
	
	/**
	 * For any area of the site that displays objects without caring what they are.
	 *
	 * Provide the entire HTML of the image tag. This gives you the ability to have no image,
	 * or to have class-specific alt text, etc.
	 */
	public function getImage()
	{
		return '';
	}
	
	/**
	 * Determines if this object allows any of its information to be available to the world at large.
	 *
	 * This should be overridden! Basically, do you want to be allowed on the front page, if you become
	 * popular enough?
	 */
	public function isPublic()
	{
		$this->_fetchPermissions();
		return $this->_permissionInfo['permission_type'] == 1;
	}
	
	/**
	 * The name that should be displayed for this object.
	 *
	 * You ARE allowed to change this depending on the user logged in, etc. It's only really used in
	 * the front-end, so it won't mess anything up if you have an infinite number of these in theory.
	 */
	public function getName()
	{
		return '';
	}
	
	protected $_hasGottenBraggable = false;
	public function getBraggable()
	{
		if (!$this->_hasGottenBraggable) {
			$this->_braggable = db_one("SELECT * FROM obj_desc WHERE obj_type = '" . $this->_type->getID() . "' AND obj_id = '" . $this->_id . "'");
			$this->_hasGottenBraggable = true;
		}
		
		return $this->_braggable['description'];
	}
	
	public function setBraggable($braggable)
	{
		$this->getBraggable();
		if ($this->_braggable) {
			db_do("UPDATE obj_desc SET description = '$braggable' WHERE id = '" . $this->_braggable['id'] . "'");
		} else {
			db_do("INSERT INTO obj_desc(obj_type, obj_id, description) VALUES('" . $this->_type->getID() . "', '" . $this->_id . "', '$braggable')");
		}
	}
		
	public function deflated()
	{
		return !$this->_hasFetchedRaw;
	}
}