<?php

require_once('ARGTechController.php');

class Dashboard_Controller extends ARGTech_Controller
{
	public function defaultAction($args)
	{
		global $user;
		
		if (!isset($user)) {
			header("Location: /");
			exit();
		}
		
		$this->_smarty->display('header.tpl');
		$this->_smarty->assign('user', $user);
		$this->_smarty->display('user-dashboard.tpl');
		$this->_smarty->display('footer.tpl');
	}
}