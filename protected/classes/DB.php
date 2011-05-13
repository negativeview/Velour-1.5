<?php

require_once('htdocs/database.php');
			
class DB
{
	private static $_instance = null;
	private $_queryInfo = null;
	private $_queryCache = null;
	
	public static function getInstance()
	{
		if (self::$_instance === null) {
			global $db_host;
			global $db_user;
			global $db_password;
			global $db_name;
			
			self::$_instance = new DB($db_host, $db_user, $db_password, $db_name);
		}
		
		return self::$_instance;
	}
	
	private function __construct($host, $username, $password, $database)
	{
		$this->_host = $host;
		$this->_username = $username;
		$this->_password = $password;
		$this->_database = $database;
		$this->_queryCache = array();
		
		$this->_connection = mysql_connect($this->_host, $this->_username, $this->_password);
		mysql_select_db($this->_database);
	}
	
	public function clearCache()
	{
		$this->_queryCache = array();
	}
	
	public function getCache()
	{
		return $this->_queryCache;
	}
	
	public function startInsert($tableName)
	{
		$this->_queryInfo = array(
			'table' => $tableName,
			'columns' => array()
		);
	}
	
	public function endInsert()
	{
		$query = "INSERT INTO " . $this->_queryInfo['table'] . " (";
		$i = 0;
		foreach ($this->_queryInfo['columns'] as $name => $value) {
			if ($i != 0)
				$query .= ", ";
			$query .= $name;
			$i++;
		}
		$query .= ") VALUES(";
		$i = 0;
		foreach ($this->_queryInfo['columns'] as $name => $value) {
			if ($i != 0)
				$query .= ", ";
			$query .= "'" . $value . "'";
			$i++;
		}
		$query .= ")";
		
		$this->_query($query);
		
		return mysql_insert_id();
	}
	
	public function startUpdate($tableName)
	{
		$this->_queryInfo = array(
			'table' => $tableName,
			'columns' => array()
		);
	}
	
	public function setColumn($column, $value)
	{
		$this->_queryInfo['columns'][$column] = $value;
	}
	
	public function endUpdate($id)
	{
		$query = "UPDATE " . $this->_queryInfo['table'] . " SET ";
		foreach ($this->_queryInfo['columns'] as $name => $value) {
			$query .= " " . $name . " = '" . $value . "'";
		}
		$query .= " WHERE id = '" . $id . "'";
		$this->_query($query);
	}
	
	public function addTable($tableName)
	{
		$this->_queryInfo = array(
			'db' => array(
				array(
					'tableName'    => $tableName,
					'alias'        => $tableName,
					'fields'       => array(),
					'stringFields' => array(),
					'textFields'   => array()
				)
			),
			'conditions' => array(
				'whereEquals' => array()
			)
		);
		
		return $this;
	}
	
	public function addColumns($tableName, $columns)
	{
		foreach($this->_queryInfo['db'] as $key => $table) {
			if ($table['tableName'] == $tableName) {
				foreach($columns as $column) {
					$foundColumn = false;
					foreach ($table['fields'] as $field) {
						if ($field['name'] == $column) {
							$foundColumn = true;
							break;
						}
					}
					if ($foundColumn)
						continue;
					
					$this->_queryInfo['db'][$key]['fields'][] = $column;
				}

				return $this;
			}
		}
		
		throw new Exception('Could not find table ' . $tableName);
	}
	
	public function addJoin($table, $joinTo, $columnLeft, $columnRight)
	{
		// Check that the joinTo table exists.
		$foundTable = false;
		foreach ($this->_queryInfo['db'] as $t) {
			if ($t['tableName'] == $joinTo) {
				$foundTable = true;
				break;
			}
		}
		
		if (!$foundTable) {
			throw new Exception('Did not find table you intend to join to.');
		}
		
		$this->_queryInfo['db'][] = array(
			'tableName'    => $table,
			'alias'        => $table,
			'fields'       => array(),
			'joinTo'       => $joinTo,
			'columnLeft'   => $columnLeft,
			'columnRight'  => $columnRight,
			'stringFields' => array(),
			'textFields'   => array()
		);
		
		return $this;
	}
	
	public function addWhereEquals($table, $column, $value)
	{
		$this->_queryInfo['conditions']['whereEquals'][] = array(
			'table'  => $table,
			'column' => $column,
			'value'  => $value
		);
	}
	
	public function addStringField($table, $field)
	{
		foreach ($this->_queryInfo['db'] as $key => $t) {
			if ($t['tableName'] == $table) {
				$this->_queryInfo['db'][$key]['stringFields'][] = $field;
				return;
			}
		}
		
		throw new Exception('Could not find string field:' . print_r($this->_queryInfo, 1));
	}
	
	public function addTextField($table, $field)
	{
		foreach ($this->_queryInfo['db'] as $key => $t) {
			if ($t['tableName'] == $table) {
				$this->_queryInfo['db'][$key]['textFields'][] = $field;
				return;
			}
		}
		
		throw new Exception('Could not find text field:' . print_r($this->_queryInfo, 1));
	}
	
	public function getAll()
	{
		$query = $this->_buildQuery();

		$res = $this->_query($query);
		
		$ret = array();
		while ($res = mysql_fetch_assoc($res)) {
			$ret[] = $this->_buildResult($res);
		}
		
		return $ret;
	}
	
	public function getSingleResult()
	{
		$query = $this->_buildQuery();

		$res = $this->_query($query);
		$res = mysql_fetch_assoc($res);
		
		$ret = $this->_buildResult($res);
	}
	
	private function _buildResult($res)
	{
		$ret = array();
		foreach ($this->_queryInfo['db'] as $table) {
			$tmp = array();
			
			foreach ($table['fields'] as $field) {
				$tmp2 = array();
				
				$tmp2['value'] = $res[$table['tableName'] . '_' . $field];
				$tmp2['type'] = 'raw';
				
				if (in_array($field, $table['stringFields'])) {
					$tmp2['type'] = 'string';
					$tmp['_' . $field] = $res['_' . $table['tableName'] . '_' . $field];
				}
				if (in_array($field, $table['textFields'])) {
					$tmp2['type'] = 'text';
					$tmp['_' . $field] = $res['_' . $table['tableName'] . '_' . $field];
				}
				
				$tmp[$field] = $tmp2;
			}
			
			$ret[$table['tableName']] = $tmp;
		}
		
		return $ret;
	}
	
	private function _buildQuery()
	{
		$query = "SELECT ";
		
		$i = 0;
		foreach ($this->_queryInfo['db'] as $table) {
			foreach ($table['fields'] as $field) {
				if ($i != 0)
					$query .= ', ';
			
				$query .= $table['tableName'] . '.' . $field . ' AS ' . $table['tableName'] . '_' . $field;
				$i++;
			}
		}
		
		foreach ($this->_queryInfo['db'] as $table) {
			foreach ($table['stringFields'] as $field) {
				$query .= ', ' . $table['tableName'] . '.' . $field . ' AS _' . $table['tableName'] . '_' . $field;
				$query .= ', (SELECT value FROM obj_string WHERE obj_string.id = ' . $table['tableName'] . '.' . $field . ') AS ' . $table['tableName'] . '_' . $field;
			}
		}

		foreach ($this->_queryInfo['db'] as $table) {
			foreach ($table['textFields'] as $field) {
				$query .= ', ' . $table['tableName'] . '.' . $field . ' AS _' . $table['tableName'] . '_' . $field;
				$query .= ', (SELECT value FROM obj_text WHERE obj_text.id = ' . $table['tableName'] . '.' . $field . ') AS ' . $table['tableName'] . '_' . $field;
			}
		}

		$firstTable = $this->_queryInfo['db'][0];
		$query .= ' FROM ' . $firstTable['tableName'];
		
		for ($i = 1; $i < count($this->_queryInfo['db']); $i++) {
			$table = $this->_queryInfo['db'][$i];
			
			$query .= ' LEFT JOIN ' . $table['tableName'] . ' ON (' . $table['tableName'] . '.' . $table['columnLeft'] . ' = ' . $table['joinTo'] . '.' . $table['columnRight'] . ')';
		}
		
		$firstWhere = $this->_queryInfo['conditions']['whereEquals'][0];
		$query .= ' WHERE ' . $firstWhere['table'] . '.' . $firstWhere['column'] . ' = ' . $firstWhere['value'];
		
		return $query;
	}
	
	private function _query($query)
	{
		$this->_queryCache[] = $query;
		
		$res = mysql_query($query);
		if (mysql_error())
			die(mysql_error() . ': ' . $query);
		
		return $res;
	}
}