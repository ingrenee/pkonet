<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsNotificationsComponent extends S2Component {

    var $notifyModel = null;
    var $validObserverModels = array(
        'PaidOrder',
        );
    var $send_email = true;

    function startup(&$controller)
    {
        $this->c = & $controller;

        if(method_exists($controller,'getNotifyModel'))
        {
            $this->notifyModel = & $controller->getNotifyModel();

            if(method_exists($controller,'getNotifyModel')
                && in_array($this->notifyModel->name,$this->validObserverModels))
            {
                $this->notifyModel->addObserver('plgAfterSave',$this);
            }
        }
    }

    function plgAfterSave(&$model)
    {
       $order_status = Sanitize::getString($model->data['PaidOrder'],'order_status');

       if(
            (defined("MCV_FRAMEWORK_ADMIN") && ($this->c->name != 'admin_paidlistings_orders' || $this->c->action != '_save'))
            ||
            Configure::read('PaidListings.email')
            ||
            empty($order_status)
            ||
            in_array($order_status,array('Failed','Cancelled','Fraud'))
       ){
           $this->send_email = false;
       }
       if(
            (
                $this->c->name == 'paidlistings_orders'
                ||
                ($this->c->name == 'admin_paidlistings_orders' && $this->c->action == '_save')
            )
            &&
            $this->send_email)
        {
            # For admin side notification for offline payments, make sure the offline payment checkbox was checked!
            if($this->c->name == 'admin_paidlistings_orders' && $this->c->action == '_save')
            {
                if(!$model->data['offline_approval']) return;
            }

            Configure::write('PaidListings.email',true); // Send emails once

            $order = $model->data['PaidOrder'];

            if(!is_array($order['plan_info']))
            {
                $order['plan_info'] = json_decode($order['plan_info'],true);
            }
            if(!is_array($order['listing_info']))
            {
                $order['listing_info'] = json_decode($order['listing_info'],true);
            }

            /* These are the payment handlers submit and process methods */
            switch($this->c->action)
            {
                case '_submit':
                    $this->pullNotifyTrigger('user_order_placed',$order);
                    $this->pullNotifyTrigger('admin_order_placed',$order);
                break;
                case '_process':
                case 'process':
                case '_save': // Admin side save
                    $order_status == "Complete" and $this->pullNotifyTrigger('user_order_processed',$order);
                    $order_status == "Complete" and $this->pullNotifyTrigger('admin_order_processed',$order);
                break;
            }
        }
    }

    /**
    * Queries all orders about to expire in the specified period and emails users
    *
    * @param mixed $controller
    */
    function checkExpiringOrders(&$controller)
    {
        $this->c = &$controller;

        $order_ids_sent1 = $order_ids_sent2 = array();

        $count1 = $count2 = 1000;

        $expires1 = Sanitize::getInt($controller->Config,'paid.notify_expiration1');

        $expires2 = Sanitize::getInt($controller->Config,'paid.notify_expiration2');

        $expiration_period1 = Sanitize::getInt($controller->Config,'paid.notify_expiration1_days',10);

        $expiration_period2 = Sanitize::getInt($controller->Config,'paid.notify_expiration2_days',5);

        $max_emails = Sanitize::getInt($controller->Config,'paid.notify_max_emails',250);

        $last_count = Sanitize::getInt($controller->Config,'paid.notify_last_count');

        # Run check
        if($expires1 || $expires2){

            S2App::import('Model',array('paid_order','paid_email'),'jreviews');

            $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

            $this->c->PaidEmail = ClassRegistry::getClass('PaidEmailModel');

            if($expires1)
            {
                # Check first notification orders
                $queryArray = array('conditions'=>array(
                        "DATEDIFF(DATE(PaidOrder.order_expires),CURDATE()) BETWEEN ".($expiration_period2+1)." AND " . ($expiration_period1),
                        "PaidOrder.order_active = 1",
                        "PaidOrder.order_notify1 = 0",
                        "(PaidOrder.payment_type = 0 OR PaidOrder.payment_type = 2)", // Only care about one time payment and free plans
                        "PaidOrder.order_never_expires = 0",
                        "PaidOrder.order_id_renewal = 0" /* order has not been renewed */
                ));

                if($count = $PaidOrder->findCount($queryArray))
                {
                    $queryArray['limit'] = $max_emails;

                    if($orders = $PaidOrder->findAll($queryArray))
                    {
                        foreach($orders AS $order)
                        {
                            if($this->pullNotifyTrigger('user_order_expiration1',$order))
                            {
                                $order_ids_sent1[] = $order['PaidOrder']['order_id'];
                            }
                        }

                        if(!empty($order_ids_sent1))
                        {
                            // Mark orders as sent to avoid sending emails again
                            $PaidOrder->updateOrderNotification($order_ids_sent1,'order_notify1');
                        }
                    }
                }
            }

            # Check final notification orders if max emails per period have not been exceeded
            if($expires2 && $count1 < $max_emails)
            {
                # Check first notification orders
                $queryArray = array('conditions'=>array(
                        "DATEDIFF(DATE(PaidOrder.order_expires),CURDATE()) <= " . $expiration_period2,
                        "PaidOrder.order_active = 1",
                        "PaidOrder.order_notify2 = 0",
                        "(PaidOrder.payment_type = 0 OR PaidOrder.payment_type = 2)", // Only care about one time payment plans and free
                        "PaidOrder.order_never_expires = 0",
                        "PaidOrder.order_id_renewal = 0" /* order has not been renewed */
                ));

                if($PaidOrder->findCount($queryArray))
                {
                    $queryArray['limit'] = $max_emails;

                    if($orders = $PaidOrder->findAll($queryArray))
                    {
                        foreach($orders AS $order)
                        {
                            if($this->pullNotifyTrigger('user_order_expiration2',$order))
                            {
                                $order_ids_sent2[] = $order['PaidOrder']['order_id'];
                            }
                        }
                        if(!empty($order_ids_sent2))
                        {
                            // Mark orders as sent to avoid sending emails again
                            $PaidOrder->updateOrderNotification($order_ids_sent2,'order_notify2');
                        }
                    }
                }
            }
        }
    }

    function pullNotifyTrigger($trigger, $order)
    {
        S2App::import('Helper','time','jreviews');

        $Time = ClassRegistry::getClass('TimeHelper');

        $tags = array();

        $bcc = '';

        isset($order['PaidOrder']) and $order = array_shift($order);

        // Get email template
        $email = $this->c->PaidEmail->findRow(array('conditions'=>array('PaidEmail.state = 1','PaidEmail.trigger = "'.$trigger.'"')));

        if(!$email) return false;

        $subject = $email['PaidEmail']['subject'];

        $body = $email['PaidEmail']['body'];

        $tags = array(
            '{order_id}',
            '{order_expires}',
            '{order_amount}',
            '{listing_title}',
            '{user_name}',
            '{listing_url}',
            '{plan_name}',
            '{plan_description}',
            '{txn_array}',
            '{order_array}',
            '{site_url}'
        );

        $values = array(
             $order['order_id'],
             _NULL_DATE == $order['order_expires'] ? __t("Never",true) : $Time->nice($order['order_expires']),
             number_format($order['order_amount'],2,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) . ' ' .$this->c->Config->{'paid.currency'},
             $order['listing_info']['listing_title'],
             $order['listing_info']['owner_name'],
             $order['listing_info']['listing_url'],
             $order['plan_info']['plan_name'],
             $order['plan_info']['plan_array']['description'],
             print_r(Sanitize::getVar($this->c->params,'form',''),true),
             print_r($order,true),
             WWW_ROOT
        );

        $subject = str_replace($tags,$values,$subject);

        $body = str_replace($tags,$values,$body);

        if(strstr($trigger,'user'))
        {
            S2App::import('Model','user','jreviews');

            $User = ClassRegistry::getClass('UserModel');

            $fields = $User->fields;

            $User->modelUnbind(array('*'));

            $to = $User->findOne(array('fields'=>array('User.email'),'conditions'=>'User.id = ' . (int)$order['user_id']));

            $User->fields = $fields;

            $bcc = $email['PaidEmail']['admin_emails'];
        }
        else {

            $to = $email['PaidEmail']['admin_emails'];
        }

        return $this->sendMail($to, $bcc, $subject, $body, true);
    }

    function sendMail($to, $bcc, $subject, $body, $html = true)
    {
        $result = false;

        trim($to) != '' and !is_array($to) and $to = array_filter(explode("\n",$to));

        if($to)
        {
            $mail = cmsFramework::getMail($html);

            foreach($to AS $address)
            {
                $mail->AddAddress($address);
            }

            trim($bcc) != '' and !is_array($bcc) and $bcc = array_filter(explode("\n",$bcc));

            if($bcc)
            {
                foreach($bcc AS $address)
                {
                    $mail->AddBCC($address);
                }
            }

            $mail->Subject = $subject;

            $mail->Body = strlen($body) != strlen(strip_tags($body)) /*check for html tags*/ ? $body : nl2br($body);

            $result = $mail->Send();

            if(!$result)
            {

               appLogMessage(array(
                       "Admin listing message was not sent.",
                       "Mailer error: " . $mail->ErrorInfo),
                       'notifications'
                   );
            }

            unset($mail);

            return $result;
        }
    }
}