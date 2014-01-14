<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class OwnerRepliesController extends MyController
{
    var $uses = array('menu','review','owner_reply','criteria');

    var $helpers = array('libraries','html','form');

    var $components = array('config','access','notifications','everywhere');

    var $autoRender = false;

    var $autoLayout = false;

    var $review_id = 0;

    var $denyAccess = false;

    function getEverywhereModel() {
        return $this->Review;
    }

    function beforeFilter()
    {
        parent::beforeFilter();

        if(Sanitize::getInt($this->data,'OwnerReply'))
        {
            $this->review_id = Sanitize::getInt($this->data['OwnerReply'],'id');
        } else {
            $this->review_id = Sanitize::getInt($this->params,'review_id');
        }

        if(!$this->Config->owner_replies || $this->review_id == 0 || $this->_user->id == 0) {
            $this->denyAccess = true;
            return;
        }

        // Get the listing id and extension
        $this->_db->setQuery("
            SELECT
                Review.pid AS listing_id, Review.`mode` AS extension
            FROM
                #__jreviews_comments AS Review
            WHERE
                Review.id = " . $this->review_id
        );

        // Get listing owner id and check if it matches the current user
        if($listing = current($this->_db->loadAssocList())){
            // Automagically load and initialize Everywhere Model to check if user is listing owner
            S2App::import('Model','everywhere_'.$listing['extension'],'jreviews');
            $class_name = inflector::camelize('everywhere_'.$listing['extension']).'Model';
            if(class_exists($class_name)) {
                $this->Listing = new $class_name();
                $owner = $this->Listing->getListingOwner($listing['listing_id']);
                if($this->_user->id != $owner['user_id']){
                    $this->denyAccess = true;
                    return;
                }
                $this->data['Listing']['created_by'] = $owner['user_id']; // Used in the Activities component
                $this->data['Listing']['listing_id'] = $listing['listing_id']; // Used in the Activities component
                $this->data['Listing']['extension'] = $listing['extension']; // Used in the Activities component
            }
        }
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->OwnerReply;
    }

    function create()
    {
        if($this->denyAccess == true)
        {
            return s2Messages::accessDenied();
        }

        $this->set('review_id',$this->review_id);

        return $this->render('owner_reply','create');
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());

        $formToken = cmsFramework::getCustomToken($this->review_id);

        if($this->denyAccess == true || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        # Validate form token
        $this->components = array('security');

        $this->__initComponents();

        if($this->invalidToken) {

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

        // Check if an owner reply already exists
        $this->OwnerReply->fields = array();

        if($reply = $this->OwnerReply->findRow(array(
            'fields'=>array('OwnerReply.owner_reply_text','OwnerReply.owner_reply_approved'),
            'conditions'=>array('OwnerReply.id = ' . $this->review_id)
        )))
        {
            if($reply['OwnerReply']['owner_reply_approved'] == 1){

                $response['str'][] = 'OWNER_REPLY_DUPLICATE';

                return cmsFramework::jsonResponse($response);
            }
        }

        if($this->Config->owner_replies)
        {
            if ($this->data['OwnerReply']['owner_reply_text'] != '' && $this->data['OwnerReply']['id'] > 0)
            {
                $this->data['OwnerReply']['owner_reply_created'] = date('Y-m-d H:i:s');

                $this->data['OwnerReply']['owner_reply_approved'] = (int) !$this->Access->moderateOwnerReply(); // Replies will be moderated by default

                if($this->OwnerReply->store($this->data))
                {
                    $response['success'] = true;

                    $update_text = $this->data['OwnerReply']['owner_reply_approved'] ?

                        $response['str'][] = 'OWNER_REPLY_NEW'
                        :
                        $response['str'][] = 'OWNER_REPLY_MODERATE'
                    ;

                    return cmsFramework::jsonResponse($response);
                }

                $response['str'][] = 'DB_ERROR';

                return cmsFramework::jsonResponse($response);
            }

            # Validation failed

            $response['str'][] = 'OWNER_REPLY_VALIDATE_REPLY';

            return cmsFramework::jsonResponse($response);
        }
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $review_id = $this->data['OwnerReply']['id'] = Sanitize::getInt($this->params,'id');

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($review_id);

        if(!$review_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        $review = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)));

        # Check access
        if(!$this->Access->canDeleteOwnerReply($review))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $this->data['OwnerReply']['owner_reply_text'] = '';
        $this->data['OwnerReply']['owner_reply_approved'] = 0;

        $this->OwnerReply->store($this->data);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }
}
