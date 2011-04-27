<?

error_reporting(-1);
date_default_timezone_set('GMT');
$site_root = realpath(__DIR__ . '/../') . '/';

$old_path = get_include_path();
set_include_path($site_root . 'protected/:' . $old_path);

session_set_cookie_params(0, '/', '.argtechnologist.com');
session_start();

$mtime = microtime(); 
$mtime = explode(" ", $mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime;

$catch_phrases = Array(
	/* Dr. Horrible. */
	'Wow, sarcasm! That\'s <span class="hilight">original</span>!',
	'The status is not <span class="hilight">quo</span>.',
	'It smells like <span class="hilight">cumin</span>.',
	'Underthings <span class="hilight">tumbling</span>.',
	'Just a few weeks away from a real, <span class="hilight">audible</span> connection.',
	'Buy rocket packs and go to the <span class="hilight">moon</span> and become <span class="hilight">florists</span>.',
	'Seems destiny ends with <span class="hilight">me</span> saving <span class="hilight">you</span>.',
	'You don\'t happen to like frozen yogurt, <span class="hilight">do you</span>?',
	'What a crazy random <span class="hilight">happenstance</span>.',
	'Trust your <span class="hilight">instincts</span>.',
	'Is that the new <span class="hilight">catchphrase</span>?',
	'Even in the darkness every <span class="hilight">color</span> can be found.',
	'We\'re meeting now for the <span class="hilight">first</span> time.',
	'We do the <span class="hilight">weird</span> stuff.',
	'Your real home is in your <span class="hilight">chest</span>.',
	'Someone <span class="hilight">maternal</span>!',
	
	/* Percy. */
	'Percy is not self-aware. <span class="hilight">Yet</span>.',
	'Percy always knows where his <span class="hilight">towel</span> is.',
	
	/* Minecraft. */
	'This site is a <span class="hilight">creeper</span> free zone. You are welcome.',
	
	/* Bragging. */
	'Yeah, I can do <span class="hilight">that</span>.',
	'It\'s kinda <span class="hilight">fun</span> to do the <span class="hilight">impossible</span>.',
	'My work is a <span class="hilight">game</span> -- a very <span class="hilight">serious</span> game.',
	'Attempt the <span class="hilight">impossible</span> in order to <span class="hilight">improve</span> your work.',
	'Sometimes you gotta <span class="hilight">create</span> what you want to be a part of.',
	'If you\'re <span class="hilight">strong</span> enough, there are no precedents.',
	'Policy is a <span class="hilight">guide</span> to the wise and a <span class="hilight">rule</span> to the fool.',
	'Sleep is just a <span class="hilight">bad</span> habit.',

	/* Meta. */
	'Technology <span class="hilight">storyboard.</span>',
	'Supplying your <span class="hilight">fix</span>... of technology.',
	'<span class="hilight">Hooked</span> on ARGs!',
	'Made with <span class="hilight">pixels</span>.',
	'These are <span class="hilight">random</span>. <span class="hilight">Nobody</span> notices at first.',
	'Let\'s have some <span class="hilight">new</span> clich&eacute;s.',

	'Metadata is <span class="hilight">always</span> true!',
	'We never really <span class="hilight">grow up</span>, we only learn how to act in <span class="hilight">public</span>.',
	'When in doubt, <span class="hilight">mumble</span>.',
	'I intend to live <span class="hilight">forever</span>. So far, <span class="hilight">so good</span>.',
	'With sufficient <span class="hilight">thrust</span>, pigs fly <span class="hilight">just fine</span>.',
	'<span class="hilight">Trouble</span> is only an <span class="hilight">opportunity</span> in work clothes.',
	'<span class="hilight">Efficiency</span> is <span class="hilight">intelligent</span> laziness.',
	'Everything should be made as simple as <span class="hilight">possible</span>, but not <span class="hilight">simpler</span>.',
	'Well <span class="hilight">done</span> is better than well <span class="hilight">said</span>.',
	'The best way to <span class="hilight">predict</span> the future is to <span class="hilight">invent</span> it.',
	'<span class="hilight">Consistency</span> is the final refuge of the <span class="hilight">unimaginative</span>.',
	'Work is only <span class="hilight">work</span> if you\'d rather be doing <span class="hilight">something else</span>.',
	'If <span class="hilight">life</span> does not offer a game worth playing, invent a <span class="hilight">new</span> one.',
	'The <span class="hilight">truth</span> is out there.',
	'<span class="hilight">Research</span> is what I\'m doing when I <span class="hilight">don\'t know</span> what I\'m doing.',
	'<span class="hilight">Imagination</span> is more important than <span class="hilight">knowledge</span>.',
	'Logic is a system whereby one may go <span class="hilight">wrong</span> with confidence.',
	'A ship doesn\'t travel far in a <span class="hilight">calm</span> sea.',
	'Opportunities multiply as they are <span class="hilight">seized</span>.',
	'What a <span class="hilight">strange</span> game. The <span class="hilight">only</span> winning move is not to play.',
	'Don\'t <span class="hilight">fear</span> change -- <span class="hilight">embrace</span> it.',
	'An <span class="hilight">error</span> is not a <span class="hilight">mistake</span> until you refuse to correct it.',
	'Don\'t let <span class="hilight">fear</span> stop you.',
	'No <span class="hilight">pressure</span>, no <span class="hilight">diamonds</span>.',
	'Kites rise highest <span class="hilight">against</span> the wind, not with it.',
	'You don\'t drown by <span class="hilight">falling</span> in the water, you drown by <span class="hilight">staying</span> there.',
	'Success is the sum of <span class="hilight">small</span> efforts, <span class="hilight">repeated</span> day in and day out.',
	'He who hesitates is probably <span class="hilight">smart</span>... or <span class="hilight">stapled</span> to the floor.',
	'Hitch your wagon to a <span class="hilight">star</span>.',
	'Nothing <span class="hilight">great</span> was ever achieved without <span class="hilight">enthusiasm</span>.',
	'<span class="hilight">Failure</span> is the opportunity to <span class="hilight">begin again</span> more intelligently.',
	'You can\'t cross a chasm in two <span class="hilight">small</span> jumps.',
	'The <span class="hilight">harder</span> you fall, the <span class="hilight">higher</span> you bounce.',
	'Life is a great big <span class="hilight">canvas</span>. Throw all the <span class="hilight">paint</span> you can at it.',
	'Pick battles big enough to <span class="hilight">matter</span>, but small enough to <span class="hilight">win</span>.',
	'All the world\'s a <span class="hilight">stage</span> and most of us are desperately <span class="hilight">unrehearsed</span>.',
	'Why not go out on a <span class="hilight">limb</span>? That\'s where all the <span class="hilight">fruit</span> is!',
	'Anyone can hold the helm when the seas are <span class="hilight">calm</span>.',
	'After all is said and done, a lot more is usually <span class="hilight">said</span> than <span class="hilight">done</span>.',
	'Inside every <span class="hilight">large</span> problem is a <span class="hilight">small</span> problem struggling to get out.',
	'Watch for big <span class="hilight">problems</span>; they disguise big <span class="hilight">opportunities</span>.',
	'If all the world is a <span class="hilight">stage</span>, where is the <span class="hilight">audience</span> sitting?',
	'It\'s better than <span class="hilight">bad</span>, it\'s <span class="hilight">good</span>.'
);
$catch_phrases[] = 'There are <span class="hilight">' . (count($catch_phrases) + 1) . '</span> random phrases.';

$phrase = $catch_phrases[array_rand($catch_phrases)];	

/*
switch(date("md")) {
	case "0101":
		$smarty->assign('phrase', date("Y") . ' edition!');
		break;
	case "0201":
		$smarty->assign('phrase', 'Happy Velour launch day!');
		break;
	case "0204":
		$smarty->assign('phrase', 'Happy Lockjaw day!');
		break;
	case "0225":
		$smarty->assign('phrase', 'Happy birthday, Ancalime!');
		break;
	case "0229":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Daniel</span>!');
		break;
	case "0806":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Percy</span>!');
		break;
	case "0901":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Julia</span>!');
		break;
	case "1001":
		$smarty->assign('phrase', 'Happy Metacortechs Day!');
		break;
	case "1105":
		$smarty->assign('phrase', 'Remember, remember the fifth of November.');
		break;
	case "1108":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Celina</span>.');
		break;
	case "1114":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Lairosiel</span>!');
		break;
	case "1116":
		$smarty->assign('phrase', 'Happy birthday, <span class="hilight">Jane</span>!');
		break;
	case "1120":
		$smarty->assign('phrase', 'Hey, <span class="hilight">you</span>! Remind <span class="hilight">Daniel</span> that it is his anniversary.');
		break;
}
*/

function email_errors($errno, $errstr, $errfile, $errline) {
	global $user;
	
	if (!isset($user) || !$user->superUser()) {
		$headers = "From: ARGTech Error <admin@argtechnologist.com>";
		$msg = "Errorno: $errno\nErrstr: $errstr\nErrfile: $errfile\nErrline: " . print_r($errline, 1);
		mail("dgrace@doomstick.com", "ARGTech Error", $msg, $headers);
	}
	return false;
}

function force_login($id) {
	$_SESSION['user'] = $id;
	$user = User::getUser($id);
	$_SESSION['user_level'] = $user->getTheme();
	
	ActivityLog::log('login', $user, false, Array());
	db_do("INSERT INTO activity_log(user_id, action, whenit) VALUES('" . $user->getID() . "', 'login', NOW())");
	
	return $user;
}

require_once('database.php');

if (isset($_SESSION['user'])) {
	require_once('classes/UserObject.php');
	$user = UserObject::getById($_SESSION['user']);
}

$_num_db_queries = 0;
$_queries = Array();

function raw_query($q) {
	global $_num_db_queries;
	$_num_db_queries++;
	
	global $_queries;
	
	$start = microtime(true);
	$res = mysql_query($q);
	if (mysql_error()) {
		echo '<pre>';
		print_r(debug_backtrace());
		echo '</pre>';
		die(mysql_error() . ': ' . $q);
	}
	$end = microtime(true);
	
	$_queries[] = Array(
		'query' => $q,
		'time' => $end - $start
	);
	
	return $res;
}

function db_many($query) {
	$res = raw_query($query);
	$ret = Array();
	while ($row = mysql_fetch_assoc($res))
		$ret[] = $row;
	return $ret;
}

function db_one($query) {
	$res = raw_query($query);
	$ret = mysql_fetch_assoc($res);
	return $ret;
}

function db_do($query) {
	raw_query($query);
}

function check_login($un, $pass) {
	return db_one("SELECT * FROM users WHERE email = '" . mysql_real_escape_string($un) . "' AND passhash = MD5(CONCAT('" .
		mysql_real_escape_string($pass) . "', COALESCE(salt, 'argtech')))");
}

function setup_theme($theme) {
	global $site_root;
	
	$old_path = get_include_path();
	
	set_include_path(
		$site_root . 'protected/' . $theme . '/' .
		PATH_SEPARATOR . $site_root . 'protected/' . $theme . '/classes/' .
		PATH_SEPARATOR . $old_path
	);
}

?>