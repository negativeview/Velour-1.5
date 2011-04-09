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
		$user_id = $args[0];
		$user = UserObject::getByid($user_id);
		
		parent::details($args);

		require_once('classes/ActivityLog.php');
		ActivityLog::log('userprofileviewed', array(), array($user));
	}
}