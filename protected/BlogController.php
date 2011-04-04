<?

require_once('ARGTechController.php');

class Blog_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'Blog', 'id' => 12);
	}
	
	public function details($args)
	{
		$blog_id = array_shift($args);
		
		require_once('classes/BlogObject.php');
		$blog = BlogObject::getById($blog_id);
		
		$this->_smarty->display('header.tpl');
		$this->_smarty->assign('blog', $blog);
		$this->_smarty->display('blog-entry.tpl');
		$this->_smarty->display('footer.tpl');
	}
}