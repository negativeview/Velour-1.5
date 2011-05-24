<?php

require_once('ObjectType.php');
require_once('CoreObject.php');
require_once('DB.php');
require_once('htdocs/database.php');
require_once('htdocs/core.php');

/**
 * Base object for all ARGTech top-level-objects.
 *
 * All top-level objects extend from this class. This makes sure that we have some core
 * functionality present on all of the objects. So far these include the ability to be
 * constructed via a type/id pair, becoming a front-page featured object, having
 * comments able to be put on the object, being able to favorite an object, etc.
 */

class BaseObject
{
	/** @var array */
	protected $_fetched;
	
	/** @var array */
	protected $_fetched_originals;
	
	/** @var boolean */
	protected $_has_updated;
	
	/** @var CoreObject */
	protected $_core_object;

	/**
	 * Constructor
	 *
	 * @param integer $id The id of this row in the obj_static table.
	 * @return BaseObject
	 */
	protected function __construct($coreObject)
	{
		$this->_core_object = $coreObject;
		$this->_fetched = array();
		$this->_fetched_originals = array();
		$this->_has_updated = false;
		$this->_core_object->addExtender($this);
		
		CoreObject::staticDispatch('BaseObject.create', $this);
	}
	
	protected function _fetchWrapper($key, $function)
	{
		if (isset($this->_fetched[$key]))
			return $this->_fetched[$key];
		
		if (!is_callable($function))
			die('_fetchWrapper called with something not callable: ' . print_r($function, true));

		$this->_fetched[$key] = call_user_func($function);
		$this->_fetched_originals[$key] = $this->_fetched[$key];
		
		$this->_core_object->dispatch('data:' . $key, $this->_fetched[$key]);
		return $this->_fetched[$key];
	}
	
	protected function _fetchRaw()
	{
		return $this->_fetchWrapper('raw', array($this, '_realFetchRaw'));
	}
	
	protected function _realFetchRaw()
	{
		$db = DB::getInstance();
		
		$db->addTable('obj_static')
		   ->addColumns('obj_static',
		   		array(
		   			'current',
		   			'id',
		   			'type',
		   			'views',
		   		)
		   	);
		   
		$db->addJoin('obj_types', 'obj_static', 'id', 'type')
		   ->addColumns('obj_types',
		   		array(
		   			'id',
		   			'menu_title',
		   			'privacy_setting',
		   			'slug',
		   		)
		   	);
		   
		$db->addJoin('base_object', 'obj_static', 'id', 'current')
		   ->addColumns('base_object',
		   		array(
		   			'buzz',
		   			'buzz_date',
		   			'created',
		   			'creator',
		   			'description',
		   			'id',
		   			'parent',
		   			'project',
		   			'title',
		   		)
		   	);
		   
		$db->addWhereEquals('obj_static', 'id', $this->_core_object->getId());
		$db->addStringField('base_object', 'title');
		$db->addTextField('base_object', 'description');
		
		return $db->getSingleResult();
	}
	
	public function getRaw()
	{
		return $this->_fetched;
	}
	
	public function getId()
	{
		return $this->_core_object->getId();
	}
	
	public function hasChanged()
	{
		return $this->_has_updated;
	}
	
	public function getBody()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['description']['value'];
	}
	
	public function setBody($body)
	{
		$this->_fetchRaw();
		$this->_fetched['raw']['base_object']['description']['value'] = $body;
		$this->_has_updated = true;
	}
	
	public function getTitle()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['title']['value'];
	}
	
	public function getViews()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_static']['views']['value'];
	}
	
	public function addView()
	{
		$this->_fetchRaw();
		$this->_fetched['raw']['obj_static']['views']['value']++;
		$this->_has_updated = true;
	}
	
	public function setTitle($title)
	{
		$this->_fetchRaw();
		$this->_fetched['raw']['base_object']['title']['value'] = $title;
		$this->_has_updated = true;
	}
	
	public function getDescription()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['description']['value'];
	}
	
	public function getType()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_static']['type']['value'];
	}
	
	public function getSlug()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['slug']['value'];
	}
	
	public function getMenuTitle()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['menu_title']['value'];
	}
	
	public function getPrivacySetting()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['obj_types']['privacy_setting']['value'];
	}
	
	public function getCreator()
	{
		$this->_fetchRaw();
		return $this->_fetched['raw']['base_object']['creator']['value'];
	}
	
	public function getParent()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['base_object']['parent']['value'])
			return BaseObject::getById($this->_fetched['raw']['base_object']['parent']['value']);
		
		return null;
	}
	
	public function setParent($parent)
	{
		$this->_fetchRaw();
		
		if (is_object($parent))
			$parent = $parent->getId();
			
		$this->_fetched['raw']['base_object']['parent']['value'] = $parent;
		$this->_has_updated = true;
	}
	
	public function getProject()
	{
		$this->_fetchRaw();
		if ($this->_fetched['raw']['base_object']['project']['value'])
			return BaseObject::getById($this->_fetched['raw']['base_object']['project']['value']);
		
		return null;
	}
	
	public function getCreated()
	{
		$this->_fetchRaw();
		return new DateTime($this->_fetched['raw']['base_object']['created']['value']);
	}
	
	public function hasBeenUpdated()
	{
		return $this->_has_updated;
	}
	
	public function __destruct()
	{
		$changed_columns = array();
		$string_columns = array();
		
		if ($this->_has_updated) {
			foreach ($this->_fetched['raw'] as $table => $columns) {
				foreach ($columns as $name => $data) {
					if ($data['value'] !== $this->_fetched_originals['raw'][$table][$name]['value']) {
						if ($data['type'] == 'string') {
							$db = DB::getInstance();
							$db->startInsert('obj_string');
							$db->setColumn('value', $data['value']);
							$new_id = $db->endInsert();
							$this->_fetched['raw'][$table]['_' . $name] = array(
								'value' => $new_id,
								'type' => 'raw'
							);
						}

						if ($data['type'] == 'text') {
							$db = DB::getInstance();
							$db->startInsert('obj_text');
							$db->setColumn('value', $data['value']);
							$new_id = $db->endInsert();
							$this->_fetched['raw'][$table]['_' . $name] = array(
								'value' => $new_id,
								'type' => 'raw'
							);
						}

						if (!isset($changed_columns[$table]))
							$changed_columns[$table] = array();
						
						$changed_columns[$table][$name] = $data;
					}
				}
			}
			
			// base object is special. We have to insert it, then update the
			// obj_static. It's crazy sauce. But it's our MAGIC crazy sauce.
			if (isset($changed_columns['base_object'])) {
				$db = DB::getInstance();
				$db->startInsert('base_object');
				$db->setColumn('creator', $this->_fetched['raw']['base_object']['creator']['value']);
				$db->setColumn('parent', $this->_fetched['raw']['base_object']['parent']['value']);
				$db->setColumn('project', $this->_fetched['raw']['base_object']['project']['value']);
				$db->setColumn('title', $this->_fetched['raw']['base_object']['_title']['value']);
				$db->setColumn('description', $this->_fetched['raw']['base_object']['_description']['value']);
				$db->setColumn('buzz', $this->_fetched['raw']['base_object']['buzz']['value']);
				$db->setColumn('buzz_date', $this->_fetched['raw']['base_object']['buzz_date']['value']);
				
				$new_base = $db->endInsert();
				
				if (!isset($changed_columns['obj_static']))
					$changed_columns['obj_static'] = array();
				
				$changed_columns['obj_static']['current'] = array(
					'value' => $new_base,
					'type' => 'raw'
				);
				
				unset($changed_columns['base_object']);
			}
			
			foreach ($changed_columns as $table => $columns) {
				$db = DB::getInstance();

				$db->startUpdate($table);				
				foreach ($columns as $name => $data) {
					$db->setColumn($name, $data['value']);
				}
				$db->endUpdate($this->_core_object->getId());
			}
		}
	}
	
	public static function newCore($coreObject)
	{
		new BaseObject($coreObject);
	}
}

CoreObject::staticSubscribe('CoreObject.create', array('BaseObject', 'newCore'), array());
