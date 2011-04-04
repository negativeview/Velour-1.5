<?php

require_once('BaseObject.php');

class ToDoObject extends BaseObject
{
	private static $_todoById = array();

	public static function getWithRow($row)
	{
		ToDoObject::$_todoById['id' . $row['id']] = new ToDoObject($row['id']);
		ToDoObject::$_todoById['id' . $row['id']]->_hasFetchedRaw = true;
		ToDoObject::$_todoById['id' . $row['id']]->_rawData = $row;
		return ToDoObject::$_todoById['id' . $row['id']];
	}
	
	public static function getById($id)
	{
		if (!isset(ToDoObject::$_todoById['id' . $id]))
			ToDoObject::$_todoById['id' . $id] = new ToDoObject($id);
		return ToDoObject::$_todoById['id' . $id];
	}
	
	public function getPriorityName()
	{
		$this->_fetch();
		
		switch($this->_rawData['priority']){
			case 0:
				return 'Unsorted';
			case 1:
				return 'Probably Not';
			case 2:
				return 'Might Do';
			case 3:
				return 'Should Do';
			case 4:
				return 'Must Do';
			case 5:
				return 'Do NOW';
		}
	}
	
	public function getCreator()
	{
		$this->_fetch();
		
		return UserObject::getById($this->_rawData['creator_id']);
	}
	
	public function getAssigned()
	{
		$this->_fetch();
		
		return UserObject::getById($this->_rawData['user_id']);		
	}

	public function isActive()
	{
		$this->_fetch();
		return $this->_rawData['status'] == 2;
	}
	
	public function __construct($id)
	{
		parent::__construct(4, $id, 'todo');
		$this->_id = $id;
	}
	
	public function getBraggable()
	{
		$this->_fetch();
		
		return $this->_rawData['body'];
	}
	
	public function canSee()
	{
		$this->_fetch();
		
		require_once('classes/ProjectObject.php');
		$project = ProjectObject::getById($this->_rawData['project_id']);
		return $project->canSee();
	}
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['title'];
	}
}