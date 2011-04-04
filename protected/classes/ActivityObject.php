<?php

require_once('BaseObject.php');

class ActivityObject extends BaseObject
{
	private static $_activityById = array();
	protected $_rawData = array();
	protected $_id;

	public static function getWithRow($row)
	{
		ActivityObject::$_activityById['id' . $row['id']] = new ActivityObject($row['id']);
		ActivityObject::$_activityById['id' . $row['id']]->_rawData = $row;
		ActivityObject::$_activityById['id' . $row['id']]->_hasFetchedRaw = true;
		return ActivityObject::$_activityById['id' . $row['id']];
	}
	
	public function __construct($id)
	{
		parent::__construct(3, $id, 'activity');
		$this->_id = $id;
	}
}