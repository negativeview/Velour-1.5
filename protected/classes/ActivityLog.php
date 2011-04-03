<?

class ActivityLog {

	public static function activityByObject($days) {
		return db_many(
			"SELECT obj_type, obj_id, COUNT(*) FROM activity JOIN log_to_object ON (log_to_object.log_id = activity.id) " .
				"WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(whenit) <= " . ($days * 24 * 60 * 60) .
				" GROUP BY obj_type, obj_id ORDER BY 3 DESC");
	}
	
	public static function log($name, $user, $project, $kv) {
		$template = db_one("SELECT * FROM activity_template WHERE name = '" . $name . "'");
		if ($template) {
			$user_id = "NULL";
			if ($user) {
				$user_id = "'" . $user->getID() . "'";
			}
			
			$project_id = "NULL";
			if ($project) {
				$project_id = "'" . $project->getID() . "'";
			}
			
			db_do("INSERT INTO activity(template, user, project, whenit) VALUES('" . $template['id'] . "', $user_id, $project_id, NOW())");
			$activity_id = mysql_insert_id();
			
			$keys = array_keys($kv);
			for ($i = 0; $i < count($keys); $i++) {
				db_do("INSERT INTO activity_key_store(activity, `key`, value) VALUES($activity_id, '" . $keys[$i] . "', '" . $kv[$keys[$i]] . "')");
			}
			
			if ($user)
				db_do("INSERT INTO log_to_object(log_id, obj_type, obj_id) VALUES($activity_id, 1, $user_id)");

			if ($project)
				db_do("INSERT INTO log_to_object(log_id, obj_type, obj_id) VALUES($activity_id, 2, $project_id)");
			
			// TODO: This should be responsible for figuring out all the permissions for emails, etc.
			// TODO: The template has the formats for the emails, just need to work in the which-user logic here.
		}
	}
}

?>