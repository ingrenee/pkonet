<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AccessController extends MyController {

	var $uses = array('acl');

	var $helpers = array('admin/admin_settings','html','form');

	var $components = array('access','config');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
	{
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
		$this->name = 'access';

		$accessGroups = $this->Acl->getAccessGroupList();

		$accessLevels = $this->Acl->getAccessLevelList();

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'accessGroups'=>$accessGroups,
				'accessLevels'=>$accessLevels

			)
		);

        return $this->render();
	}

	function _save()
	{
		// If all groups are de-selected then the setting needs to be set to empty here because it' not in the posted form data

		$settings = array_keys($this->data['Access']);

		$access_settings = $this->Access->__settings;

		foreach($access_settings AS $setting) {
			if(!in_array($setting, $settings)) {
				$this->data['Access'][$setting] = 'none';
			}
		}

		$this->Config->store($this->data['Access'],false /*store as comma list*/);
	}

}