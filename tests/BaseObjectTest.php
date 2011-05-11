<?php

require_once '/opt/local/apache2/protected/classes/BaseObject.php';

/**
 * Test class for BaseObject.
 * Generated by PHPUnit on 2011-05-03 at 19:34:38.
 */
class BaseObjectTest extends PHPUnit_Framework_TestCase
{
	private $_subscriber;
	private $_incCount;
	private $_lastData;
	private $_latsOb;
	
	static private $_staticIncCount;
	
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    	$this->_subscriber = array($this, 'countingCallback');
    	$this->_incCount = 0;
    	$this->_lastData = null;
    	$this->_lastOb = null;
    	
    	BaseObject::destroyCache();
    	
    	self::$_staticIncCount = 0;
    }
    
    public function testAddFunction()
    {
    	$ob = BaseObject::getById(1);
    	$ob->addFunction('foo', $this->_subscriber);
    	$ob->foo();
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEmpty($this->_lastData);
    	$this->assertEquals($ob, $this->_lastOb);
    }
    
    public function countingCallback($ob = null, $data = null)
    {
    	$this->_incCount++;
    	$this->_lastData = $data;
    	$this->_lastOb = $ob;
    }

	/**
	 * Makes sure that things are sane.
	 */
    public function testSubscriberEmpty()
    {
    	$bo = BaseObject::getById(1);
    	$this->assertEquals(0, count($bo->getSubscriberList()));
    }
    
    public function testAddSubscriberNormally()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(1, count($subscribers['example']));
    }
    
    public function testUnsubscribe()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(1, count($subscribers['example']));
    	
    	$bo->unsubscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(0, count($subscribers['example']));    	
    }
    
    public function testAddSubscriberOnlyOnce()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->subscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(1, count($subscribers['example']));
    }
    
    public function testCallbackWorks()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->dispatch('example');
    	$this->assertEquals(1, $this->_incCount);
    }
    
    public function testCallbackPassesData()
    {
    	$data = array(
    		'this'         => 'data',
    		'is'           => 'very',
    		'unlikely'     => 'to',
    		'accidentally' => 'be',
    		'duplicated'   => 'accidentally'
    	);
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber);
    	$bo->dispatch('example', $data);
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($data, $this->_lastData);
    }
    
    public function testCallbackPassesObject()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber);
    	$bo->dispatch('example');
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($bo, $this->_lastOb);
    }
    
    public function testCallbackOnlyOnce()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->dispatch('example');
    	$this->assertEquals(1, $this->_incCount);
    }
    
    public function testStaticCallback()
    {
    	$bo = BaseObject::getById(1);
    	$bo->subscribe('example', array('BaseObjectTest', 'staticCallback'), array());
    	$bo->dispatch('example');
    	$this->assertEquals(1, self::$_staticIncCount);
    }
    
    public static function staticCallback()
    {
    	self::$_staticIncCount++;
    }
    
    public function testCreateCallback()
    {
    	BaseObject::staticSubscribe('create', $this->_subscriber, array());
    	$bo = BaseObject::getById(1);
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($bo, $this->_lastOb);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
}
?>