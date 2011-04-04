<?php

require_once('BaseObject.php');

class FileObject extends BaseObject
{
	private static $_fileById = array();
	protected $_rawData = array();
	protected $_id;

	public static function getById($id)
	{
		if (isset(FileObject::$_fileById['id' . $id]))
			return FileObject::$_fileById['id' . $id];
		
		FileObject::$_fileById['id' . $id] = new FileObject($id);
		return FileObject::$_fileById['id' . $id];
	}

	public static function getWithRow($row)
	{
		FileObject::$_fileById['id' . $row['id']] = new FileObject($row['id']);
		FileObject::$_fileById['id' . $row['id']]->_rawData = $row;
		FileObject::$_fileById['id' . $row['id']]->_hasFetchedRaw = true;
		return FileObject::$_fileById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(7, $id, 'file');
		$this->_id = $id;
	}

	public function canSee()
	{
		$this->_fetch();
		
		$project = ProjectObject::getById($this->_rawData['project_id']);
		return $project->canSee();
	}
}