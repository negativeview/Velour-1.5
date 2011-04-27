<?php

class ObjectType
{
	private static $_cache = array();
	private static $_allowed_mappings = array();
	
	protected static function _doAllowances()
	{
		if (!empty(ObjectType::$_allowed_mappings))
			return;
			
		ObjectType::$_allowed_mappings = db_many("SELECT * FROM obj_allowed_mappings");
	}
	
	public function getAll()
	{
		require_once('classes/' . $this->getName() . 'Object.php');
		
		$res = db_many("SELECT * FROM " . $this->getSlug());
		$ret = array();
		$class_name = $this->getName() . 'Object';
		foreach ($res as $row) {
			$tmp = new $class_name($row['id']);
			$tmp->setRaw($row);
			$ret[] = $tmp;
		}
		
		return $ret;
	}
	
	protected $_id;
	
	public function __construct($id)
	{
		$this->_id = $id;
	}
	
	public function getID()
	{
		return $this->_id;
	}
	
	public function getName()
	{
		return ObjectType::$_cache['id' . $this->_id]['name'];
	}
	
	public function toLink()
	{
		return '<a href="/' . $this->getSlug() . '/">' . $this->getName() . '</a>';
	}
	
	public function getSlug()
	{
		return ObjectType::$_cache['id' . $this->_id]['slug'];
	}
	
	public function canHaveChild($id)
	{
		ObjectType::_doAllowances();
		
		foreach (ObjectType::$_allowed_mappings as $k) {
			if ($k['parent_type'] == $this->_id && $k['child_type'] == $id)
				return true;
		}
		
		return false;
	}
}