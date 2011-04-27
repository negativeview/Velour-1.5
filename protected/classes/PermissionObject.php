<?php

require_once('BaseObject.php');

/**
 * Base object for all ARGTech top-level-objects.
 *
 * All top-level objects extend from this class. This makes sure that we have some core
 * functionality present on all of the objects. So far these include the ability to be
 * constructed via a type/id pair, becoming a front-page featured object, having
 * comments able to be put on the object, being able to favorite an object, etc.
 */

class PermissionObject extends BaseObject
{
	/**
	 * Constructor
	 *
	 * @param integer $id The id of this row in the obj_static table.
	 * @return BaseObject
	 */
	public function __construct($id)
	{
		parent::__construct($id);
	}
	
	public function isPublic()
	{
		$raw = $this->_fetchRaw();
		switch ($raw['privacy_setting']) {
			case 'complex':
				return false;
			case 'parent':
				$parent = $this->getParent();
				if (!$parent)
					return false;
				return $parent->isPublic();
			case 'project':
				$project = $this->getProject();
				if (!$project)
					return false;
				return $project->isPublic();
			case 'public':
				return true;
			default:
				die('Unknown privacy setting: ' . $raw['privacy_setting']);
		}
	}
	
	public function getParent()
	{
		$raw = $this->_fetchRaw();

		if (!$raw['parent'])
			return null;
		
		return new PermissionObject($raw['parent']);
	}
	
	public function getProject()
	{
		$raw = $this->_fetchRaw();
		
		if (!$raw['project'])
			return null;
		
		return new PermissionObject($raw['project']);
	}
	
	public function toURL()
	{
		$raw = $this->_fetchRaw();
		return '/' . $raw['slug'] . '/' . $raw['id'] . '/';
	}
	
	public function toLink()
	{
		return '<a href="' . $this->toURL() . '">' . $this->getName() . '</a>';
	}
	
	public function getTypeName()
	{
		$raw = $this->_fetchRaw();
		return $raw['menu_title'];
	}
	
	public function getName()
	{
		$raw = $this->_fetchRaw();
		return $raw['title'];
	}
	
	public function getImage()
	{
		return '';
	}
	
	public function getCreated()
	{
		$raw = $this->_fetchRaw();
		return $raw['created'];
	}
	
	public function getOwner()
	{
		$raw = $this->_fetchRaw();
		$creator = $raw['creator'];
		if (!$creator)
			return null;
		
		return new PermissionObject($creator);
	}
	
	public function getBraggable()
	{
		$raw = $this->_fetchRaw();
		return $raw['description'];
	}
}