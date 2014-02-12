<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class HandlerJomsocialComponent extends S2Component
{
	var $period = array('days'=>'D','weeks'=>'W','months'=>'M','years'=>'Y');

	/**
	* Handler configuration array
	*
	* @var array
	*/
	var $handler = array();

	var $enabled = false;

	function startup(&$controller)
	{
		$this->c = &$controller;

		$path = PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php';

		if(file_exists($path))
		{
			include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');
			if(file_exists(PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'userpoints.php'))
			{
				include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'userpoints.php');
				$this->enabled = true;
			}
		}
	}

	/**
	* Generates post data to be sent to Paypal
	*
	*/
	function submit($handler, $plan, $listing, $order)
	{
		if(!$this->enabled) return;
		$c = &$this->c;
		$handler = $handler['PaidHandler'];
		$handler_settings = & $handler['settings'];
		$plan = $plan['PaidPlan'];
		$plan_array = $plan['plan_array'];
		$listing = $listing['Listing'];

		$PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

		// Charge JomSocial points
		// Instantiate models
		$PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');
		$PaidOrder = ClassRegistry::getClass('PaidOrderModel');

		# Run some security checks
        $user_id = $this->c->_user->id;

		if(!$user_id
			||
			$order['PaidOrder']['user_id'] != $user_id
			||
			$order['PaidOrder']['plan_id'] != $plan['plan_id']
			||
			($order['PaidOrder']['order_amount'] + $order['PaidOrder']['order_discount'] - Sanitize::getFloat($order['PaidOrder'],'order_tax') != $plan['plan_price'])
		) {
			// The logged in user doesn't match the user in the order
			$PaidTxnLog->addNote("Payment status: Failed.");
			$PaidTxnLog->addNote("Reason: The order details don't match the plan details or the order was submitted by a different user.");
			$PaidTxnLog->addNote("Order active: 0");
			$PaidOrder->updateOrder($order,array('order_status'=>'Failed','order_active'=>0));
		}
		else {

			// Get required points amount
			$exchange_rate = Sanitize::getInt($handler_settings, 'exchange_rate', 1);
			$display_zero = Sanitize::getBool($handler_settings, 'display_zero', 1);
			$points_amount = $exchange_rate * $order['PaidOrder']['order_amount'];

			// get total points
			$user	= CFactory::getUser($order['PaidOrder']['user_id']);

			$total_points = $user->getKarmaPoint();

			if($total_points >= $points_amount)
			{
				$user->_points = $total_points - $points_amount;

				$user->save();

				if($display_zero) {
					$order['PaidOrder']['order_amount'] = 0; // Set the transaction value of point payments to zero
				}

				$order['PaidOrder']['order_status'] = 'Complete';
				$c->Paidlistings->processSuccessfulOrder($order);
				$txn_id = time();
				$order_data = array('txn_id' => $txn_id, 'points_deducted'=>$points_amount);
				$PaidTxnLog->save($order, $order_data, $txn_id, true);
			} else {
				// Not enough points
				$PaidTxnLog->addNote("Payment status: Failed.");
				$PaidTxnLog->addNote("Order active: 0");
				$PaidOrder->updateOrder($order,array('order_status'=>'Failed','order_active'=>0));
			}


		}

		// Redirect to payment instructions
		$complete_url = $PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'order_id'=>$order['PaidOrder']['order_id']));

		$complete_url = str_replace('&amp;','&',$complete_url);

		return $complete_url;
	}

	function getPointBalance($handler, $user_id)
	{
		$balance = array('points' => 0, 'value' => 1);
		if($this->enabled) {
			$handler = $handler['PaidHandler'];
			$handler_settings = & $handler['settings'];
			$exchange_rate = Sanitize::getInt($handler_settings, 'exchange_rate', 1);

			$user	= CFactory::getUser($user_id);

			$balance['points'] = $user->getKarmaPoint();
			$balance['value'] = floor($balance['points'] / $exchange_rate);

			return $balance;
		}
		return $balance;
	}
}