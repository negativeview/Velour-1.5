<?

require_once('ARGTechController.php');

class Project_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'Projects', 'id' => 2);
	}
	
	public function iconpngAction($args)
	{
		global $site_root;
		
		header("Content-Type: image/png");
		if (file_exists($site_root . 'protected/argtech_projects/' . $args[0])) {
			readfile($site_root . 'protected/argtech_projects/' . $args[0]);
		} else {
			readfile($site_root . 'protected/argtech_projects/0');
		}
	}
	
	public function details($args)
	{
		require_once('classes/ProjectObject.php');
		
		$project = ProjectObject::getByid($args[0]);

		if (!$project->isPublic()) {
			$this->_smarty->display('header.tpl');
			echo 'Error: You do not have permission to view this object.';
			$this->_smarty->display('footer.tpl');
			return;
		}
		
		parent::details($args);

		$objects = array();
		
		global $user;
		if ($user)
			$objects[] = $user;
		
		$objects[] = $project;
		require_once('classes/ActivityLog.php');
		ActivityLog::log('project', 'view', array(), $objects);
	}
}