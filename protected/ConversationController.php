<?

require_once('ARGTechController.php');

class Conversation_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_ownedType = array('name' => 'Conversation', 'id' => 8);
	}
	
	public function details($args)
	{
		$conversation_id = array_shift($args);
		
		require_once('classes/ConversationObject.php');
		$conversation = ConversationObject::getById($conversation_id);
		
		$this->_smarty->display('header.tpl');
		$this->_smarty->assign('conversation', $conversation);
		$this->_smarty->display('conversation-full.tpl');
		$this->_smarty->display('footer.tpl');
	}
}