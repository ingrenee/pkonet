<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class HandlerAlphauserpointsComponent extends S2Component
{
	var $period = array('days'=>'D','weeks'=>'W','months'=>'M','years'=>'Y');

	/**
	* Handler configuration array
	*
	* @var array
	*/
	var $handler = array();

	var $enabled = true;

	function startup(&$controller)
	{
		$this->c = &$controller;

		$api_AUP = PATH_ROOT . 'components' . DS . 'com_alphauserpoints' . DS . 'helper.php';

		if(file_exists($api_AUP))
		{
			require_once($api_AUP);
		}
		else
		{
			$this->enabled = false;
		}
	}

	/**
	* Generates post data to be sent to Paypal
	*
	*/
	function submit($handler, $plan, $listing, $order)
	{
		if(!$this->enabled) return;

		$handler = $handler['PaidHandler'];

		$handler_settings = & $handler['settings'];

		$plan = $plan['PaidPlan'];

		$plan_array = $plan['plan_array'];

		$listing = $listing['Listing'];

		$PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

		// Charge AUP points
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
			$exchange_rate = Sanitize::getFloat($handler_settings, 'exchange_rate', 1.00);

			$display_zero = Sanitize::getBool($handler_settings, 'display_zero', 1);

			$points_amount = $exchange_rate * $order['PaidOrder']['order_amount'];

			$AUPHelper = new AlphaUserPointsHelper();

			$aupid = $AUPHelper->getAnyUserReferreID($order['PaidOrder']['user_id']);

			if($aupid)
			{
				$total_points = (float) $AUPHelper->getCurrentTotalPoints($aupid);

				if($total_points >= $points_amount)
				{
					$AUPHelper->newpoints('plgaup_jreviews_paidlistings', $aupid, '', 'JReviews Order #' . $order['PaidOrder']['order_id'], $points_amount * -1.00);

					if($display_zero)
					{
						$order['PaidOrder']['order_amount'] = 0; // Set the transaction value of point payments to zero
					}

					$order['PaidOrder']['order_status'] = 'Complete';

					$this->c->Paidlistings->processSuccessfulOrder($order);

					$txn_id = time();

					$order_data = array('txn_id' => $txn_id, 'points_deducted'=>$points_amount);

					$PaidTxnLog->save($order, $order_data, $txn_id, true);
				}
				else {

					// Not anough points

					$PaidTxnLog->addNote("Payment status: Failed.");

					$PaidTxnLog->addNote("Order active: 0");

					$PaidOrder->updateOrder($order,array('order_status'=>'Failed','order_active'=>0));
				}
			}
			else {

				// Referre ID not found

				$PaidTxnLog->addNote("Payment status: Failed.");

				$PaidTxnLog->addNote("Order active: 0");

				$PaidOrder->updateOrder($order,array('order_status'=>$order_status,'order_active'=>0));
			}


		}

		// Redirect to payment instructions
		$complete_url = $PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'order_id'=>$order['PaidOrder']['order_id']));

		$complete_url = str_replace('&amp;','&',$complete_url);

		return $complete_url;
	}

	function getPointBalance($handler, $user_id)
	{
		$balance = array('points' => 0, 'value' => 0);

		if($this->enabled) {

			$AUPHelper = new AlphaUserPointsHelper();

			$handler = $handler['PaidHandler'];

			$handler_settings = & $handler['settings'];

			$exchange_rate = Sanitize::getFloat($handler_settings, 'exchange_rate', 1.00);

			$aupid = $AUPHelper->getAnyUserReferreID($user_id);

			if($aupid)
			{
				$balance['points'] = (float) $AUPHelper->getCurrentTotalPoints($aupid);

				$balance['value'] = $balance['points'] / $exchange_rate;

				return $balance;
			}
		}

		return $balance;
	}
}