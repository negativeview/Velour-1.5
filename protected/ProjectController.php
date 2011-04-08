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
		
		$this->_smarty->display('header.tpl');
		$project = ProjectObject::getByid($args[0]);

		if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'])) {
			switch($_POST['action']) {
				case 'generic-post':
					if ($_POST['id'] == 'project-bio' && $project->isOwned()) {
						$project->updateBio($_POST['value']);
					}
					break;
			}
			exit();
		}

		if (!$project->isPublic()) {
			echo 'Error: You do not have permission to view this object.';
			$this->_smarty->display('footer.tpl');
			return;
		}
		$this->_smarty->assign('project', $project);
		$this->_smarty->display('project-front-page.tpl');
		$this->_smarty->display('footer.tpl');
		
		$objects = array();
		
		global $user;
		if ($user)
			$objects[] = $user;
		
		$objects[] = $project;
		require_once('classes/ActivityLog.php');
		ActivityLog::log('project', 'view', array(), $objects);
	}
}