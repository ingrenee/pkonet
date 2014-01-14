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

class AdminClaimsController extends MyController
{
    var $uses = array('claim','predefined_reply','menu','field','criteria');

    var $components = array('access','config','everywhere','admin/admin_notifications');

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
        return $this->Claim;
    }

    function edit()
    {
        $id = Sanitize::getInt($this->params,'id');

        $user_id = Sanitize::getInt($this->params,'user_id');

        $referrer = Sanitize::getInt($this->params,'referrer');

        $claim = $this->Claim->findRow(array('conditions'=>array(
            'Claim.listing_id = ' . $id,
            'Claim.approved = 1'
        )));

        if(empty($claim)) {

            $claim = array('Claim'=>array(
                'claim_id'=>'',
                'claim_text'=>'',
                'claim_note'=>'',
                'name'=>'',
                'created'=>_CURRENT_SERVER_TIME,
                'approved'=>1,
                'user_id'=>$user_id,
                'listing_id'=>$id,
            ));
        }

        $this->set(array(
            'claim'=>$claim,
            'referrer'=>$referrer
        ));

        return $this->render('claims','edit');
    }

    function moderation()
    {
        $reviews = array();

        $predefined_replies = array();

        $this->limit = 10;

        $processed = Sanitize::getInt($this->params,'processed');

        $this->offset = $this->offset - $processed;

        $conditions = array(
                "Claim.approved = 0"
                //,"Claim.claim_text <> ''" /*allow claims without text*/
            );

        $total = 0;

        $claims = $this->Claim->findAll(array(
            'fields'=>array(
                'Claim.*',
                'User.name AS `Claim.name`',
                'User.email AS `Claim.email`'
            ),
            'conditions'=>$conditions,
            'joins'=>array(
                'LEFT JOIN #__users AS User ON User.id = Claim.user_id'
            ),
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('Claim.created DESC')
        ));

       if(!empty($claims))
       {
			$total = $this->Claim->findCount(array('conditions'=>$conditions,'session_cache'=>false));

            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "claim"')
                ));

            // Complete the listing info for claim
            // First get listing ids
            $listing_ids = array();
            foreach($claims AS $key=>$claim)
            {
                $listing_ids[] = $claim['Claim']['listing_id'];
            }

            $listings = $this->Listing->findAll(array(
                'conditions'=>array(
                    'Listing.id IN (' . implode(',',$listing_ids) . ')'
                )
            ),array('afterFind'));

            # Pre-process all urls to sef
            $this->_getListingSefUrls($listings);

            foreach($claims AS $key=>$claim)
            {
                if(isset($listings[$claim['Claim']['listing_id']]))
                {
                    $claims[$key] = array_merge($listings[$claim['Claim']['listing_id']],$claim);
                }
                else
                {
                    // The listing no longer exists, don't show the claim
                    unset($claims[$key]);
                }
            }
       }

        $this->set(array(
            'processed'=>$processed,
            'claims'=>$claims,
            'predefined_replies'=>$predefined_replies,
            'total'=>$total
        ));

        return $this->render('claims','claims');
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid', Sanitize::getInt($this->params,'id'));

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $deleted = $this->Claim->delete('claim_id',$ids);

        if($deleted) {

            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $response = array();

        $response['success'] = false;

        if($this->Claim->store($this->data)){

            $response['success'] = true;

            $response['state'] = $this->data['Claim']['approved'];
        }

        clearCache('', 'views');

        clearCache('', '__data');

        return cmsFramework::jsonResponse($response);
    }

}