<?php

require_once('BaseObject.php');

class CommentObject extends BaseObject
{
	private static $_commentById = array();
	protected $_rawData = array();
	protected $_id;

	public static function getById($id)
	{
		if (isset(CommentObject::$_commentById['id' . $id]))
			return CommentObject::$_commentById['id' . $id];
		
		CommentObject::$_commentById['id' . $id] = new CommentObject($id);
		return CommentObject::$_commentById['id' . $id];
	}

	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['whenit'];
	}
	
	public static function getWithRow($row)
	{
		CommentObject::$_commentById['id' . $row['id']] = new CommentObject($row['id']);
		CommentObject::$_commentById['id' . $row['id']]->_rawData = $row;
		CommentObject::$_commentById['id' . $row['id']]->_hasFetchedRaw = true;
		return CommentObject::$_commentById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(11, $id, 'comment');
		$this->_id = $id;
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
	
	public function getName()
	{
		$this->_fetch();
		return $this->_rawData['name'];
	}
}