<?php

class ARGTech_Controller
{
	protected $_smarty = null;
	protected $_ownedType;
	
	public function __construct()
	{
		global $site_root;
		global $phrase;
		
		require_once('includes/Smarty.class.php');
		$this->_smarty = new Smarty();
		$this->_smarty->assign('this', $this->_smarty);
		$this->_smarty->template_dir = $site_root . 'protected/templates/';
		$this->_smarty->compile_dir = $site_root . 'protected/templates_c/';
		$this->_smarty->assign('phrase', $phrase);
	}
	
	/**
	 * Displays a list of the objects.
	 *
	 * Override this if you can make it better for your object. This basically displays a list
	 * of the objects on the system. It can only really display things that are part of BaseObject,
	 * and cannot make any assumptions about what kind of data it is.
	 *
	 * You can probably do better than this, honestly.
	 */
	
	public function defaultAction($args)
	{
		require_once('classes/BaseObject.php');
		if (!count($args)) {
			$this->_smarty->display('header.tpl');

			$this->_smarty->assign('title', $this->_ownedType['name']);
			$res = BaseObject::getAllByType($this->_ownedType['id']);
			
			$this->_smarty->assign('page_number', isset($_GET['page']) ? (int)$_GET['page'] : 1);
			$this->_smarty->assign('objects', $res);
			$this->_smarty->assign('object_count', count($res));
			$this->_smarty->assign('per_page', 10);
			$this->_smarty->assign('max_pages', ceil(count($res) / 10));
			$this->_smarty->display('object-list.tpl');
		
			$this->_smarty->display('footer.tpl');
		} else {
			$this->details($args);
		}
	}
	
	public function logAction($args)
	{
		$obj_id = array_shift($args);
		
		$this->_smarty->assign('title', $this->_ownedType['name']);
		$res = BaseObject::getByTypeAndId($this->_ownedType['id'], $obj_id);
		$this->_smarty->assign('object', $res);
		$this->_smarty->display('header.tpl');
		$this->_smarty->display('object-log.tpl');
		$this->_smarty->display('footer.tpl');
	}
	
	public function details($args)
	{
		die("Need to override details.");
	}
}