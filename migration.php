<?php

require_once('htdocs/database.php');

$query_count = 0;
function db_do($q)
{
	global $query_count;
	$query_count++;
	echo $q . "\n";
	$res = mysql_query($q);
	if (mysql_error())
		die(mysql_error());
	return $res;
}

/* Get rid of stupid tables. */
db_do("DROP TABLE a_award");
db_do("DROP TABLE a_award_user");
db_do("DROP TABLE a_user");
db_do("DROP TABLE a_user_client");
db_do("DROP TABLE a_user_email");
db_do("DROP TABLE api_tool");
db_do("DROP TABLE api_tool_key");
db_do("DROP TABLE file_notes");
db_do("DROP TABLE post_alerts");
db_do("DROP TABLE randcounts");
db_do("DROP TABLE searches");

db_do("CREATE TABLE obj_allowed_mappings(id serial, left_type BIGINT(20) UNSIGNED NOT NULL, right_type BIGINT(20) UNSIGNED NOT NULL)");

/* Create the object type table, very important for a few reasons. */
db_do("CREATE TABLE obj_types(id SERIAL, slug VARCHAR(255), t VARCHAR(255), menu_title VARCHAR(255), privacy_setting ENUM('complex', 'public', 'project', 'parent'))");
db_do("ALTER TABLE obj_types ADD UNIQUE(slug)");
db_do("ALTER TABLE obj_types ADD UNIQUE(t)");
db_do("ALTER TABLE obj_types ADD UNIQUE(menu_title)");

/* Insert the user type into it. Our first type! */
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('user', 'user', 'Users', 'complex')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('project', 'project', 'Projects', 'complex')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('blog', 'blog', 'Blog', 'public')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('todo', 'todo', 'To Dos', 'project')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('character', 'character', 'Characters', 'project')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('comment', 'comment', 'Comments', 'parent')");
db_do("INSERT INTO obj_types(slug, t, menu_title, privacy_setting) VALUES('file', 'file', 'Files', 'parent')");

/* This stores any varchar values for the object. */
db_do("CREATE TABLE obj_string(id SERIAL, value VARCHAR(255))");

/* This stores any text values for the object. */
db_do("CREATE TABLE obj_text(id SERIAL, value TEXT)");

/* The base object table is the master table for all objects. This table will get big. */
db_do("CREATE TABLE base_object(id serial, creator BIGINT(20) UNSIGNED, parent BIGINT(20) UNSIGNED, project BIGINT(20) UNSIGNED, title BIGINT(20) UNSIGNED, created DATETIME, description BIGINT(20) UNSIGNED, buzz DECIMAL(5, 3) UNSIGNED NOT NULL DEFAULT 0.0, buzz_date DATETIME)");
db_do("ALTER TABLE base_object ADD CONSTRAINT base_object_title_fk FOREIGN KEY (title) REFERENCES obj_string(id)");
db_do("ALTER TABLE base_object ADD CONSTRAINT base_object_description_fk FOREIGN KEY (description) REFERENCES obj_text(id)");
db_do("ALTER TABLE base_object ADD CONSTRAINT base_object_creator_fk FOREIGN KEY (creator) REFERENCES obj_static(id)");
db_do("ALTER TABLE base_object ADD CONSTRAINT base_object_project_fk FOREIGN KEY (project) REFERENCES obj_static(id)");
db_do("ALTER TABLE base_object ADD CONSTRAINT base_object_parent_fk FOREIGN KEY (parent) REFERENCES obj_static(id)");

db_do("CREATE TABLE obj_static(id serial, type BIGINT(20) UNSIGNED NOT NULL, current BIGINT(20) UNSIGNED NOT NULL, views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0)");
db_do("ALTER TABLE obj_static ADD CONSTRAINT obj_static_current_fk FOREIGN KEY (current) REFERENCES base_obj(id)");
db_do("ALTER TABLE obj_static ADD CONSTRAINT obj_static_obj_type_fk FOREIGN KEY(type) REFERENCES obj_types(id)");


$user_old_to_new = array();

$res = db_do("SELECT * FROM users");
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['display_name']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['bio']) . "')");
	$bio_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(title, created, description, buzz, buzz_date) VALUES($title_id, '" . $row['signup'] . "', $bio_id, '" . $row['buzz'] . "', '" . $row['buzz_date'] . "')");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current) VALUES(1, $ver_id)");
	$user_old_to_new['a' . $row['id']] = mysql_insert_id();
}

/* Remind ourselves that the display_name has been taken care of. */
db_do("ALTER TABLE users DROP display_name, DROP signup, DROP bio, DROP buzz, DROP buzz_date");

$project_old_to_new = array();
$res = db_do("SELECT * FROM project");
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['public_name']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['bio']) . "')");
	$bio_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(creator, title, created, description, buzz, buzz_date) VALUES(" . $user_old_to_new['a' . $row['primary_contact']] . ", $title_id, '" . $row['created'] . "', $bio_id, '" . $row['buzz'] . "', '" . $row['buzz_date'] . "')");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current, views) VALUES(2, $ver_id, '" . $row['profile_views'] . "')");
	$project_old_to_new['a' . $row['id']] = mysql_insert_id();
}
db_do("ALTER TABLE project DROP public_name, DROP private_name, DROP bio, DROP buzz, DROP buzz_date, DROP created, DROP profile_views, DROP primary_contact");

$blog_old_to_new = array();
$res = db_do("SELECT * FROM post");
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['title']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['body']) . "')");
	$body_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(creator, title, created, description) VALUES(" . $user_old_to_new['a' . $row['user']] . ", $title_id, '" . $row['whenit'] . "', $body_id)");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current) VALUES(3, $ver_id)");
	$blog_old_to_new['a' . $row['id']] = mysql_insert_id();
}
db_do("DROP TABLE post");

$res = db_do("SELECT * FROM todo");
$todo_old_to_new = array();
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['title']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['body']) . "')");
	$body_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(creator, parent, title, created, description, project) VALUES(" . $user_old_to_new['a' . $row['creator_id']] . ", " . $project_old_to_new['a' . $row['project_id']] . ", $title_id, '" . $row['created'] . "', $body_id, '" . $project_old_to_new['a' . $row['project_id']] . "')");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current) VALUES(4, $ver_id)");
	$todo_old_to_new['a' . $row['id']] = mysql_insert_id();
}
db_do("ALTER TABLE todo DROP title, DROP body, DROP created, DROP creator_id, DROP project_id");

$res = db_do("SELECT * FROM game_character");
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['name']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['body']) . "')");
	$body_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(creator, parent, title, created, description) VALUES(" . $user_old_to_new['a' . $row['creator']] . ", " . $project_old_to_new['a' . $row['project_id']] . ", $title_id, '" . $row['created'] . "', $body_id)");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current) VALUES(5, $ver_id)");
}
db_do("DROP TABLE game_character");

$res = db_do("SELECT * FROM comment");
while ($row = mysql_fetch_assoc($res)) {
	if (!$row['verified'])
		continue;
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['name']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['body']) . "')");
	$body_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(parent, title, created, description) VALUES(" . $blog_old_to_new['a' . $row['post_id']] . ", $title_id, '" . $row['whenit'] . "', $body_id)");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current) VALUES(6, $ver_id)");
}
db_do("ALTER TABLE comment DROP name, DROP body, DROP whenit, DROP post_id");

$res = db_do("SELECT * FROM conversation ORDER BY id");
$conversation_old_to_new = array();
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['title']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['body']) . "')");
	$body_id = mysql_insert_id();
	
	$parent = NULL;
	$do_insert = true;
	switch ($row['parent_type']) {
		case 0:
			$parent = $project_old_to_new['a' . $row['project_id']];
			break;
		case 1:
			// This is really a comment. I was stupid.
			$parent = $conversation_old_to_new['a' . $row['parent_id']];
			db_do("INSERT INTO base_object(parent, title, created, description) VALUES($parent, $title_id, '" . $row['posted'] . "', $body_id)");
			$ver_id = mysql_insert_id();
			
			db_do("INSERT INTO obj_static(type, current) VALUES(6, $ver_id)");
			$do_insert = false;
			break;
		case 3:
			$parent = $todo_old_to_new['a' . $row['parent_id']];
			break;
		case 4:
			// These are orphaned anyway. Sadface.
			$do_insert = false;
			break;
		default:
			die('Unknown parent type: ' . $row['parent_type'] . "\n" . print_r($row, 1) . "\n");
	}
	
	if ($do_insert) {
		db_do("INSERT INTO base_object(creator, project, parent, title, created, description) VALUES(" . $user_old_to_new['a' . $row['user_id']] . ", " . $project_old_to_new['a' . $row['project_id']] . ", $parent, $title_id, '" . $row['posted'] . "', $body_id)");
		$ver_id = mysql_insert_id();
		
		db_do("INSERT INTO obj_static(type, current) VALUES(6, $ver_id)");
		$conversation_old_to_new['a' . $row['id']] = mysql_insert_id();
	}
}
db_do("ALTER TABLE conversation DROP title, DROP body, DROP user_id, DROP posted");

$res = db_do("SELECT * FROM file_version");
while ($row = mysql_fetch_assoc($res)) {
	db_do("INSERT INTO obj_string(value) VALUES('" . mysql_real_escape_string($row['shortdesc']) . "')");
	$title_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_text(value) VALUES('" . mysql_real_escape_string($row['note']) . "')");
	$body_id = mysql_insert_id();
	
	db_do("INSERT INTO base_object(creator, title, created, description) VALUES(" . $user_old_to_new['a' . $row['creator_id']] . ", $title_id, '" . $row['created'] . "', $body_id)");
	$ver_id = mysql_insert_id();
	
	db_do("INSERT INTO obj_static(type, current, views) VALUES(7, $ver_id, '" . $row['dl_count'] . "')");
}
db_do("ALTER TABLE file_version DROP shortdesc, DROP note, DROP creator_id, DROP created, DROP dl_count");

echo $query_count . " queries\n";