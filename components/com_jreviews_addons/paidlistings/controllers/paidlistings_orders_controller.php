<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model','paid_txn_log','jreviews');

class PaidlistingsOrdersController extends MyController
{
    var $uses = array('menu','media','paid_plan','paid_order','paid_plan_category','paid_handler','paid_order','paid_email','paid_coupon','article');

    var $components = array('config','access','everywhere','media_storage','paidlistings_notifications');

    var $helpers = array('html','form','assets','paginator','time','paid','routes','paid_routes');

    var $autoRender = false;

    var $autoLayout = false;

    var $couponProcess = false;

    function beforeFilter()
    {
        $this->PaidOrder = ClassRegistry::getClass('PaidOrderModel');
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    // Need to return object by reference for PHP4
/*    function &getObserverModel() {
        return $this->Listing;
    }    */

    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Listing;  // Listing returned because plugins will work on this model, not PaidOrders
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');
        return $PaidOrder;
    }

    function afterFilter()
    {
        if(in_array($this->action,array('process','_process','complete')))
        {
            $this->assets = array_merge(
                $this->assets,
                array('css'=>array('jq.ui.core'))
            );
            parent::afterFilter();
        }
    }

    function index()
    {
        if($this->Access->isGuest()) {

            echo $this->render('elements','login');

            return;
        }

        $conditions = array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'PaidOrder.order_status != "Incomplete"'
            );

        $fields = array(
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE())' . ' AS `PaidOrder.daysToExpiration`',
                'PaidOrder.listing_id AS `Listing.listing_id`',
                'Listing.title AS `Listing.title`',
                'Listing.catid AS `Listing.cat_id`',
                'Listing.alias AS `Listing.slug`',
                '\'com_content\' AS `Listing.extension`',
                'Listing.state AS `Listing.state`',
                'Listing.publish_up AS `Listing.publish_up`',
                'Listing.publish_down AS `Listing.publish_down`',
                'Category.alias AS `Category.slug`'
            );

        $orders = $this->PaidOrder->findAll(array(
            'fields'=>$fields,
            'joins'=>array(
                'LEFT JOIN #__content AS Listing ON Listing.id = PaidOrder.listing_id',
                'LEFT JOIN #__categories AS Category ON Category.id = Listing.catid'
            ),
            'conditions'=>$conditions,
            'order'=>array('PaidOrder.order_created DESC'),
            'limit'=>$this->limit,
            'offset'=>$this->offset
        ));

        $total = $this->PaidOrder->findCount(array('conditions'=>$conditions));

        $this->set(array(
            'orders'=>$orders,
            'pagination'=>array(
                'total'=>$total
            )
        ));

        echo $this->render('paid_account', 'orders');
    }

    /**
    * Displays available plans for individual listings allowing users to place and order
    *
    */
    function _getOrderForm()
    {
        $user_id = $this->_user->id;

        if(!$user_id && $this->Access->isGuest()) {

            cmsFramework::noAccess();

            return;
        }

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        $listing_title = Sanitize::getString($this->params,'listing_title');

        $plan_type = Sanitize::getVar($this->params,'plan_type',null);

        $renewal = Sanitize::getInt($this->params,'renewal');

        $plan_id = Sanitize::getInt($this->params,'plan_id');

        $order_id = Sanitize::getInt($this->params,'order_id');

        // Check if listing has pending orders to avoid creating a duplicate
        $order_conditions = array(
            "PaidOrder.plan_type = " . $plan_type,
            "PaidOrder.listing_id = " . $listing_id,
            "DATE(PaidOrder.order_expires) > CURDATE()",
            "PaidOrder.order_status = 'Incomplete'"
        );

        $renewal and $order_id and $order_conditions = array("PaidOrder.order_id = " . $order_id);

        $order = $this->PaidOrder->findRow(array('conditions'=>$order_conditions));

        // If upgrade plans, then check if listing has a base plan that never expires. Otherwise don't show subscription plans
        $plans = $this->PaidPlan->getValidPlans($listing_id,$plan_type,$renewal);

        // If loaded fater the listing form is submitted, remove the hidden plans

        if(Sanitize::getString($this->params,'referrer',Sanitize::getString($this->data,'referrer')) == 'create')
        {
            foreach($plans['PaidPlan']['PlanType0']['PaymentType2'] AS $key=>$row)
            {
                if(isset($row['plan_array']) && (int) $row['plan_array']['submit_form'] == 0) {

                    unset($plans['PaidPlan']['PlanType0']['PaymentType2'][$key]);
                }
            }
        }

        if($renewal){ // Remove subscription and free plans from renewals page. Subscriptions are processed automatically

            // Comment the this line to allow renewals using free plans
            unset($plans['PaidPlan']['PlanType0']['PaymentType2'],$plans['PaidPlan']['PlanType1']['PaymentType2']);

            $subscription_order = $order['PaidOrder']['payment_type'] == 1;

            // This condition allows subscription plans to appear when renewing from a free or one time payment plan
            // Need to process it inmediately instead of as a renewal
            // if($subscription_order) {

                unset($plans['PaidPlan']['PlanType0']['PaymentType1'],$plans['PaidPlan']['PlanType1']['PaymentType1']);

            // }
        }

        $handlers_single = $this->PaidHandler->findAll(array(
            'conditions'=>array(
                'PaidHandler.state > 0'
            )
        ));

        $handlers_subs = $this->PaidHandler->findAll(array(
            'conditions'=>array(
                'PaidHandler.state > 0',
                'PaidHandler.subscriptions = 1'
            )
        ));

        # Terms of Service
        if(Sanitize::getInt($this->Config,'paid.tos')
            &&
            $tos_id = Sanitize::getInt($this->Config,'paid.tos_articleid')
        ) {

            $tos_article = $this->Article->findRow(array('conditions'=>array(
                    'Article.id = ' . $tos_id
                )));

            $this->set('tos_article',$tos_article);
        }

		# Enable / Disable handlers (points)
		foreach($handlers_single as &$handler)
        {
			$handler_class = Inflector::camelize($handler['PaidHandler']['plugin_file']).'Component';

        	S2App::import('Component',$handler['PaidHandler']['plugin_file']);

        	$Handler = new $handler_class();

        	$Handler->startup($this);

        	if(method_exists($handler_class, 'getPointBalance')) {

        		$balance = $Handler->getPointBalance($handler, $user_id);

        		$handler['balance'] = $balance;
			}
		}

        $this->set(array(
            'plan_type'=>$plan_type,
            'renewal'=>$renewal,
            'order_id'=>$order_id,
            'listing_id'=>$listing_id,
            'listing_title'=>$listing_title,
            'order'=>$order,
            'plans'=>$plans,
            'sel_plan_id'=>$plan_id,
            'handlers_single'=>$handlers_single,
            'handlers_subs'=>$handlers_subs
        ));

        return $this->render('paid_orders','form');
    }

    function _submit()
    {
        $response = array('success'=>false,'str'=>'');

        $user_id = $this->_user->id;

        if(!$user_id && $this->Access->isGuest()) {

            $response['str'] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $this->couponProcess = true; // Forces validation to return the discounted price instead of JSON

        $listing_id = $this->params['listing_id'] = Sanitize::getInt($this->data['PaidOrder'],'listing_id');

        $plan_id = $this->params['plan_id'] = Sanitize::getInt($this->data['PaidOrder'],'plan_id');

        $handler_id = $this->params['handler_id'] = Sanitize::getInt($this->data['PaidOrder'],'handler_id',0);

        $order_id = $this->params['order_id'] = Sanitize::getInt($this->data['PaidOrder'],'order_id',0);

        $coupon = $this->params['coupon'] = Sanitize::getString($this->data['PaidOrder'],'coupon_name');

        $track = '';

        if(!$plan = $this->PaidPlan->findRow(array('conditions'=>array('PaidPlan.plan_id = ' . $plan_id))))
        {
            return cmsFramework::jsonResponse($response);
        }

        $this->data['PaidOrder']['order_amount'] = $plan['PaidPlan']['plan_price']; // Required for coupon validation

        # Free plan limit validation
        if($plan['PaidPlan']['payment_type'] == 2 && Sanitize::getInt($plan['PaidPlan']['plan_array'],'trial_limit') > 0)
        {
            $trial_limit = $plan['PaidPlan']['plan_array']['trial_limit'];

            // Find the number of existing Complete orders with the same plan
            $used_trials = $this->PaidOrder->findCount(array(
                'conditions'=>array(
                    "PaidOrder.user_id = " . $user_id,
                    "PaidOrder.plan_id = " . $plan_id,
                    "PaidOrder.order_status = 'Complete'"
                )
            ));

           if($used_trials >= $trial_limit)
           {
                $response['str'] = 'PAID_FREE_LIMIT_REACHED';

                return cmsFramework::jsonResponse($response);
           }
        }

        # Find active orders for the same listing and plan type
        $curr_order_conditions = array(
            "PaidOrder.listing_id = " . $listing_id,
            "(DATE(PaidOrder.order_expires) > CURDATE() OR PaidOrder.order_never_expires = 1)",
            "PaidOrder.plan_type = " . $plan['PaidPlan']['plan_type']
        );

        $order_id and $curr_order_conditions[] = "PaidOrder.order_id = " . $order_id;

        $curr_order = $this->PaidOrder->findRow(array('conditions'=>$curr_order_conditions));

        $handler = $this->PaidHandler->findRow(array(
            'conditions'=>array('PaidHandler.handler_id = ' . $handler_id)
        ));

        $listing = $this->Listing->findRow(array(
            'conditions'=>array('Listing.id = ' . $listing_id)
        ),array('afterFind'));

        # Check if plan is valid for this listing
        $renewal = in_array($curr_order['PaidOrder']['payment_type'],array(0,2)) /* Free or Single payment plans */
                && $curr_order['PaidOrder']['order_status'] == "Complete"
                && $curr_order['PaidOrder']['order_active'];

        if(!$this->PaidPlan->validatePlan($listing,$plan,$renewal))
        {
            $response['str'] = 'PAID_INVALID_PLAN';

            return cmsFramework::jsonResponse($response);
        }

        if(($handler || $plan['PaidPlan']['payment_type'] == 2) && $plan && $listing)
        {
            $discounted_price = $this->validateCoupon(true);

            // Create new order array
            $order = $this->PaidOrder->makeOrder($plan,$listing,array('handler_id'=>$handler_id,'discounted_price'=>$discounted_price,'coupon'=>$coupon,'tax_rate'=>Sanitize::getVar($this->Config,'paid.tax',0)/100));

            // For incomplete orders
            $curr_order and !empty($curr_order['PaidOrder']['order_id']) and $curr_order['PaidOrder']['order_status'] == 'Incomplete' and $order['PaidOrder']['order_id'] = $curr_order['PaidOrder']['order_id'];

            // For renewals
            if($order_id && $curr_order && $renewal)
            {
                $order['PaidOrder']['order_renewal'] = $curr_order['PaidOrder']['order_expires'];

                $order['PaidOrder']['order_expires'] = $this->PaidOrder->getExpirationDate($plan['PaidPlan']['plan_array']['duration_period'],$plan['PaidPlan']['plan_array']['duration_number'], $curr_order['PaidOrder']['order_expires']);
            }

            if($this->PaidOrder->store($order) && $order_id)
            {
                // Add the order_id_renewal to the order that was renewed so the expiring soon message is no longer displayed
                $curr_order['PaidOrder']['order_id_renewal'] = $order['PaidOrder']['order_id'];

                $this->PaidOrder->store($curr_order);
            }

         /**
           * Tracking code
           */
           if($track = Sanitize::stripWhiteSpace(Sanitize::getVar($this->Config,'paid.track_order_submit','')))
           {
                $track = PaidlistingsComponent::trackingReplacements($track, $order);

                $track = html_entity_decode($track,ENT_QUOTES,cmsFramework::getCharset());
           }

            # Process free or zero amount (discounted) plans
            if($discounted_price === 0 || $plan['PaidPlan']['payment_type'] == 2)
            {
                if($this->Paidlistings->processFreeOrder($order))
                {
                    $PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper');

                    $order_id_complete = isset($order['insert_id']) ? $order['insert_id'] : $order['PaidOrder']['order_id'];

                    $url = str_replace('&amp;','&',$PaidRoutes->orderComplete(array('handler_id'=>$handler_id,'order_id'=>$order_id_complete)));

                    $response['success'] = true;

                    $response['url'] = $url;

                    $response['tracking'] = Sanitize::stripWhitespace($track);

                    return cmsFramework::jsonResponse($response);
                }
                else {

                    return cmsFramework::jsonResponse($response);
                }
            }

            $handler_class = Inflector::camelize($handler['PaidHandler']['plugin_file']).'Component';

            S2App::import('Component',$handler['PaidHandler']['plugin_file']);

            $Handler = new $handler_class();

            $Handler->startup($this);

            $response['success'] = true;

            $handlerResponse = $Handler->submit($handler, $plan, $listing, $order);

            if(strstr($handlerResponse,'<form')) {

                $response['form'] = $handlerResponse;
            }
            else {

                $response['url'] = $handlerResponse;
            }

            $response['tracking'] = $track;

            return cmsFramework::jsonResponse($response);
        }

        $response['str'] = 'PAID_DUPLICATE_ORDER';

        return cmsFramework::jsonResponse($response);
    }

    function complete()
    {
        // If it's a post request and the handler id is set
        if(!empty($this->params['form']))
        {
            $this->action = '_process';

            return $this->_process();
        }

        // For offline payments and other handlers that return via GET instead of POST
        if($order_id = Sanitize::getInt($this->params,'order_id'))
        {
            $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

            $order = $PaidOrder->findRow(array('conditions'=>array("PaidOrder.order_id = " . $order_id)));

            $handler = $this->PaidHandler->findRow(array('conditions'=>array('PaidHandler.handler_id = ' . $order['PaidOrder']['handler_id'])));

            /**
            * Special case for Paypal subscriptions which don't post back the transaction variables
            */
            if(Sanitize::getString($this->params,'auth') || Sanitize::getString($this->params,'tx')) {

                $order['PaidOrder']['order_status'] != 'Complete' and $order['PaidOrder']['order_status'] = "Processing";
            }

            /**
            * Special case for 2Checkout "Header Redirect" which returns via GET without sending IPN */
            if(Sanitize::getString($this->params,'sid')  && Sanitize::getString($this->params,'key'))
            {
                $this->action = '_process';

                $this->params['form'] = $this->params;

                return $this->_process();
            }

            $listing_id = Sanitize::getInt($order['PaidOrder'],'listing_id');

            $this->Listing->addStopAfterFindModel(array('Media','PaidOrder'));

            $listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)));

            $this->set(array(
                'User'=>$this->_user,
                'order'=>$order,
                'handler'=>$handler,
                'listing'=>$listing
                ));

            return $this->render('paid_orders','complete');
        }
    }

    function _process()
    {
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $handler_id = Sanitize::getInt($this->params,'handler_id');

        if(isset($this->params['form']) && $handler_id > 0)
        {
            if($handler = $this->PaidHandler->findRow(array(
                'conditions'=>array('PaidHandler.handler_id = ' . $handler_id)
            )))
            {
                $handler_class = Inflector::camelize($handler['PaidHandler']['plugin_file']).'Component';

                S2App::import('Component',$handler['PaidHandler']['plugin_file']);

                $PaidTxnLog->addNote("Modified by: Payment Handler");

                $Handler = new $handler_class();

                $Handler->startup($this);

                $order = $Handler->process($handler);

                $listing_id = Sanitize::getInt($order['PaidOrder'],'listing_id');

                $this->Listing->addStopAfterFindModel(array('Media','PaidOrder'));

                $listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)));

                $this->set(array(
                    'order'=>$order,
                    'handler'=>$handler,
                    'listing'=>$listing
                    ));

                return $this->render('paid_orders','complete');
            }
        }
    }

    function validateCoupon()
    {
        $response = array('success'=>false,'response_code'=>200);

        $user_id = $this->_user->id;

        if(!isset($this->data['PaidOrder'])) {

            $coupon_code = Sanitize::getString($this->params,'coupon');

            $plan_id = Sanitize::getInt($this->params,'plan_id');

            $listing_id = Sanitize::getInt($this->params,'listing_id');

            $price = Sanitize::getFloat($this->params,'order_amount');
        }
        else {

            $coupon_code = Sanitize::getString($this->data['PaidOrder'],'coupon_name');

            $plan_id = Sanitize::getInt($this->data['PaidOrder'],'plan_id');

            $listing_id = Sanitize::getInt($this->data['PaidOrder'],'listing_id');

            $price = Sanitize::getFloat($this->data['PaidOrder'],'order_amount');
        }

        $response_code = 200;

        # Do some input validation
        if($coupon_code == '')
        {
            if($this->couponProcess) {

                return null;
            }

            return $this->invalidCoupon($response_code);
        }

        $response_code++; //1

        # Get the coupon
        if(!$coupon = $this->PaidCoupon->findRow(array('conditions'=>array('PaidCoupon.coupon_name = ' . $this->PaidCoupon->Quote($coupon_code)))))
        {
            return $this->invalidCoupon($response_code);
        }

       $response_code++; //2

        # Check coupon dates
        /*----------------------*/
        if(($coupon['PaidCoupon']['coupon_starts']!='' && $coupon['PaidCoupon']['coupon_starts']!=NULL_DATE)
            || ($coupon['PaidCoupon']['coupon_ends']!='' && $coupon['PaidCoupon']['coupon_ends']!=NULL_DATE)
        ){
            $now = strtotime(_CURRENT_SERVER_TIME);
            if($coupon['PaidCoupon']['coupon_starts']!='' && $coupon['PaidCoupon']['coupon_ends']!=''
                && (
                    $now < strtotime($coupon['PaidCoupon']['coupon_starts'])
                    ||
                    $now > strtotime($coupon['PaidCoupon']['coupon_ends'])
                )
            )
            {
                return $this->invalidCoupon($response_code);
            }
            if($coupon['PaidCoupon']['coupon_starts']=='' && $coupon['PaidCoupon']['coupon_ends']!=''
                && $now > strtotime($coupon['PaidCoupon']['coupon_ends'])
            )
            {
                return $this->invalidCoupon($response_code);
            }
        }

       $response_code++; //3

        # Check renewals - based on previous orders for the same listing
        if($coupon['PaidCoupon']['coupon_renewals_only'])
        {
            if(!$listingHasOrder = $this->PaidOrder->findCount(array('conditions'=>array(
                'PaidOrder.listing_id = ' . $listing_id
            )))) {
                return $this->invalidCoupon($response_code);
            }
        }

       $response_code++; //4

        # Check user
        if(!empty($coupon['PaidCoupon']['coupon_users']) && !in_array($user_id,$coupon['PaidCoupon']['coupon_users']))
        {
            return $this->invalidCoupon($response_code);
        }

       $response_code++; //5

        # Check usage count
        if($coupon['PaidCoupon']['coupon_count'] > 0 &&  $coupon['PaidCoupon']['coupon_count_type'] == 'global')
        {
            $count = $this->PaidOrder->findCount(array('conditions'=>array(
                'PaidOrder.coupon_name = ' . $this->PaidOrder->Quote($coupon_code),
                'PaidOrder.order_status = "Complete"'
            )));
            if($count >= $coupon['PaidCoupon']['coupon_count'])
            {
                return $this->invalidCoupon($response_code);
            }
        }

       $response_code++; //6

        # Check plan
        if(!empty($coupon['PaidCoupon']['coupon_plans']) && !in_array($plan_id,$coupon['PaidCoupon']['coupon_plans']))
        {
            return $this->invalidCoupon($response_code);
        }

       $response_code++; //7

        # Check categories
        if(!empty($coupon['PaidCoupon']['coupon_categories']))
        {
            // Get listing cat id
            $query = "
                SELECT
                    catid
                FROM
                    #__content
                WHERE
                    id = " . $listing_id
            ;

            $cat_id = $this->PaidOrder->query($query,'loadResult');

            if($cat_id)
            {
                if(!in_array($cat_id,$coupon['PaidCoupon']['coupon_categories']))
                {
                    return $this->invalidCoupon($response_code++);
                }
            }
        }

        $discount = $coupon['PaidCoupon']['coupon_discount']/100;

        if($this->couponProcess)
        {  // Returned on order submit
            return $price-$price*$discount;
        }

        $tax_rate = Sanitize::getFloat($this->Config,'paid.tax')/100;

        $response['success'] = true;

        $response['jr-order-discount'] = '('.number_format($price*$discount,2).')';

        $response['jr-order-subtotal'] = number_format($price-$price*$discount,2);

        $response['jr-order-tax'] = number_format($tax_rate*($price-$price*$discount),2);

        $response['jr-order-total'] = number_format(($price-$price*$discount)+$tax_rate*($price-$price*$discount),2);

        return cmsFramework::jsonResponse($response);
    }

    function invalidCoupon($response_code)
    {
        $response = array('success'=>false);

        if($this->couponProcess)  {

            return cmsFramework::jsonResponse($response);
        }

        $response['response_code'] = $response_code;

        return cmsFramework::jsonResponse($response);
    }
}
