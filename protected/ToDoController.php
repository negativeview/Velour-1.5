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
		$todo_id = array_shift($args);
		$todo = TodoObject::getById($todo_id);

		$this->_smarty->display('header.tpl');
		$this->_smarty->assign('todo', $todo);
		$this->_smarty->display('todo-body.tpl');
		$this->_smarty->display('footer.tpl');
	}
}