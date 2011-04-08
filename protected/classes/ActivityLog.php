<?

class ActivityLog {
	public static function activityByObject($days) {
		return db_many(
			"SELECT activity.id, obj_type, obj_id, COUNT(*) FROM activity " .
				"LEFT JOIN activity_objects ON (activity_objects.activity_id = activity.id) " .
				"WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(whenit) <= " . ($days * 24 * 60 * 60) . " " .
				"GROUP BY obj_type, obj_id ORDER BY 4 DESC"
		);
	}

	public static function log($type, $name, $kv, $objects = array()) {
		$template = db_one("SELECT * FROM activity_template WHERE type='$type' AND name = '$name'");
		if ($template) {
			db_do("INSERT INTO activity(template, whenit) VALUES('" . $template['id'] . "', NOW())");
			$activity_id = mysql_insert_id();
			
			$keys = array_keys($kv);
			for ($i = 0; $i < count($keys); $i++) {
				db_do("INSERT INTO activity_key_store(activity, `key`, value) VALUES($activity_id, '" . $keys[$i] . "', '" . $kv[$keys[$i]] . "')");
			}
			
			for ($i = 0; $i < count($objects); $i++) {
				$obj = $objects[$i];
				db_do("INSERT INTO activity_objects(activity_id, obj_type, obj_id, idx) VALUES('$activity_id', '" . $obj->getTypeId() . "', '" . $obj->getID() . "', '$i')");
			}
		}
	}
}

?>