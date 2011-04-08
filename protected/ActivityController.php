<?

require_once('ARGTechController.php');
require_once('classes/ActivityObject.php');

class Activity_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'Activity', 'id' => 3);
	}
	
	public function displayList()
	{
		$this->_smarty->display('activity-list.tpl');
	}
}