<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsCronFunctionsComponent extends S2Component {

    var $plugin_order = 1;

    var $name = 'paidlistings_cron_functions';

    var $published = true;

    var $c;

    function startup(&$controller)
    {
        if(!isset($controller->Config)
			|| (!$controller->Config->cron_site_visits && $controller->name != 'cron')
			|| $controller->ajaxRequest
			|| Sanitize::getString($controller->params,'action') == 'xml'
			|| Sanitize::getString($controller,'action' == 'com_content_blog'))

				return;

		if(Configure::read('JreviewsSystem.paid_cron')) {
			return;
		}

		Configure::write('JreviewsSystem.paid_cron',1);

        # Trigger cron
    	$this->c = & $controller;

		S2App::import('Model',array('paid_plan','paid_plan_category','paid_order','paid_txn_log','paid_listing_field'),'jreviews');

    	$this->processOrders();

    	$this->processNotifications();
    }

    function processOrders()
    {
		$cron_period = Sanitize::getVar($this->c->Config,'paid_orders_interval') * 3600;

		$last_run = Sanitize::getInt($this->c->Config,'paid_last_orders');

		$now = time();

		if($last_run + $cron_period <= $now)
		{
			$this->c->Config->store(array('paid_last_orders'=>$now));

	        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

	        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

	        # Order expirations

            $PaidOrder->updateAllExpiredOrders();

	        # Order renewals

	        $orders = $PaidOrder->findAll(array(
	            'conditions'=>array(
	                "PaidOrder.order_status = 'Complete'",
	                "PaidOrder.order_renewal <= '".gmdate('Y-m-d',time())."'",
	                'PaidOrder.order_renewal != "'._NULL_DATE.'"',
	                'PaidOrder.order_active = 0'
	            )
	        ));

	        if(!empty($orders)) {

			    foreach($orders AS $order)
		        {
		            $PaidTxnLog->addNote("Modified by: cron");

		            $order['PaidOrder']['order_renewal'] = _NULL_DATE; // To allow the order to process

		            $success = $this->c->Paidlistings->processSuccessfulOrder($order,1/* set listing as published */);

		            $PaidTxnLog->save($order, '', 'Renewal processed', $success);
		        }
 	        }
		}
	}

	function processNotifications()
	{
		$cron_period = Sanitize::getVar($this->c->Config,'paid_notifications_interval') * 3600;

		$last_run = Sanitize::getInt($this->c->Config,'paid_last_notifications');

		$now = time();

		if($last_run + $cron_period <= $now)
		{
			$this->c->Config->store(array('paid_last_notifications'=>$now));

	        # Expiration notifications
	        $expires1 = Sanitize::getInt($this->c->Config,'paid.notify_expiration1');

	        $expires2 = Sanitize::getInt($this->c->Config,'paid.notify_expiration2');

	        if($expires1 || $expires2)
	        {
	            S2App::import('Component','paidlistings_notifications');

	            $PaidNotifications = new PaidlistingsNotificationsComponent();

	            $PaidNotifications->checkExpiringOrders($this->c);
	        }
		}
	}
}
