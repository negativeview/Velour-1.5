<?php

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
	protected $_type;
	protected $_id;
	protected $_hasFetchedRaw = false;
	protected $_rawData;
	protected $_tableName;
	protected $_hasFetchedType = false;
	protected $_typeInfo;
	protected $_hasFetchedPermissions = false;
	protected $_permissionInfo;
	
	public static function getAllByType($type)
	{
		require_once('ObjectType.php');
		$res = ObjectType::getByid($type);
		$className = $res['name'] . 'Object';
		
		require_once('classes/' . $className . '.php');
		
		$table_name = $res['slug'];
		$res = db_many("SELECT * FROM " . $table_name);
		$ret = array();
		foreach ($res as $r) {
			$ret[] = $className::getWithRow($r);
		}
		
		return $ret;
	}
	
	public static function getByTypeAndRow($type, $row)
	{
		require_once('classes/ObjectType.php');
		
		$res = ObjectType::getById($type);
		if (!$res)
			die("There is no top level object with id " . $type);
		$className = $res['name'] . 'Object';

		require_once('classes/' . $className . '.php');
		return $className::getWithRow($row);
	}
	
	public static function getByTypeAndId($type, $id)
	{
		require_once('classes/ObjectType.php');
		$res = ObjectType::getById($type);
		if (!$res)
			die("There is no top level object with id " . $type);
		$className = $res['name'] . 'Object';
		
		require_once('classes/' . $className . '.php');
		return $className::getById($id);
	}
	
	public function __construct($type, $id, $tableName)
	{
		$this->_type = $type;
		$this->_id = $id;
		$this->_tableName = $tableName;
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
			"' AND obj_type_right = '" . $this->_typeInfo['id'] . "' AND obj_id_right = '" . $this->getId() . "'");
		if ($res) {
			$this->_canSee = true;
			return true;
		}

		$this->_canSee = false;
		return false;
	}
	
	public function getDiscussURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/comments/';
	}
	
	public function getLogURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/log/';
	}
	
	public function getSubscribeURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/subscribe/';
	}
	
	public function getToDoURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/todo/';
	}
	
	public function toLink()
	{
		return '<a href="' . $this->toURL() . '">' . $this->getName() . '</a>';
	}
	
	public function toURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/';
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
		
		$this->_permissionInfo = db_one("SELECT * FROM object_permissions WHERE obj_type = " . $this->_type . " AND obj_id = " . $this->_id);
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
			
			$type = ObjectType::getById($type);
			
			$res = db_many("SELECT * FROM " . $type['slug'] . " WHERE id IN('" . implode("', '", $ids) . "')");
			foreach($res as $row) {
				$real_ret[] = BaseObject::getByTypeAndRow($type['id'], $row);
			}
			
			return $real_ret;
		}
		
		return $ret;
	}
	
	public function getLogs()
	{
		require_once('LogEntry.php');
		require_once('ProjectObject.php');
		require_once('UserObject.php');
		
		$res = db_many(
			"SELECT activity.*, activity_template.*, activity.id AS id " .
			"FROM activity " .
			"LEFT JOIN activity_template ON (activity.template = activity_template.id) " .
			"WHERE activity.id IN (" .
				"SELECT obj_id_left FROM obj_to_obj WHERE obj_type_left = 3 AND obj_type_right = '" . $this->getTypeId() . "' AND obj_id_right = '" . $this->getId() . "'" .
			")"
		);
		$ret = array();
		
		$log_ids = array();
		foreach ($res as $log) {
			$can_see = false;

			switch ($log['permission_type']) {
				case 'user':
					$user = UserObject::getById($log['user']);
					if ($user->isPublic())
						$can_see = true;
					break;
				case 'project':
					$project = ProjectObject::getById($log['project']);
					if ($project->isPublic())
						$can_see = true;
					break;
				case 'system':
					$can_see = false;
					break;
			}
			
			if ($can_see) {
				$ret[] = new LogEntry($log);
				$log_ids[] = $log['id'];
			}
		}
		
		if (count($log_ids)) {
			$res = db_many("SELECT * FROM activity_key_store WHERE activity IN ('" . implode("', '", $log_ids) . "')");
			
			foreach ($res as $activitykv) {
				foreach ($ret as $activity) {
					if ($activity->getId() == $activitykv['activity']) {
						$activity->setKeyValue($activitykv['key'], $activitykv['value']);
						break;
					}
				}
			}
		}
		
		return $ret;
	}
	
	protected function _fetchType()
	{
		require_once('classes/ObjectType.php');
		
		if ($this->_hasFetchedType)
			return;
		
		$this->_hasFetchedType = true;
		$this->_typeInfo = ObjectType::getById($this->_type);
	}
	
	public function getTypeId()
	{
		$this->_fetchType();
		return $this->_typeInfo['id'];
	}
	
	public function getTypeName()
	{
		$this->_fetchType();
		return $this->_typeInfo['name'];
	}
	
	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['created'];
	}
	
	public function getOwner()
	{
		$this->_fetch();
		return $this->_rawData['user_id'];
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
	
	/**
	 * If you ever get on the home page as a super-awesome object, what do you want to be displayed for you?
	 *
	 * There are no real requirements for this. It will be placed as-is into the home page. Valid HTML, please.
	 */
	public function getBraggable()
	{
		return '';
	}
	
	public function deflated()
	{
		return !$this->_hasFetchedRaw;
	}
}