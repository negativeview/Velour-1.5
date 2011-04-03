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
	
	public static function tryCreate($username, $passone, $passtwo, &$errors)
	{
		$username = trim($username);
		if (!$username) {
			$errors[] = 'Username cannot be blank.';
		}
		
		$errors = array();
		if ($passone != $passtwo) {
			$errors[] = 'Passwords do not match.';
		}
		
		if ($passone == '') {
			$errors[] = 'Password cannot be an empty string.';
		}
		
		$res = db_one("SELECT * FROM user WHERE email = '" . $username . "'");
		if ($res)
			$errors[] = 'Email address is already in use.';
		
		if (count($errors))
			return null;

		$characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-=_+[]\{}|;':,./<>?";
		$salt = $characters[mt_rand(0, strlen($characters))] . $characters[mt_rand(0, strlen($characters))];
		db_do("INSERT INTO user(email, passhash, signup, power, salt) VALUES('" . $username . "', '" .
			MD5($salt . $passone) . "', NOW(), 2, '" . $salt . "')");
		$user_id = mysql_insert_id();
		
		db_do("INSERT INTO object_permissions(obj_type, obj_id, permission_type) VALUES(1, $user_id, 2)");
		
		return UserObject::getById($user_id);
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
	
	public function setLoggedIn()
	{
		session_start();
		$_SESSION['user'] = $this->_id;
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