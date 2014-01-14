<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsAccountsController extends MyController
{
    var $uses = array('menu','paid_account');

    var $components = array('config');

    var $helpers = array('html','form');

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

        $account = $this->PaidAccount->findRow(array(
            'conditions'=>'PaidAccount.user_id = ' . (int) $this->_user->id
        ));

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
            'User'=>$this->_user
        ));

        return $this->render('paid_account', 'user');
    }

    function _save()
    {
        $response = array('success'=>false);

        $user_id = (int)$this->_user->id;

        if($user_id > 0)
        {
            $fields = $this->PaidAccount->fields;

            $this->PaidAccount->fields = array('PaidAccount.account_id');

            $account_id = $this->PaidAccount->findOne(array(
                'conditions'=>'PaidAccount.user_id = ' . (int) $this->_user->id
            ));

            if($account_id)
            {
                $this->data['PaidAccount']['account_id'] = $account_id;
            }

            $this->data['PaidAccount']['user_id'] = $user_id;

            if($this->PaidAccount->store($this->data))
            {
                $response['success'] = true;

            }
        }

        return cmsFramework::jsonResponse($response);
    }
}