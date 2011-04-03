<?php

require_once('ARGTechController.php');

class Signup_Controller extends ARGTech_Controller
{
	public function defaultAction($args)
	{
		$this->_smarty->display('header.tpl');
		$this->_smarty->display('signup-form-one.tpl');
		$this->_smarty->display('footer.tpl');
	}
	
	public function firstAction($args)
	{
		require_once('classes/UserObject.php');
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
			die("Attempt to submit the signup form via GET. ... What?!");

		$email = $_POST['email'];
		$password = $_POST['pass1'];
		$passconfirm = $_POST['pass2'];
		$submit = $_POST['submit'];
		
		$user = UserObject::tryCreate($email, $password, $passconfirm, $errors);
		if (!$user) {
			$this->_smarty->display('header.tpl');
			$this->_smarty->assign('errors', $errors);
			$this->_smarty->display('signup-form-one.tpl');
			$this->_smarty->display('footer.tpl');
			exit();
		}
		
		require_once('classes/ActivityLog.php');
		ActivityLog::log('signup', $user, null);
		$user->setLoggedIn();
		
		switch($submit) {
			case 'Step Two':
				header("Location: /dashboard/");
				break;
			case 'Skip The Rest':
		}
	}
}