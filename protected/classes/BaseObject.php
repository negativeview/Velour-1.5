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
	
	public static function getAllByType($type)
	{
		$res = db_one("SELECT * FROM top_level_object WHERE id = '" . $type . "'");
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
	
	public static function getByTypeAndId($type, $id)
	{
		$res = db_one("SELECT * FROM top_level_object WHERE id = '" . $type . "'");
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
		$this->_fetch();
		return $this->_rawData['id'];
	}
	
	public function getDiscussURL()
	{
		$this->_fetchType();
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/comments/';
	}
	
	public function getLogURL()
	{
		$this->_fetchType;
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/log/';
	}
	
	public function getSubscribeURL()
	{
		$this->_fetchType;
		return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/subscribe/';
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
	
	protected function _fetchType()
	{
		if ($this->_hashFetchedType)
			return;
		
		$this->_hasFetchedType = true;
		$this->_typeInfo = db_one("SELECT * FROM top_level_object WHERE id = '" . $this->_type . "'");
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
		return false;
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
}