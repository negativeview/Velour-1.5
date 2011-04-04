<?php

require_once('BaseObject.php');

class CharacterObject extends BaseObject
{
	private static $_characterById = array();
	protected $_rawData = array();
	protected $_id;

	public static function getById($id)
	{
		if (isset(CharacterObject::$_characterById['id' . $id]))
			return CharacterObject::$_characterById['id' . $id];
		
		CharacterObject::$_characterById['id' . $id] = new CharacterObject($id);
		return CharacterObject::$_characterById['id' . $id];
	}

	public static function getWithRow($row)
	{
		CharacterObject::$_characterById['id' . $row['id']] = new CharacterObject($row['id']);
		CharacterObject::$_characterById['id' . $row['id']]->_rawData = $row;
		CharacterObject::$_characterById['id' . $row['id']]->_hasFetchedRaw = true;
		return CharacterObject::$_characterById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(5, $id, 'game_character');
		$this->_id = $id;
	}

	public function canSee()
	{
		$this->_fetch();
		
		$project = ProjectObject::getById($this->_rawData['project_id']);
		return $project->canSee();
	}
}