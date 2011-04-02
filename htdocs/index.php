<?

require_once('core.php');

$request = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
$pieces = explode('/', $request);
for ($i = 0; $i < count($pieces); $i++) {
	if (trim($pieces[$i]) == '') {
		array_splice($pieces, $i, 1);
		$i--;
	}
}

switch(count($pieces)) {
	// we're looking for the home page, or default controller action default
	case 0:
		$controller = 'default';
		$action = 'default';
		$args = array();
		break;
	default:
		$controller = array_shift($pieces);
		$action = 'default';
		$args = array();
		
		if (count($pieces)) {
			if (is_numeric($pieces[0])) {
				$id = array_shift($pieces);
				if (count($pieces))
					$action = array_shift($pieces);
				else
					$action = 'default';
			
				$args = $pieces;
				array_unshift($args, $id);
			} else {
				print_r($_SERVER);
				die();
				$action = $pieces[1];
				for ($i = 2; $i < count($pieces); $i++) {
					$args[] = $pieces[$i];
				}
			}
		}
}

$action = str_replace('.', '', $action);

$controller_name = ucfirst($controller) . '_Controller';

require_once(ucfirst($controller) . 'Controller.php');
$controller = new $controller_name();

$action_name = $action . 'Action';
$controller->$action_name($args);

?>
