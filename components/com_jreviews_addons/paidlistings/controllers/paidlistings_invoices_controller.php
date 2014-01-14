<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsInvoicesController extends MyController
{
    var $uses = array('menu','paid_txn_log','paid_order','paid_account');

    var $components = array('config','access');

    var $helpers = array('html','time','paginator','paid_routes');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        # Make configuration available in models
//        $this->Listing->Config = &$this->Config;

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index()
    {
        if(!$this->_user->id) {
            cmsFramework::noAccess();
            return;
        }

        $queryString = array(
            'fields'=>array(
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE())' . ' AS `PaidOrder.daysToExpiration`',
                'Listing.title AS `Listing.title`',
                'PaidOrder.plan_info AS `PaidOrder.plan_info`',
                'PaidOrder.order_active AS `PaidOrder.order_active`',
                'PaidOrder.payment_type AS `PaidOrder.payment_type`',
                'PaidOrder.order_amount AS `PaidOrder.order_amount`',
                'PaidOrder.order_created AS `PaidOrder.order_created`',
                'PaidOrder.order_never_expires AS `PaidOrder.order_never_expires`',
                'PaidOrder.order_expires AS `PaidOrder.order_expires`',
                'PaidOrder.listing_info AS `PaidOrder.listing_info`',
                'PaidHandler.name AS `PaidHandler.name`'
            ),
            'conditions'=>array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'PaidTxnLog.txn_success = 1',
                'PaidOrder.order_status = "Complete"'
            ),
            'order'=>array(
                'PaidTxnLog.txn_date DESC'
            ),
            'joins'=>array(
                'RIGHT JOIN #__jreviews_paid_orders AS PaidOrder ON PaidOrder.order_id = PaidTxnLog.order_id',
                'LEFT JOIN #__content AS Listing ON Listing.id = PaidOrder.listing_id',
                'LEFT JOIN #__jreviews_paid_handlers AS PaidHandler ON PaidHandler.handler_id = PaidTxnLog.handler_id',
            )
            ,'limit'=>$this->limit,
            'offset'=>$this->offset
        );

        $invoices = $this->PaidTxnLog->findAll($queryString);

        foreach($invoices AS $key=>$row)
        {
            $invoices[$key]['PaidOrder']['listing_info'] = is_array($row['PaidOrder']['listing_info']) ? $row['PaidOrder']['listing_info'] : json_decode($row['PaidOrder']['listing_info'],true);

            $invoices[$key]['PaidOrder']['plan_info'] = is_array($row['PaidOrder']['plan_info']) ? $row['PaidOrder']['plan_info'] : json_decode($row['PaidOrder']['plan_info'],true);
        }

        $total = $this->PaidTxnLog->findCount(array(
            'conditions'=>array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'PaidTxnLog.txn_success = 1',
                'PaidOrder.order_status = "Complete"'
            ),
            'joins'=>array(
                'RIGHT JOIN #__jreviews_paid_orders AS PaidOrder ON PaidOrder.order_id = PaidTxnLog.order_id'
            )
        ));

        $this->set(array(
            'invoices'=>$invoices,
            'pagination'=>array(
                'total'=>$total
            )
        ));

        echo $this->render('paid_account', 'invoices');
    }

    function view()
    {
        if(!$this->_user->id)
        {
            cmsFramework::noAccess();
            return;
        }

        $txn_id = Sanitize::getInt($this->params,'invoice');

        if(!$txn_id)
        {
            cmsFramework::noAccess();
            return;
        }

        $user_id = Sanitize::getInt($this->params,'user');

        // Only use the user id in the url if the current logged in  user is manager or above
        // This is reserved for letting high access users view invoices of any client
        if(!$this->Access->isManager() || !$user_id) {

            $user_id = (int) $this->_user->id;
        }

        $queryString = array(
            'fields'=>array(
                'PaidOrder.order_id AS `PaidOrder.order_id`',
                'PaidOrder.plan_info AS `PaidOrder.plan_info`',
                'PaidOrder.order_amount AS `PaidOrder.order_amount`',
                'PaidOrder.order_discount AS `PaidOrder.order_discount`',
                'PaidOrder.coupon_name AS `PaidOrder.coupon_name`',
                'PaidOrder.order_tax AS `PaidOrder.order_tax`',
                'PaidOrder.order_never_expires AS `PaidOrder.order_never_expires`',
                'PaidOrder.order_expires AS `PaidOrder.order_expires`',
                'PaidOrder.order_status AS `PaidOrder.order_status`',
                'PaidOrder.listing_info AS `PaidOrder.listing_info`',
                'PaidHandler.name AS `PaidHandler.name`'
            ),
            'conditions'=>array(
                'PaidOrder.user_id = ' . $user_id,
                'PaidTxnLog.log_id = ' . $txn_id,
                'PaidTxnLog.txn_success = 1'
            ),
              'joins'=>array(
                'RIGHT JOIN #__jreviews_paid_orders AS PaidOrder ON PaidOrder.order_id = PaidTxnLog.order_id',
                'LEFT JOIN #__jreviews_paid_handlers AS PaidHandler ON PaidHandler.handler_id = PaidOrder.handler_id'
            )
        );

        $invoice = $this->PaidTxnLog->findRow($queryString);

        $invoice['PaidOrder']['listing_info'] = is_array($invoice['PaidOrder']['listing_info']) ? $invoice['PaidOrder']['listing_info'] : json_decode($invoice['PaidOrder']['listing_info'],true);

        $invoice['PaidOrder']['plan_info'] = is_array($invoice['PaidOrder']['plan_info']) ? $invoice['PaidOrder']['plan_info'] : json_decode($invoice['PaidOrder']['plan_info'],true);

        $account = $this->PaidAccount->findRow(array('conditions'=>array('PaidAccount.user_id = ' . (int) $this->_user->id)));

        if(empty($account)) {

            $account = array('PaidAccount'=>array(
                    'name'=>'',
                    'business'=>'',
                    'address'=>'',
                    'country'=>'',
                    'tax_id'=>''
                ));
        }

        $this->set(array(
            'account'=>$account,
            'invoice'=>$invoice
        ));

        return $this->render('paid_invoice', 'index');
    }

    function unpaid()
    {
        if(!$this->_user->id)
        {
            cmsFramework::noAccess();
            return;
        }

        $order_id = Sanitize::getInt($this->params,'order');

        if(!$order_id)
        {
            cmsFramework::noAccess();
            return;
        }

        $queryString = array(
            'fields'=>array(
                'PaidHandler.name AS `PaidHandler.name`',
                'PaidHandler.settings AS `PaidHandler.settings`',
            ),
            'conditions'=>array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'PaidOrder.order_id = ' . $order_id,
            ),
              'joins'=>array(
                'LEFT JOIN #__jreviews_paid_handlers AS PaidHandler ON PaidHandler.handler_id = PaidOrder.handler_id'
            )
        );

        $invoice = $this->PaidOrder->findRow($queryString);

        $invoice['PaidHandler']['settings'] = json_decode($invoice['PaidHandler']['settings'],true);

        $invoice['PaidOrder']['listing_info'] = is_array($invoice['PaidOrder']['listing_info']) ? $invoice['PaidOrder']['listing_info'] : json_decode($invoice['PaidOrder']['listing_info'],true);

        $invoice['PaidOrder']['plan_info'] = is_array($invoice['PaidOrder']['plan_info']) ? $invoice['PaidOrder']['plan_info'] : json_decode($invoice['PaidOrder']['plan_info'],true);

        $account = $this->PaidAccount->findRow(array('conditions'=>array('PaidAccount.user_id = ' . (int) $this->_user->id)));

        if(empty($account)) {

            $account = array('PaidAccount'=>array(
                    'name'=>'',
                    'business'=>'',
                    'address'=>'',
                    'country'=>'',
                    'tax_id'=>''
                ));
        }

        $this->set(array(
            'account'=>$account,
            'invoice'=>$invoice
        ));

        return $this->render('paid_invoice', 'index');
    }
}