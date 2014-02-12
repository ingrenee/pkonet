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

class HandlerAuthorizeNetComponent extends S2Component
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
        $c = & $this->c;

        $user_id =  $this->c->_user->id;

        $handler = $handler['PaidHandler'];

        $handler_settings = & $handler['settings'];

        $plan = $plan['PaidPlan'];

        $plan_array = $plan['plan_array'];

        $listing = $listing['Listing'];

        $login_id = $handler_settings['api_login'];

        $transaction_key = $handler_settings['transaction_key'];

        // Real or sandbox transaction
        switch($handler_settings['test_gateway'])
        {
            case 1: // Sandbox
                $handler_post_url = "https://test.authorize.net/gateway/transact.dll"; //Test
            break;
            default: // For real
                $handler_post_url = "https://secure.authorize.net/gateway/transact.dll"; //Live
            break;
        }

        // a sequence number is randomly generated
        $sequence    = rand(1, 1000);
        // a timestamp is generated
        $timeStamp    = time ();
        $fingerprint = hash_hmac("md5", $login_id . "^" . $sequence . "^" . $timeStamp . "^" . $order['PaidOrder']['order_amount'] . "^", $transaction_key);

        $PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

        $formData = array (
            // Additional fields can be added here as outlined in the SIM integration guide
            // at: http://developer.authorize.net
            'x_cust_id'=>$user_id,
            'x_login'=>$login_id,
            'x_amount'=>$order['PaidOrder']['order_amount'],
            'x_description'=>$plan['plan_name'].":".$listing['title'],
            'x_invoice_num'=>$order['PaidOrder']['order_id'],
            'x_fp_sequence'=>$sequence,
            'x_fp_timestamp'=>$timeStamp,
            'x_fp_hash'=>$fingerprint,
//            'x_test_request'=>$handler['state'] == 2 ? true : false,
            'x_show_form'=>'PAYMENT_FORM',
            'x_relay_response'=>true,
            'x_relay_url'=>$PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'menu_id'=>0)) // Menu set to 0 because the session is lost on return and MyAccount menu could be set to registered users only
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
        $order_id = $post_vars['x_invoice_num'];
        $order = $PaidOrder->findRow(array(
            'conditions'=>array('PaidOrder.order_id = '  . $order_id)
        ));

        // Verify if this is really a 2checkout post
        $secret_key = Sanitize::getVar($handler_settings,'secret_word',null);
        $login_id = $handler_settings['api_login'];
        $transaction_id = $post_vars['x_trans_id'];
        $order_amount = $post_vars['x_amount'];
        $string_to_hash = $secret_key . $login_id. $transaction_id . $order_amount;
        $check_key = strtoupper(md5($string_to_hash));

        if (strcmp($check_key,$post_vars['x_MD5_Hash']) != 0)
        {
            // Invalid transaction detected
            $PaidTxnLog->addNote("Payment status: INVALID return.");
            $PaidOrder->updateOrder($order,array('order_status'=>'Failed'));
            $PaidTxnLog->save($order,$post_vars,$post_vars['x_trans_id']);
            ?>
            <h1><?php __t("Transaction invalid");?></h1>
            <p><?php __t("We have detected an invalid attempt to bypass the payment system. If you feel this is mistake please contact us.");?></p>
            <?php
            return;
        }

        switch($post_vars['x_response_code'])
        {
            case '1': // Approved
//                $PaidOrder->updateOrder($order,array('order_status'=>'Complete'));
                // Proceed with db operations
                $order['PaidOrder']['order_status'] = 'Complete';
                $c->Paidlistings->processSuccessfulOrder($order);
                $PaidTxnLog->save($order,$post_vars,$post_vars['x_trans_id']);
            break;
            case '2':
            // break left out on purpose
            case '3':
                // Invalid transaction detected
                $PaidTxnLog->addNote("Payment status: Declined/Error.");
                $PaidTxnLog->addNote("Reason Code: " . Sanitize::getString($post_vars,'x_response_reason_code'));
                $PaidTxnLog->addNote("Reason: " . Sanitize::getString($post_vars,'x_response_reason_text'));
                $PaidOrder->updateOrder($order,array('order_status'=>'Failed'));
                $PaidTxnLog->save($order,$post_vars,$post_vars['x_trans_id']);
            break;
            case '4': // Held for Review
                $PaidTxnLog->addNote("Payment status: Pending.");
                $PaidTxnLog->addNote("Reason Code: " . Sanitize::getString($post_vars,'x_response_reason_code'));
                $PaidTxnLog->addNote("Reason: " . Sanitize::getString($post_vars,'x_response_reason_text'));
                $PaidOrder->updateOrder($order,array('order_status'=>'Pending'));
                $PaidTxnLog->save($order,$post_vars,$post_vars['x_trans_id']);
            break;
        }

        return $order;
    }
}