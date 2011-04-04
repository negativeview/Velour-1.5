<?php

require_once('ARGTechController.php');

class Default_Controller extends ARGTech_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Find most bragworthy, public information.
	 *
	 * This page finds the most brag-worthy activity and describes it in detail, automatically.
	 * For simplicities sake, this must always be something public. Even if you're logged in,
	 * this page will not show your super-awesome-but-private stuff. Brag-worthy is currently
	 * defined by amount of activity in the last 30 days, averaged over the number of days
	 * represented, with a minimum of five days.
	 */
	public function defaultAction($args)
	{
		require_once('classes/ActivityLog.php');
		$this->_smarty->display('header.tpl');
		
		$braggables = ActivityLog::activityByObject(30);
		$count = count($braggables);
		
		$public_braggables = array();
		require_once('classes/BaseObject.php');
		for ($i = 0; $i < $count; $i++) {
			if (!$braggables[$i]['obj_id'])
				continue;
			$bo = BaseObject::getByTypeAndId($braggables[$i]['obj_type'], $braggables[$i]['obj_id']);
			if ($bo->isPublic())
				$public_braggables[] = $bo;
		}
		
		$this->_smarty->assign('braggables', $public_braggables);
		$this->_smarty->display('braggables-home.tpl');
		$this->_smarty->display('footer.tpl');
		
		printf("%0.2fMB", memory_get_peak_usage() / (1024 * 1024));
	}
}