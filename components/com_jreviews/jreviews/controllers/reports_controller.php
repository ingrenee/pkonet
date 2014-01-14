<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReportsController extends MyController {

	var $uses = array('report','review','menu');

	var $helpers = array('libraries','html','form');

	var $components = array('config','access','notifications');

	var $autoRender = true;

	var $autoLayout = false;

	function beforeFilter()
    {
        parent::beforeFilter();

        # Init Access
        $this->Access->init($this->Config);
		# Set Theme
		$this->viewTheme = $this->Config->template;
	}

	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Report;
	}

	function create()
	{
        $this->autoRender = false;
        $this->autoLayout = false;

        if($this->Config->user_report)
        {
            return $this->render('reports','create');
        } else {
            return s2Messages::accessDenied();
        }
    }

	function _save()
	{
        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array('success'=>false,'str'=>array());

		# Validate form token
        $this->components = array('security');

        $this->__initComponents();

        if($this->invalidToken) {

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
		}

		if($this->Config->user_report)
        {
            $User = cmsFramework::getUser();

			$this->data['Report']['report_text'] = Sanitize::getString($this->data['Report'],'report_text');

            $listing_id = $this->data['Report']['listing_id'] = Sanitize::getInt($this->data['Report'],'listing_id');

            $review_id = $this->data['Report']['review_id'] = Sanitize::getInt($this->data['Report'],'review_id');

            $media_id = $this->data['Report']['media_id'] = Sanitize::getInt($this->data['Report'],'media_id');

            $post_id = $this->data['Report']['post_id'] = Sanitize::getInt($this->data['Report'],'post_id');

            $extension = $this->data['Report']['extension'] = Sanitize::getString($this->data['Report'],'extension');

			if ($this->data['Report']['report_text'] != '') {

                $this->data['Report']['user_id'] = $User->id;

                $this->data['Report']['ipaddress'] = $this->ipaddress;

                $this->data['Report']['created'] = date('Y-m-d H:i:s');

                $this->data['Report']['approved'] = 0;

                if($User->id)
                {
                    $this->data['Report']['name'] = $User->name;
                    $this->data['Report']['username'] = $User->username;
                    $this->data['Report']['email'] = $User->email;
                }
                else {
                    $this->data['Report']['name'] = 'Guest';

                    $this->data['Report']['username'] = 'guest';
                }

				if($this->Report->store($this->data))
                {
                    $response['success'] = true;

                    return cmsFramework::jsonResponse($response);
                }

                $response['str'][] = 'DB_ERROR';

                return cmsFramework::jsonResponse($response);
			}

			# Validation failed

            $response['str'][] = 'REPORT_VALIDATE_MESSAGE';

            return cmsFramework::jsonResponse($response);
		}
	}
}
