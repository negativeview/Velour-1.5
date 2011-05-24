<?php

require_once '/opt/local/apache2/protected/classes/CoreObject.php';

/**
 * Test class for CoreObject.
 */
class CoreObjectTest extends PHPUnit_Framework_TestCase
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
    	
    	CoreObject::destroyCache();
    	
    	self::$_staticIncCount = 0;
    }
    
    public function testAddExtender()
    {
    	$ob = CoreObject::getById(1);
    	$ob->addExtender($this);
    	$ob->countingCallback();
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
    	$bo = CoreObject::getById(1);
    	$this->assertEquals(0, count($bo->getSubscriberList()));
    }
    
    public function testAddSubscriberNormally()
    {
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(1, count($subscribers['example']));
    }
    
    public function testUnsubscribe()
    {
    	$bo = CoreObject::getById(1);
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
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->subscribe('example', $this->_subscriber, array());
    	$subscribers = $bo->getSubscriberList();
    	$this->assertEquals(1, count($subscribers));
    	$this->assertEquals(1, count($subscribers['example']));
    }
    
    public function testCallbackWorks()
    {
    	$bo = CoreObject::getById(1);
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
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber);
    	$bo->dispatch('example', $data);
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($data, $this->_lastData);
    }
    
    public function testCallbackPassesObject()
    {
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber);
    	$bo->dispatch('example');
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($bo, $this->_lastOb);
    }
    
    public function testCallbackOnlyOnce()
    {
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->subscribe('example', $this->_subscriber, array());
    	$bo->dispatch('example');
    	$this->assertEquals(1, $this->_incCount);
    }
    
    public function testStaticCallback()
    {
    	$bo = CoreObject::getById(1);
    	$bo->subscribe('example', array('CoreObjectTest', 'staticCallback'), array());
    	$bo->dispatch('example');
    	$this->assertEquals(1, self::$_staticIncCount);
    }
    
    public static function staticCallback()
    {
    	self::$_staticIncCount++;
    }
    
    public function testCreateCallback()
    {
    	CoreObject::staticSubscribe('CoreObject.create', $this->_subscriber, array());
    	$bo = CoreObject::getById(1);
    	$this->assertEquals(1, $this->_incCount);
    	$this->assertEquals($bo, $this->_lastOb);
    }

    protected function tearDown()
    {
    }
}
?>
