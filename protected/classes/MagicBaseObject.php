<?php

require_once('BaseObject.php');

class MagicBaseObject extends BaseObject {
	protected $_rawDataCache;
	
	public function __construct()
	{
		super::__construct();
		$this->recordRawData();
	}
	
	protected function recordRawData()
	{
		$this->_rawDataCache = array();
		foreach ($this->_rawData as $k => $v) {
			$this->_rawDataCache[$k] = $v;
		}
	}
	
	public function __destruct()
	{
		if (!isset($this->_rawDataCache))
			return;
		if (!isset($this->_rawData))
			return;
		
		$columnsToUpdate = array();
		foreach($this->_rawDataCache as $k => $v) {
			if (!isset($this->_rawData[$k]))
				continue;
			
			if ($this->_rawData[$k] != $this->_rawDataCache[$k])
				$columnsToUpdate[] = $k;
		}
		
		if (count($columnsToUpdate)) {
			$q = "UPDATE " . $this->_tableName . " SET ";
			$i = 0;
			foreach ($columnsToUpdate as $column) {
				if ($i != 1)
					$q .= ", ";
				$q .= $column . " = '" . mysql_real_escape_string($this->_rawData[$k]) . "'";
				$i++;
			}
			
			$q .= " WHERE id = " . $this->_rawData['id'];
		}
		
		db_do($q);
	}
}