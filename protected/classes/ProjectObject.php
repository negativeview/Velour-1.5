<?php

class ProjectObject extends BaseObject
{
	private static $_projectsById = array();
	public static function getById($id)
	{
		if (isset(ProjectObject::$_projectsById['id' . $id]))
			return ProjectObject::$_projectsById['id' . $id];
		
		ProjectObject::$_projectsById['id' . $id] = new ProjectObject($id);
		return ProjectObject::$_projectsById['id' . $id];
	}
	
	public static function getWithRow($row)
	{
		ProjectObject::$_projectsById['id' . $row['id']] = new ProjectObject($row['id']);
		ProjectObject::$_projectsById['id' . $row['id']]->_rawData = $row;
		return ProjectObject::$_projectsById['id' . $row['id']];
	}
	

	public function __construct($id)
	{
		parent::__construct(2, $id, 'project');
	}
	
	public function getOwner()
	{
		$this->_fetch();
		return BaseObject::getByTypeAndId(1, $this->_rawData['primary_contact']);
	}
	
	public function getImage()
	{
		return '<img src="/project/' . $this->_id . '/icon.png" />';
	}
	
	public function isPublic()
	{
		return true;
	}
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['public_name'];
	}
	
	public function getBraggable()
	{
		$this->_fetch();
		return $this->_rawData['bio'];
	}
}