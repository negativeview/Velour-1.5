<?php

require_once '/opt/local/apache2/protected/classes/PermissionObject.php';

class PermissionObjectTest extends PHPUnit_Framework_TestCase
{
	private $_permissionObject;
	
	public function setUp()
	{
		$this->_permissionObject = new PermissionObject();
		BaseObject::destroyCache();
	}
	
	public function testBasicSubscribe()
	{
		$bo = BaseObject::getById(1);
		$this->assertEquals(1, $this->_permissionObject->getSubscriptionCount());
	}
	
	public function testAllPermissions()
	{
		// This is one ID per object type. Make sure that they all work somehow.
		
		$obj_ids = array(
			1,
			29,
			42,
			75,
			374,
			395,
			556
		);
		
		foreach ($obj_ids as $id) {
			$ob = BaseObject::getById($id);
			$ob->canSee();
		}
	}
	
	public function testUnsubscribe()
	{
		$bo = BaseObject::getById(1);
		$this->assertEquals(1, $this->_permissionObject->getSubscriptionCount());
		
		// This triggers the database pull, which should cause our permission object to run,
		// and to unsubscribe.
		$bo->getType();
		$this->assertEquals(0, $this->_permissionObject->getSubscriptionCount());
		$bo->canSee();
	}
	
	public function tearDown()
	{
	}
}

?>