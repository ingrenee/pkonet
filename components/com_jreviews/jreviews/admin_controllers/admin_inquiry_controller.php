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

class AdminInquiryController extends MyController
{
    var $uses = array('inquiry','menu');

    var $components = array('config','access','everywhere');

    var $helpers = array('routes','admin/paginator','html','time','text');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        # Call beforeFilter of MyAdminController parent class
        parent::beforeFilter();
    }

    function getEverywhereModel() {
        return $this->Inquiry;
    }

    function index(){

        return $this->browse();
    }

    function browse()
    {
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        $this->Listing->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder'));

        $search             = Sanitize::getString($this->params,'search');

        $conditions         = array();

        $filters            = Sanitize::getVar($this->params,'filter',array());

        $title              = Sanitize::getString($filters,'title');

        $date_from          = Sanitize::getString($filters,'date_from');

        $date_to            = Sanitize::getString($filters,'date_to');

        $title and $conditions[] = "LOWER( Listing.title ) LIKE " . $this->QuoteLike($title);

        $date_from and $conditions[] = "Inquiry.created >= " . $this->Quote($date_from);

        $date_to and $conditions[] = "Inquiry.created <= " . $this->Quote($date_to);

        $queryData = array(
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'conditions'=>$conditions,
            'joins'=>array(
                'INNER JOIN #__content AS Listing on Listing.id = Inquiry.listing_id'
                )
            );

        $inquiries = $this->Inquiry->findAll($queryData,array('plgAfterFind'));

        $total = $this->Inquiry->findCount($queryData);

        $this->set(array(
                'inquiries'=>$inquiries,
                'pagination'=>array(
                    'total'=>$total
                ),
                'filters'=>$filters
        ));

        return $this->render('inquiries','browse');
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $this->Inquiry->delete('inquiry_id',$ids);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }
}