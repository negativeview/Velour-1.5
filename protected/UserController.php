<?

require_once('ARGTechController.php');
require_once('classes/ActivityLog.php');
require_once('classes/UserObject.php');

class User_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'Users', 'id' => 1);
	}
	
	public function iconpngAction($args)
	{
		global $site_root;
		
		header("Content-Type: image/png");
		if (file_exists($site_root . 'protected/argtech_users/' . $args[0])) {
			readfile($site_root . 'protected/argtech_users/' . $args[0]);
		} else {
			readfile($site_root . 'protected/argtech_users/0');
		}
	}
	
	public function details($args)
	{
		$user_id = array_shift($args);
		$user = UserObject::getById($user_id);
		$this->_smarty->assign('user', $user);
		$this->_smarty->display('header.tpl');
		$this->_smarty->display('user-bio.tpl');
		$this->_smarty->display('footer.tpl');
	}
}