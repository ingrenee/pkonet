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

class HandlerPaypalComponent extends S2Component
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

        // One time or subscription plan
        if ($plan['payment_type'] == 1) {

            $payment_type = "_xclick-subscriptions";
        }
        else {

            $payment_type = "_xclick";
        }

        // Real or sandbox transaction
        switch($handler['state'])
        {
            case 1: // For real

                $handler_email = $handler_settings['handler_email']; //PayPal account email

                $handler_post_url = "https://www.paypal.com/cgi-bin/webscr"; //Live
            break;

            case 2: // Sandbox

                $handler_email = $handler_settings['sandbox_email']; //Sandbox account email

                $handler_post_url = "https://www.sandbox.paypal.com/cgi-bin/webscr"; //Test
            break;
        }

        $PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

        $formData = array(
            'cmd'           =>  $payment_type,
            'charset'       => 'utf-8',
            'business'      =>  $handler_email,
            'no_shipping'   =>  1,
            'handling'      =>  0,
            'currency_code' =>  $c->Config->{'paid.currency'},
            'item_name'     =>  $plan['plan_name'].":".$listing['title'],
            'item_number'   =>  $plan['plan_id'],
            'cancel_return' =>  $PaidRoutes->myaccount(),
            'return'        =>  $PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'order_id'=>$order['PaidOrder']['order_id'])),
            'notify_url'    =>  WWW_ROOT. 'index.php?option=com_jreviews&url=paidlistings_orders/_process&handler_id='.$handler['handler_id'].'&component=tmpl&format=raw',
            'custom'        =>  htmlentities(json_encode(array(
                                    'user_id'=>$user_id,
                                    'order_id'=>$order['PaidOrder']['order_id']))
                                )
        );

        $lc = Sanitize::getString($handler_settings,'lc');

        if($lc == '') {

            $lc = $this->languageCode();
        }

        if($lc != '') {

            $formData['lc'] = $lc;
        }

        if ($plan['payment_type'] == 1) {

            $formData['a3'] = $order['PaidOrder']['order_amount'];

            $formData['p3'] = $plan_array['duration_number'];

            $formData['t3'] = $this->period[$plan_array['duration_period']];

            $formData['src'] = 1;

            $formData['sra'] = 1;
        }
        else {

            $formData['amount'] = $order['PaidOrder']['order_amount'];
        }

        // build the form
        $form = "<form id='jr-form-paid-handler' name='jr-form-paid-handler' action='$handler_post_url' method='post' accept-charset='utf-8'>";


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
        // Instantiate models
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $posts = '';
        $res = '';
        $this->handler = $handler;
        $post_vars = Sanitize::getVar($c->params,'form',array());

        if(empty($post_vars)) return;

        isset($post_vars['merchant_return_link']) and Configure::write('PaidListings.email',true); // Don't send email on return to site because it's already triggered via IPN post - This is a Paypal thing

        // Verify if this is really a Paypal post
        $req = 'cmd=_notify-validate';

        foreach($post_vars AS $key => $value)
        {
            if(!in_array($key,array('CONTEXT','myAllTextSubmitID','cmd','form_charset')))
            {
                if (@get_magic_quotes_gpc()) {
                    $value = stripslashes($value);
                }
                $req .= "&$key=".urlencode($value);
                $posts .= "$key => $value\r\n";    // Used in file log
            }
        }

        // post back to PayPal system to validate
        if ($post_vars['test_ipn'] == 1) {
            $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        } else {
            $url = 'https://www.paypal.com/cgi-bin/webscr';
        }

        $ch = curl_init();    // Starts the curl handler
        curl_setopt($ch, CURLOPT_URL,$url); // Sets the paypal address for curl
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1); // Set curl to send data using post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // Returns result to a variable instead of echoing
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req); // Add the request parameters to the post
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Sets a time limit for curl in seconds (do not set too low)
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Enabling certificate verification makes the curl call fail on some servers
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $res = curl_exec($ch); // run the curl process (and return the result to $result
        curl_close($ch);

        // Extract custom fields
        $custom = json_decode($post_vars['custom'],true);
        $order_id = $custom['order_id']; // Local transaction id
        $order = $PaidOrder->findRow(array(
            'conditions'=>array('PaidOrder.order_id = '  . $order_id)
        ));

        if (!preg_match("/VERIFIED/", $res)) {
        // if (strcmp (trim($res), "VERIFIED") == 0) {

            $PaidTxnLog->addNote("Payment status: INVALID return.");

            $PaidOrder->updateOrder($order,array('order_status'=>'Failed'));

            $PaidTxnLog->save($order,$post_vars,Sanitize::getString($post_vars,'txn_id'));

            die();
        }

        $order['txn_id'] = Sanitize::getString($post_vars,'txn_id'); // Make txn_id available in other methods where only the order is passed as a parameter

        switch(Sanitize::getString($post_vars,'txn_type'))
        {
            case 'web_accept':

                $success = $this->processOneTimePlan($order,$post_vars);

                break;

            case 'subscr_signup':
            case 'subscr_payment':
            case 'subscr_cancel':
            case 'subscr_failed':
            case 'recurring_payment_suspended_due_to_max_failed_payment':

                $success = $this->processSubscriptionPlan($order,$post_vars);

                break;
            default:

                $success = false;

                break;
        }

        if(!isset($post_vars['txn_type']))
        {
            switch($post_vars['reason_code'])
            {
                case 'chargeback':
                    $PaidOrder->updateOrder($order,array('order_status'=>'Fraud'));
                    $c->Paidlistings->processFailedOrder($order);
                break;
            }
            $PaidTxnLog->addNote("Reason code: {$post_vars['reason_code']}.");
        }

        $PaidTxnLog->save($order, $post_vars, Sanitize::getString($post_vars,'txn_id'), $success);

        return $order;
     }

    /**
     * Process One Time payament plan
     * @param array $post_vars
     */
    function processOneTimePlan(&$order,$post_vars)
    {
        $c = &$this->c;
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        #  Begin txn validation
        // Assign posted variables to local variables
        $payment_status = Sanitize::getString($post_vars,'payment_status');
        $receiver_email = $post_vars['receiver_email'];
        $pending_reason = Sanitize::getString($post_vars,'pending_reason',__t("N/A",true));

        // Extract custom fields
        $custom = json_decode($post_vars['custom'],true);
        $user_id = $custom['user_id'];
        $order_id = $custom['order_id']; // Local order id

        // Check the payment_status is Completed
        if ($payment_status == "Completed")
        {
            $result = $this->validateTxn($order,$post_vars);
        }
        else
        {
            $PaidTxnLog->addNote("Pending reason: {$pending_reason}.");
            $result = false;
        }
        #  End txn validation

        if ($result)
        { // Valid
            $order['PaidOrder']['order_status'] = 'Complete';
            $c->Paidlistings->processSuccessfulOrder($order);
        }
        else
        { // Invalid
            switch($post_vars['payment_status'])
            {
                case 'Pending':
                    $order_status = 'Pending';
                break;
                case 'Denied':
                    $order_status = 'Failed';
                break;
                case 'Reversed':
                    $order_status = 'Fraud';
                break;
                default:
                    $order_status = 'Pending';
                break;
            }
            $PaidTxnLog->addNote("Payment status: {$order_status}.");
            $PaidTxnLog->addNote("Order active: 0");
            $PaidOrder->updateOrder($order,array('order_status'=>$order_status,'order_active'=>0));
        }
        return $result;
    }

    /**
     * Process Subscription Payment plan
     * @param array $post_vars
     */
    function processSubscriptionPlan(&$order,$post_vars)
    {
        $c = & $this->c;

        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        # Begin txn validation
        // Assign posted variables to local variables
        $txn_type =  Sanitize::getString($post_vars,'txn_type');

        $payment_status = Sanitize::getString($post_vars,'payment_status');

        $pending_reason = Sanitize::getString($post_vars,'pending_reason',__t("N/A",true));

        // Extract custom fields
        $custom = json_decode($post_vars['custom'],true);

        $user_id = $custom['user_id'];

        $order_id = $custom['order_id']; // Local order id

        // Check the payment_status is Completed
        if ($txn_type == 'subscr_payment' && $payment_status == "Completed")
        {
            $result = $this->validateTxn($order,$post_vars);

            if($result) $order = $this->checkSubscriptionRenewal($order);
        }
        elseif($txn_type == 'subscr_signup')
        {
            $order_status = 'Processing';

            $PaidTxnLog->addNote("Payment status: " . __t("New subscription",true));

            return false; // This isn't a payment notification
        }
        elseif(Sanitize::getString($post_vars,'pending_reason'))
        {
            $PaidTxnLog->addNote("Pending reason: {$pending_reason}");

            $PaidTxnLog->addNote("Txn type: {$txn_type}");

            $result = false;
        }
        else /*if($txn_type == 'subscr_cancel')*/
        {
            $payment_status = "Cancelled";

            $PaidTxnLog->addNote("Payment status: " . __t("Cancelled",true));

            $result = false;
        }
        # End txn validation

        if ($result)
        {
            $order_status = 'Complete';

            $order['PaidOrder']['order_status'] = $order_status;

            $c->Paidlistings->processSuccessfulOrder($order);
        }
        else
        {
            switch($payment_status)
            {
                case 'Pending':
                    $order_status = 'Pending';
                break;
                case 'Denied':
                    $order_status = 'Failed';
                break;
                case 'Reversed':
                    $order_status = 'Fraud';
                break;
                case 'Cancelled':
                    $order_status = 'Cancelled';
                    break;
                break;
                default:
                    $order_status = 'Pending';
                break;
            }

            $PaidTxnLog->addNote("Payment status: {$order_status}");

            $PaidTxnLog->addNote("Order active: 0");

            $PaidOrder->updateOrder($order,array('order_status'=>$order_status,'order_active'=>0));
        }
        return $result;

    }

    /**
    * If this is a subscription renewal we want to extend the expiration date of the current order
    * With Paypal, a subscription renewal will be identified by order status
    * @param mixed $order
    */
    function checkSubscriptionRenewal($order)
    {
        $plan = & $order['PaidOrder']['plan_info']['plan_array'];
        if($order['PaidOrder']['order_status'] == 'Complete') /* The order had already been procesed in the past */
        {
                $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');
                $PaidTxnLog->addNote("Subscription Renewal");
                $new_expiration = PaidOrderModel::getExpirationDate($plan['duration_period'],$plan['duration_number'],$order['PaidOrder']['order_expires']);
                $order['PaidOrder']['order_expires'] = $new_expiration;
                $plan['moderation'] = 0; // If it was published before no need to moderate it again
        }

        return $order;
    }


    function validateTxn(&$order,&$post_vars)
    {
        $c = &$this->c;
        $validation = true;
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $receiver_email = $post_vars['receiver_email'];
        $payment_amount = Sanitize::getVar($post_vars,'mc_amount3') ? $post_vars['mc_amount3'] : Sanitize::getVar($post_vars,'payment_gross');
        $payment_amount == '' and $payment_amount = Sanitize::getVar($post_vars,'mc_gross');
        $payment_currency = $post_vars['mc_currency'];

        // Extract custom fields
        $custom = json_decode($post_vars['custom'],true);
        $user_id = $custom['user_id'];
        $order_id = $custom['order_id']; // Local order id

        // check that receiver_email is your Primary PayPal email
        if (Sanitize::getInt($post_vars,'test_ipn') == 1) {
            $primary_email = $this->handler['PaidHandler']['settings']['sandbox_email'];
        } else {
            $primary_email = $this->handler['PaidHandler']['settings']['handler_email'];
        }

        if ($receiver_email != $primary_email)
        {
            $PaidTxnLog->addNote("Not Primary PayPal email: {$receiver_email}.");
            $validation = false;
        }

          // check that payment_amount/payment_currency are correct
        if ($order['PaidOrder']['order_amount'] != $payment_amount) {
            $PaidTxnLog->addNote("Payment amount is incorrect. Charged {$order['PaidOrder']['plan_info']['plan_price']}, but got {$payment_amount}.");
            $validation = false;
        }

        if (trim($c->Config->{'paid.currency'}) != $payment_currency) {
            $PaidTxnLog->addNote("Payment currency is incorrect. Charged in ".$c->Config->{'paid.currency'}.", but got {$payment_currency}.");
            $validation = false;
        }

        if (!($order['PaidOrder']['user_id'] == $user_id)) {
            $PaidTxnLog->addNote("User details are incorrect. Sent {$order['PaidOrder']['user_id']}, but got {$user_id}.");
            $validation = false;
        }

        return $validation;
    }

    function languageCode()
    {
        $locale = cmsFramework::getLocale();
        $lang = substr($locale,0,2);
        $country = strtolower(substr($locale,3,2)) ;

        $langArray = array(
            'en'=>'us',
            'es'=>'es',
            'fr'=>'fr',
            'de'=>'de',
            'it'=>'it',
            'jp'=>'jp'
        );

        $countryArray = array(
            'us'=>'us',
            'es'=>'es',
            'fr'=>'fr',
            'de'=>'de',
            'it'=>'it',
            'jp'=>'jp',
            'cn'=>'cn',
            'gb'=>'gb',
            'au'=>'au'
        );

        if(in_array($lang,$langArray)) {
            return $langArray[$lang];
        }

        if(in_array($country,$countryArray)) {
            return $countryArray[$country];
        }

        return false;
    }
}
