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
		ProjectObject::$_projectsById['id' . $row['id']]->_hasFetchedRaw = true;
		return ProjectObject::$_projectsById['id' . $row['id']];
	}
	
	public function getDiscussURL()
	{
		global $user;
		
		$sees_full = false;
		if ($user) {
			if ($this->userIsUnder($user))
				$sees_full = true;
		}
		
		if ($sees_full)
			return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/conversation/';
		else
			return '/' . $this->_typeInfo['slug'] . '/' . $this->_id . '/comments/';
	}
	
	public function __construct($id)
	{
		parent::__construct(2, $id, 'project');
	}
	
	public function getSummaryURL()
	{
		return '/project/' . $this->_id . '/';
	}
	
	public function getDashboardURL()
	{
		return '/project/' . $this->_id . '/dashboard/';
	}
	
	public function getTodos()
	{
		$res = $this->getSubObjects(4, true);
		$ret = array();
		foreach($res as $todo) {
			if ($todo->isActive())
				$ret[] = $todo;
		}
		
		return $ret;
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
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['public_name'];
	}
}