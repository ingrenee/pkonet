<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsListingsController extends MyController
{
    var $uses = array('menu','media','paid_plan','paid_plan_category','paid_handler','paid_order','paid_txn_log');

    var $helpers = array('routes','assets','time','html','form','paginator','media','widgets','community','rating','paid','paid_routes');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Listing;
    }
    // Need to return object by reference for PHP4
    function &getObserverModel() {
        return $this->Listing;
    }

    function index()
    {
        if(!$this->_user->id) {
            cmsFramework::noAccess();
            return;
        }

        unset($this->Listing->joins['ParentCategory']); // Only works when specifying a cat id

        $conditions = array(
                'Listing.created_by = ' . (int) $this->_user->id,
                'Listing.catid IN (
                    SELECT DISTINCT
                        PaidPlanCategory.cat_id
                    FROM
                        #__jreviews_paid_plans_categories AS PaidPlanCategory
                    WHERE Listing.catid = PaidPlanCategory.cat_id
                )'
            );

        $listings = $this->Listing->findAll(array(
            'conditions'=>$conditions,
            'order'=>array('Listing.created DESC'),
            'limit'=>$this->limit,
            'offset'=>$this->offset
        ));

        $total = $this->Listing->findCount(array('conditions'=>$conditions));

        $this->set(array(
            'listings'=>$listings,
            'pagination'=>array(
                'total'=>$total
            ),
            'Config'=>$this->Config,
            'Access'=>$this->Access
        ));

        return $this->render('paid_account', 'listings');
    }
}
