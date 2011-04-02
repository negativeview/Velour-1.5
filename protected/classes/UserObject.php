<?php

require_once('BaseObject.php');

class UserObject extends BaseObject
{
	private static $_usersById = array();
	public static function getById($id)
	{
		if (isset(UserObject::$_usersById['id' . $id]))
			return UserObject::$_usersById['id' . $id];
		
		UserObject::$_usersById['id' . $id] = new UserObject($id);
		return UserObject::$_usersById['id' . $id];
	}
	
	public static function getWithRow($row)
	{
		UserObject::$_usersById['id' . $row['id']] = new UserObject($row['id']);
		UserObject::$_usersById['id' . $row['id']]->_rawData = $row;
		return UserObject::$_usersById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(1, $id, 'user');
	}
	
	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['signup'];
	}
	
	public function getImage()
	{
		return '<img src="/user/' . $this->_id . '/icon.png" />';
	}
	
	public function isPublic()
	{
		return true;
	}
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['display_name'];
	}
	
	public function getBraggable()
	{
		$this->_fetch();
		return $this->_rawData['bio'];
	}
}