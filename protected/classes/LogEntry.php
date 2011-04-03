<?php

class LogEntry
{
	private $_row;
	private static $_templates;
	
	public function __construct($row)
	{
		$this->_row = $row;
	}
	
	public function toHTML($smarty)
	{
		if (isset(LogEntry::$_templates['id' . $this->_row['template']])) {
			$template = LogEntry::$_templates['id' . $this->_row['template']];
		} else {
			LogEntry::$_templates['id' . $this->_row['template']] = db_one("SELECT * FROM activity_template WHERE id = '" . $this->_row['template'] . "'");
			$template = LogEntry::$_templates['id' . $this->_row['template']];
		}
		
		$status = $template['status_template'];
		$user_object = UserObject::getById($this->_row['user']);
		$status = str_replace('{user}', $user_object->toLink(), $status);
		$status = str_replace('{project_id}', $this->_row['project'], $status);
		
		$res = db_many("SELECT * FROM activity_key_store WHERE activity = '" . $this->_row['id'] . "'");
		foreach ($res as $key) {
			if ($key['key'] == 'other_user') {
				$other_user = UserObject::getById($key['value']);
				$status = str_replace('{other_user}', $other_user->toLink(), $status);
			} else {
				$status = str_replace(
					'{' . $key['key'] . '}',
					$key['value'],
					$status);
			}
		}
		
		$smarty->assign('status', $status);
		$smarty->assign('raw_row', $this->_row);
		$res = $smarty->fetch('single-log-entry.tpl');
		return $res;
	}
}