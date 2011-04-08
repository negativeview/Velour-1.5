<?php

class ObjectType
{
	private static $_cache = array();
	private static $_allowed_mappings = array();
	
	public static function getById($id)
	{
		// For the time being, there's so few object types, we might as well load them
		// all at once. Why not, right? This may change.
		if (!isset(ObjectType::$_cache['id' . $id])) {
			$tmp = db_many("SELECT * FROM top_level_object");
			foreach ($tmp as $type)
			{
				ObjectType::$_cache['id' . $type['id']] = $type;
			}
			
			ObjectType::_doAllowances();
		}

		return ObjectType::$_cache['id' . $id];
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
	
	protected static function _doAllowances()
	{
		if (empty(ObjectType::$_allowed_mappings))
			ObjectType::$_allowed_mappings = db_many("SELECT * FROM obj_allowed_mappings");
	}
}