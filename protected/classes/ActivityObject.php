<?php

require_once('BaseObject.php');

class ActivityObject extends BaseObject
{
	private static $_activityById = array();
	private static $_templateCache = array();

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
	
	public function canSee()
	{
		return true;
	}
	
	public function getFilledOut()
	{
		$this->_fetch();
		if (!isset(ActivityObject::$_templateCache[$this->_rawData['template']])) {
			ActivityObject::$_templateCache[$this->_rawData['template']] = db_one("SELECT * FROM activity_template WHERE id = '" . $this->_rawData['template'] . "'");
		}
		
		global $objects;
		$objects = db_many("SELECT * FROM activity_objects WHERE activity_id = '" . $this->_id . "'");
		
		$tmp = ActivityObject::$_templateCache[$this->_rawData['template']]['status_template'];
		$tmp = preg_replace_callback('/{([0-9]+)}/',
			function($a) {
				global $objects;
				for ($i = 0; $i < count($objects); $i++) {
					if ($objects[$i]['idx'] + 1 == $a[1]) {
						$ob = BaseObject::getByTypeAndId($objects[$i]['obj_type'], $objects[$i]['obj_id']);
						return $ob->toLink();
					}
				}
			},
			$tmp
		);
		
		return $tmp;
	}
	
	public function getName()
	{
		$this->_fetch();

		return ActivityObject::$_templateCache[$this->_rawData['template']]['status_template'];
	}
	
	public function getCreated()
	{
		$this->_fetch();
		return $this->_rawData['whenit'];
	}
}