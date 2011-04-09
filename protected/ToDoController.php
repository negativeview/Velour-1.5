<?

require_once('ARGTechController.php');
require_once('classes/ToDoObject.php');

class Todo_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'ToDos', 'id' => 4);
	}

	public function details($args)
	{
		if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'])) {
			$todo_id = $args[0];
			$todo = TodoObject::getById($todo_id);

			switch($_POST['action']) {
				case 'generic-post':
					switch($_POST['id']) {
						case 'add-comment':
							if ($todo->isOwned())
								$todo->addComment($_POST['value']);
					}
			}
		}
		parent::details($args);
	}
	
}