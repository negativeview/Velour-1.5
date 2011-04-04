<?

class ActivityLog {

	public static function activityByObject($days) {
		return db_many(
			"SELECT activity.id, obj_type_right AS obj_type, obj_id_right AS obj_id, COUNT(*) FROM activity " .
			"LEFT JOIN obj_to_obj ON (obj_to_obj.obj_type_left = 3 AND obj_to_obj.obj_id_left = activity.id) " .
			"WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(whenit) <= " . ($days * 24 * 60 * 60) . " " .
			"GROUP BY obj_type_right, obj_id_right ORDER BY 4 DESC"
		);
	}
	
	public static function log($name, $kv, $objects = array()) {
		$template = db_one("SELECT * FROM activity_template WHERE name = '" . $name . "'");
		if ($template) {
			db_do("INSERT INTO activity(template, whenit) VALUES('" . $template['id'] . "', NOW())");
			$activity_id = mysql_insert_id();
			
			$keys = array_keys($kv);
			for ($i = 0; $i < count($keys); $i++) {
				db_do("INSERT INTO activity_key_store(activity, `key`, value) VALUES($activity_id, '" . $keys[$i] . "', '" . $kv[$keys[$i]] . "')");
			}
			
			foreach($objects as $obj) {
				db_do("INSERT INTO obj_to_obj(obj_type_left, obj_id_left, obj_type_right, obj_id_right) VALUES(3, $activity_id, '" . $obj->getTypeId() . "', '" . $obj->getID() . "')");
			}
			
			// TODO: This should be responsible for figuring out all the permissions for emails, etc.
			// TODO: The template has the formats for the emails, just need to work in the which-user logic here.
		}
	}
}

?>