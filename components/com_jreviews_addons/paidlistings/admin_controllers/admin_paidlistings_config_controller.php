<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsConfigController extends MyController
{
    var $uses = array('menu');

    var $helpers = array('html','form','admin/admin_settings');

    var $components = array('config','access');

    var $autoRender = false;

    var $autoLayout = false;

    /**
    * Controller specific vars
    *
    */

    function beforeFilter()
    {
        $this->Access->init($this->Config);
        parent::beforeFilter();
    }

    /**
    * Save GeoMaps configuration
    *
    */
    function _save()
    {
        # Process tracking code
        $this->data['Config']['paid.track_listing_form'] = htmlentities(Sanitize::getVar($this->data['__raw']['Config'],'paid.track_listing_form'),ENT_QUOTES,cmsFramework::getCharset());
        $this->data['Config']['paid.track_listing_submit'] = htmlentities(Sanitize::getVar($this->data['__raw']['Config'],'paid.track_listing_submit'),ENT_QUOTES,cmsFramework::getCharset());
        $this->data['Config']['paid.track_order_form'] = htmlentities(Sanitize::getVar($this->data['__raw']['Config'],'paid.track_order_form'),ENT_QUOTES,cmsFramework::getCharset());
        $this->data['Config']['paid.track_order_submit'] = htmlentities(Sanitize::getVar($this->data['__raw']['Config'],'paid.track_order_submit'),ENT_QUOTES,cmsFramework::getCharset());
        $this->data['Config']['paid.track_order_complete'] = htmlentities(Sanitize::getVar($this->data['__raw']['Config'],'paid.track_order_complete'),ENT_QUOTES,cmsFramework::getCharset());

        $this->Config->store($this->data['Config']);
    }

    function index()
    {
        return $this->render('paidlistings_config','index');
    }
}
