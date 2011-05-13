<?php

/**
 * Handles adding permission information to a base object. Built to be flexible not because
 * this particular bit needs it, but because it's built on a foundation of flexible awesome.
 */

class PermissionObject
{
	private $_subscriptionCount;
	
	public function __construct()
	{
		$this->_subscriptionCount = 0;
		
		BaseObject::staticSubscribe('create', array($this, 'newBaseObject'));
	}
	
	public function newBaseObject($ob)
	{
		$ob->subscribe('data:raw', array($this, 'setupPermissions'));
		$this->_subscriptionCount++;
	}
	
	public function setupPermissions($ob, $data)
	{
		switch ($data['obj_types']['privacy_setting']['value']) {
			case 'complex':
				// Some other class is going to have to handle this,
				// as it's not something that has been deemed common enough
				// to just go in here.
				
				$ob->addFunction('canSee', array($this, 'canSeeComplex'));
				break;
			case 'public':
				$ob->addFunction('canSee', array($this, 'canSeeTrue'));
				break;
			case 'project':
				$ob->addFunction('canSee', array($this, 'canSeeProject'));
				break;
			case 'parent':
				$ob->addFunction('canSee', array($this, 'canSeeParent'));
				break;
			default:
				throw new Exception('Unknown privacy setting: ' . $data['privacy_setting']);
		}
		$ob->unsubscribe('data:raw', array($this, 'setupPermissions'));
		$this->_subscriptionCount--;
	}
	
	public function getSubscriptionCount()
	{
		return $this->_subscriptionCount;
	}
	
	// TODO: Do this.
	public function canSeeComplex($ob, $data)
	{
		return false;
	}
	
	public function canSeeTrue($ob, $data)
	{
		return true;
	}
	
	public function canSeeProject($ob, $data)
	{
		$project = $ob->getProject();
		if (!$project) {
			throw new Exception('Object ' . $ob->toString() . ' has project permissions, but no project.');
		}
		
		return $project->canSee();
	}
	
	public function canSeeParent($ob, $data)
	{
		$parent = $ob->getParent();
		if (!$parent) {
			throw new Exception('Object ' . $ob->toString() . ' has parent permissions, but no parent.');
		}
		
		return $parent->canSee();
	}
}