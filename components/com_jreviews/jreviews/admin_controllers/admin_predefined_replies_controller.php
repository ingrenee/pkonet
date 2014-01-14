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

class AdminPredefinedRepliesController extends MyController {

	var $uses = array('predefined_reply');

	var $helpers = array();

    var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

    function beforeFilter()
    {
        parent::beforeFilter();
    }

	function index()
    {
        $raw_replies = $this->PredefinedReply->findAll(array(
            'fields'=>array('PredefinedReply.*'),
            'order'=>array('PredefinedReply.reply_id ASC')
        ));

		foreach($raw_replies AS $key=>$reply) {
			extract($reply['PredefinedReply']);
			$replies[$reply_type][] = $reply['PredefinedReply'];
		}

		$this->set(array(
			'replies'=>$replies
		));

		return $this->render('predefined_replies','predefined_replies');
	}

	function _save()
    {
        $count = $this->PredefinedReply->findCount(array());
        $i = 0;

        foreach($this->data['replies'] AS $key=>$data)
        {
			$data['PredefinedReply']['reply_body'] = $this->data['__raw']['replies'][$key]['PredefinedReply']['reply_body'];
			$this->PredefinedReply->store($data);
		}
	}

}