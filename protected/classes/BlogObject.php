<?php

require_once('BaseObject.php');

class BlogObject extends BaseObject
{
	private static $_blogById = array();
	protected $_rawData = array();
	protected $_id;

	public static function getById($id)
	{
		if (isset(BlogObject::$_blogById['id' . $id]))
			return BlogObject::$_blogById['id' . $id];
		
		BlogObject::$_blogById['id' . $id] = new BlogObject($id);
		return BlogObject::$_blogById['id' . $id];
	}

	public static function getWithRow($row)
	{
		BlogObject::$_blogById['id' . $row['id']] = new BlogObject($row['id']);
		BlogObject::$_blogById['id' . $row['id']]->_rawData = $row;
		return BlogObject::$_blogById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(12, $id, 'blog');
		$this->_id = $id;
	}
	
	public function getBody()
	{
		$this->_fetch();
		return $this->_rawData['body'];
	}
	
	public function canSee()
	{
		return true;
	}
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['title'];
	}
	
	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['whenit'];
	}
	
	public function getOwner()
	{
		$this->_fetch();
		
		require_once('classes/UserObject.php');
		return UserObject::getById($this->_rawData['user']);
	}
}