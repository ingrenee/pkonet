<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsEmailsController extends MyController
{
    var $uses = array('paid_email');

    var $helpers = array('html','form');

    var $components = array('config');

    var $autoRender = false;

    var $autoLayout = false;


    function beforeFilter()
    {
        parent::beforeFilter();
    }

    function index()
    {
        $admin_emails = $this->PaidEmail->findAll(array('conditions'=>'PaidEmail.trigger LIKE "admin_%"'));

        $user_emails = $this->PaidEmail->findAll(array('conditions'=>'PaidEmail.trigger LIKE "user_%"'));

        $this->set(array(
            'admins'=>$admin_emails
            ,'users'=>$user_emails
        ));

        return $this->render('paidlistings_emails','index');
    }

    function edit()
    {
        $email_id = Sanitize::getInt($this->params,'id');

        $email = $this->PaidEmail->findRow(array(
            'conditions'=>array('PaidEmail.email_id = ' . $email_id)
        ));

        $this->set('email',$email);

        return $this->render('paidlistings_emails','edit');
    }

    function _save()
    {
        $response = array('success'=>false);

        // Index page
        if(isset($this->data['emails']))
        {
            foreach($this->data['emails'] AS $key=>$data)
            {
                $this->PaidEmail->store($data);
            }

            return '';
        }
        // Email template thing
        else {

            $this->PaidEmail->store($this->data['__raw']);

            $response['success'] = true;

            return cmsFramework::jsonResponse($response);
        }
    }
}
