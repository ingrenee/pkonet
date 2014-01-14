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

class AdminOwnerRepliesController extends MyController {

	var $uses = array('menu','owner_reply','predefined_reply','review','criteria');

	var $components = array('config','admin/admin_notifications','everywhere');

    var $helpers = array('html','routes','admin/admin_routes','form','time','rating','custom_fields');

	var $autoRender = false;

	var $autoLayout = false;

    var $__loaded = array();

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

    // Need to return object by reference for PHP4
    function &getNotifyModel(){
        return $this->OwnerReply;
    }

    function &getEverywhereModel(){
        return $this->Review;
    }

	function moderation()
    {
        $reviews = array();

        $this->params = $this->data;

        $this->limit = 1;

        $processed = Sanitize::getInt($this->params,'processed');

        $this->offset = $this->offset - $processed;

        $conditions = array(
            "OwnerReply.pid > 0",
            "OwnerReply.owner_reply_approved = 0",
            "OwnerReply.owner_reply_text<>''"
        );

		$replies = $this->OwnerReply->findAll(array(
            'fields'=>array(
                'CASE WHEN CHAR_LENGTH(User.name) THEN User.name ELSE OwnerReply.name END AS `User.name`',
                'OwnerReply.email AS `User.email`'
            ),
            'conditions'=>$conditions,
            'joins'=>array(
                'LEFT JOIN #__users AS User ON User.id = OwnerReply.userid'
            ),
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('OwnerReply.owner_reply_created DESC')
        ));

        $total = $this->OwnerReply->findCount(array('conditions'=>$conditions));

       if(!empty($replies))
       {
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "owner_reply"')
                ));

            $this->Review->runProcessRatings = false;
            $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

            // Complete the owner info for each reply
            // Get the review info for each reply
            $reviews = $this->Review->findAll(array(
                'conditions'=>'Review.id IN ('.implode(',',array_keys($replies)).')'
            ));

            # Pre-process all urls to sef
            $this->_getListingSefUrls($reviews);

            $this->_getReviewSefUrls($reviews);

            foreach($replies AS $key=>$reply)
            {
                // Automagically load and initialize Everywhere Model to check if user is listing owner
               if(!isset($this->__loaded[$reply['Review']['extension']]))
                {
                    S2App::import('Model','everywhere_'.$reply['Review']['extension'],'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$reply['Review']['extension']).'Model';

                    if(class_exists($class_name))
                    {
                        ${$reply['Review']['extension']} = new $class_name();
                    }
                }

                $replies[$key]['Owner'] = ${$reply['Review']['extension']}->getListingOwner($reply['Review']['listing_id']);

                isset($reviews[$reply['Review']['review_id']]) and $replies[$key] = array_merge($replies[$key],$reviews[$reply['Review']['review_id']]);
            }
       }

        $this->set(array(
            'processed'=>$processed,
            'total'=>$total,
			'owner_replies'=>$replies,
            'predefined_replies'=>!empty($predefined_replies) ? $predefined_replies : array()
		));

        return $this->render('owner_replies','moderation');
	}

    function edit()
    {
        $review_id = Sanitize::getInt($this->params,'id');

        $referrer = Sanitize::getInt($this->params,'referrer');

        $owner_reply = $this->OwnerReply->findRow(array('conditions'=>array('OwnerReply.id = ' . $review_id)));

        $this->set(array(
            'owner_reply'=>$owner_reply,
            'referrer'=>$referrer
        ));

        return $this->render('owner_replies','edit');
    }

    function _delete()
    {
        $response = array();

        $response['success'] = true;

        $entry_id = Sanitize::getInt($this->data,'entry_id');

        $this->data['OwnerReply']['id'] = $entry_id;
        $this->data['OwnerReply']['owner_reply_text'] = '';
        $this->data['OwnerReply']['owner_reply_note'] = '';
        $this->data['OwnerReply']['owner_reply_created'] = NULL_DATE;
        $this->data['OwnerReply']['owner_reply_approved'] = 0;

        # Delete listing and all associated records and images
        $deleted = $this->OwnerReply->store($this->data);

        if($deleted){
            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array();

        $response['success'] = false;

        if(Sanitize::getString($this->data['OwnerReply'],'owner_reply_created')=='')
            {
                $this->data['OwnerReply']['owner_reply_created'] = date('Y-m-d H:i:s');
            }

        if($this->OwnerReply->store($this->data))
        {
            $response['success'] = true;

            // We are in the review browse page
            clearCache('', 'views');
            clearCache('', '__data');

            $response['moderation'] = $this->data['OwnerReply']['owner_reply_approved'] === 0;

            $response['state'] = $this->data['OwnerReply']['owner_reply_approved'];

            return cmsFramework::jsonResponse($response);
        }

    }

}