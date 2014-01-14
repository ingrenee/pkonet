<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminControlFieldsController extends MyController {

	var $uses = array('field','field_option');

    var $components = array('config');

	var $helpers = array();

	var $autoRender = false;

	var $autoLayout = false;

    function _loadFields()
    {
        $location = strtolower(Sanitize::getString($this->data,'location','content'));

        $location == 'listing' and $location = 'content';

        $fieldq = Sanitize::getString($this->data,'field');

        $fieldid = Sanitize::getString($this->data,'fieldid');

        return cmsFramework::jsonResponse($this->Field->getControlList($location,$fieldq,$fieldid));
    }

    function _loadValues()
    {
        $field_id = Sanitize::getString($this->data,'field_id');

        $valueq = Sanitize::getString($this->data,'value');

        if($field_id != '')
        {

            $field_options = $this->FieldOption->getControlList($field_id, $valueq);

            return cmsFramework::jsonResponse($field_options);
        }
    }
}
