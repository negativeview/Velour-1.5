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
}