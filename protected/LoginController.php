<?php

require_once('ARGTechController.php');

class Login_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function defaultAction($args)
	{
		$this->_smarty->display('header.tpl');
		$this->_smarty->display('login-form.tpl');
		$this->_smarty->display('footer.tpl');
	}
	
	public function postAction($args)
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
			die("Attempt to submit the login form via GET. ... What?!");
		
		require_once('classes/UserObject.php');
		$user = UserObject::tryLogin($_POST['username'], $_POST['password']);
		if ($user) {
			header("Location: /dashboard/");
		} else {
			$this->_smarty->display('header.tpl');
			$this->_smarty->assign('errors', array('Invalid login.'));
			$this->_smarty->display('login-form.tpl');
			$this->_smarty->display('footer.tpl');			
		}
	}
}