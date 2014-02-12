<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsTxnController extends MyController
{
    var $uses = array('menu','paid_order','paid_txn_log','paid_handler','paid_plan');

    var $helpers = array('html','form','time','paginator','paid_routes');

    var $components = array('access','config');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        parent::beforeFilter();
    }

    function index()
    {
        $total = 0;

        $conditions = array();

        $listing_title = Sanitize::getString($this->params,'title');

        if($listing_title) {

        $query = "
                SELECT
                    Listing.id
                FROM
                    #__content AS Listing
                WHERE
                    Listing.title LIKE " . $this->quoteLike($listing_title) . "
                    AND
                    Listing.id IN (
                        SELECT listing_id FROM #__jreviews_paid_orders
                    )
            ";

            if($listing_ids = $this->PaidOrder->query($query,'loadColumn'))
            {
                $conditions[] = 'PaidOrder.listing_id IN (' .cleanIntegerCommaList($listing_ids) . ')';
            }
        }

        $filters = Sanitize::getVar($this->params,'filter',array());

        foreach($filters AS $filter=>$value) {

            if(is_array($value)) {

                $modelName = $filter == 'txn_date' ? 'PaidTxnLog' : 'PaidOrder';

                $value = array_filter($value,array($this,'cleanFilterValues'));

                if(count($value) == 1 && $value != '') {

                    $conditions[] = $modelName.'.'.$filter . ' >= ' . $this->quote($value);
                }
                elseif(!empty($value)) {

                    $conditions[] = $modelName.'.'.$filter . ' BETWEEN ' . $this->quote($value[0]) . ' AND ' . $this->quote($value[1]);
                }

            }
            elseif($value != '') {

                $conditions[] = 'PaidOrder.'.$filter . ' = ' . $this->quote($value);
            }
        }

       // For search/filtering
        $plans = $this->PaidPlan->getPlanList();

        $handler_list = $this->PaidHandler->getList();

        $joins = array(
            "LEFT JOIN #__jreviews_paid_orders AS PaidOrder ON PaidOrder.order_id = PaidTxnLog.order_id",
            'INNER JOIN #__content AS Listing ON Listing.id = PaidOrder.listing_id',
            "LEFT JOIN #__jreviews_paid_handlers AS PaidHandler ON PaidTxnLog.handler_id = PaidHandler.handler_id"
        );

        $conditions[] = 'PaidTxnLog.txn_success = 1';

        $conditions[] = 'PaidOrder.handler_id > 0';

        $txns = $this->PaidTxnLog->findAll(array(
            'fields'=>array(
                'Listing.title AS `Listing.title`',
                'PaidOrder.user_id AS `PaidOrder.user_id`',
                'PaidOrder.plan_info AS `PaidOrder.plan_info`',
                'PaidOrder.listing_info AS `PaidOrder.listing_info`',
                'PaidOrder.order_amount AS `PaidOrder.order_amount`',
                'PaidOrder.coupon_name AS `PaidOrder.coupon_name`',
                'PaidHandler.name AS `PaidHandler.name`'
            ),
            'conditions'=>$conditions,
            'joins'=>$joins,
            'limit'=>$this->limit,
            'offset'=>$this->offset,
            'order'=>array('PaidTxnLog.log_id DESC')
        ));

        !empty($txns) and $total = $this->PaidTxnLog->findCount(array('conditions'=>$conditions,'joins'=>$joins));

        $this->set(array(
            'pagination'=>array('total'=>$total),
            'txns'=>$txns,
            'plans'=>$plans,
            'handler_list'=>$handler_list
        ));

        return $this->render('paidlistings_txn','index');
    }

    function cleanFilterValues($value) {

        if($value !== '') return $value;

        return false;
    }

    function getOrderTxn()
    {
        $conditions = $joins = array();

        $order_id = Sanitize::getInt($this->params,'id');

        $txn_id = Sanitize::getInt($this->params,'txn_id');

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        $order_id and $conditions[] = 'PaidTxnLog.order_id = ' . $order_id;

        $txn_id and $conditions[] = 'PaidTxnLog.log_id = ' . $txn_id;

        $listing_id and $conditions[] = 'PaidOrder.listing_id = ' . $listing_id;

        $listing_id and $joins = array("LEFT JOIN #__jreviews_paid_orders AS PaidOrder ON PaidOrder.order_id = PaidTxnLog.order_id");

        $txns = $this->PaidTxnLog->findAll(array(
            'conditions'=>$conditions,
            'joins'=>$joins,
            'order'=>array('PaidTxnLog.log_id DESC')
        ));

        $this->set('txns',$txns);

        return $this->render('paidlistings_txn','order_txn');
    }

    function _delete()
    {
        $response = array('success'=>false);

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        foreach($ids AS $txn_log_id)
        {

            $this->PaidTxnLog->delete('log_id',$txn_log_id);
        }

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }
}