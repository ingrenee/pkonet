<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
* https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_IPNandPDTVariables
*/

class Handler2checkoutComponent extends S2Component
{
    var $period = array('days'=>'D','weeks'=>'W','months'=>'M','years'=>'Y');

      /**
    * Handler configuration array
    *
    * @var array
    */
    var $handler = array();

    function startup(&$controller)
    {
        $this->c = &$controller;
    }

    /**
    * Generates post data to be sent to Paypal
    *
    */
    function submit($handler, $plan, $listing, $order)
    {
        $c = &$this->c;

        $user_id = $this->c->_user->id;

        $username = $this->c->_user->username;

        $handler = $handler['PaidHandler'];

        $handler_settings = & $handler['settings'];

        $plan = $plan['PaidPlan'];

        $plan_array = $plan['plan_array'];

        $listing = $listing['Listing'];

        $handler_post_url = "https://www.2checkout.com/2co/buyer/purchase";

        $PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

        $formData = array (
            'id_type'=>1,
            'c_prod'=>$plan['plan_id'],
            'c_name'=>$plan['plan_name'].":".$listing['title'],
            'c_description'=>$plan['plan_name'].":".$listing['title'],
            'c_price'=>$order['PaidOrder']['order_amount'],
            'c_tangible'=>'N',
            'sid'=>$handler_settings['sid'],
            'cart_order_id'=>$order['PaidOrder']['order_id'],
            'merchant_oder_id'=>$order['PaidOrder']['order_id'],
            'total'=>$order['PaidOrder']['order_amount'],
            'x_receipt_link_url'=>$PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'order_id'=>$order['PaidOrder']['order_id'])), // Order id must be excluded so it's processed on return
            'fixed'=>'Y',
            'demo'=>($handler['state'] == 2 ? 'Y' : 'N'),
            'pay_method'=>'CC',
            'uid'=>$user_id,
            'uname'=>$username
        );

        //build the form
        $form = "<form id='jr_handlerPostForm' name='jr_handlerPostForm' action='$handler_post_url' method='post'>";
        foreach($formData AS $key => $val){
            $form .= "\n <input type='hidden' name='$key' value='$val' />";
        }
        $form .= "\n </form>";

        return $form;
    }

    /**
    * Processes IPN response from Paypal and updates the transaction
    *
    */
     function process($handler)
     {
        $c = &$this->c;
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');
        $handler_settings = & $handler['PaidHandler']['settings'];
        $posts = "";
        $post_vars = Sanitize::getVar($c->params,'form',array());

        if(empty($post_vars)) return;

        # Get order info from db
        $order_id = $post_vars['cart_id'];
        $order = $PaidOrder->findRow(array(
            'conditions'=>array('PaidOrder.order_id = '  . $order_id)
        ));

        // Verify if this is really a 2checkout post
        $secret_key = $handler_settings['secret_word'];
        $sid = $handler_settings['sid'];
        $order_no = $handler['PaidHandler']['state'] == 2 ? 1 : $post_vars['order_number'];
        $string_to_hash = $secret_key . $sid . $order_no . $post_vars['total'];
        $check_key = strtoupper(md5($string_to_hash));

        $order['txn_id'] = $order_no; // Make txn_id available in other methods where only the order is passed as a parameter

        if (strcmp($check_key,$post_vars['key']) != 0)
        {
            // Invalid transaction detected
            $PaidTxnLog->addNote("Payment status: INVALID return.");
            $PaidOrder->updateOrder($order,array('order_status'=>'Failed'));
            $PaidTxnLog->save($order,$post_vars,$order_no);
            $order['PaidOrder']['order_status'] = 'Failed';
        }
        elseif ($post_vars['credit_card_processed'] == 'Y')
        {
            // Proceed with db operations
            $order['PaidOrder']['order_status'] = 'Complete';
            $c->Paidlistings->processSuccessfulOrder($order);
            $PaidTxnLog->save($order,$post_vars,$order_no);
        }
        else
        { // Payment is still pending
            $PaidTxnLog->addNote("Payment status: Pending.");
            $PaidOrder->updateOrder($order,array('order_status'=>'Pending'));
            $PaidTxnLog->save($order,$post_vars,$order_no);
        }

        return $order;
    }
}
