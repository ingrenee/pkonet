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

class HandlerOfflineComponent extends S2Component
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

        $handler = $handler['PaidHandler'];

        $handler_settings = & $handler['settings'];

        $PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper','jreviews');

        // Redirect to payment instructions
        $complete_url = $PaidRoutes->orderComplete(array('handler_id'=>$handler['handler_id'],'order_id'=>$order['PaidOrder']['order_id'],'offline'=>1));

        $complete_url = str_replace('&amp;','&',$complete_url);

        return $complete_url;
    }
}