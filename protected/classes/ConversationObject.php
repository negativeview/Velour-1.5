<?php

require_once('classes/BaseObject.php');
require_once('classes/ProjectObject.php');

class ConversationObject extends BaseObject
{
	private static $_conversationsById = array();

	public static function getById($id)
	{
		if (isset(ConversationObject::$_conversationsById['id' . $id]))
			return ConversationObject::$_conversationsById['id' . $id];
		
		ConversationObject::$_conversationsById['id' . $id] = new ConversationObject($id);
		return ConversationObject::$_conversationsById['id' . $id];
	}
	
	public static function getWithRow($row)
	{
		ConversationObject::$_conversationsById['id' . $row['id']] = new ConversationObject($row['id']);
		ConversationObject::$_conversationsById['id' . $row['id']]->_rawData = $row;
		ConversationObject::$_conversationsById['id' . $row['id']]->_hasFetchedRaw = true;
		return ConversationObject::$_conversationsById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(8, $id, 'conversation');
	}
	
	public function canSee()
	{
		$this->_fetch();
		
		$project = ProjectObject::getById($this->_rawData['project_id']);
		return $project->canSee();		
	}
	
	public function getBody()
	{
		$this->_fetch();
		return $this->_rawData['body'];
	}
	
	public function getTitle()
	{
		$this->_fetch();
		return $this->_rawData['title'];
	}
}