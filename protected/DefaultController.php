<?php

require_once('ARGTechController.php');
require_once('classes/PermissionObject.php');

class DefaultController extends ARGTechController
{
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
		$res = db_many("SELECT id FROM base_object WHERE id IN (SELECT current FROM obj_static) ORDER BY buzz DESC");
		$this->_smarty->display('header.tpl');
		
		$public_braggables = array();
		require_once('classes/BaseObject.php');
		foreach($res as $row) {
			$tmp = new PermissionObject($row['id']);
			
			if ($tmp->isPublic())
				$public_braggables[] = $tmp;
		}

		$this->_smarty->assign('braggables', $public_braggables);
		$this->_smarty->display('braggables-home.tpl');
		$this->_smarty->display('footer.tpl');
		
		printf("%0.2fMB", memory_get_peak_usage() / (1024 * 1024));
	}
}