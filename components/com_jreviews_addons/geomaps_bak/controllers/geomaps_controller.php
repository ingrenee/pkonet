<?php
/**
 * GeoMaps Addon for JReviews
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class GeomapsController extends MyController
{
    var $uses = array('menu','field','criteria','media');

    var $helpers = array('html','form','custom_fields','routes','media','rating');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        $this->Access->init($this->Config);
        parent::beforeFilter();
    }

    function _getMarkerTooltip()
    {
        $listing_id = Sanitize::getInt($this->params,'listing_id');
        $listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)));
        $this->set('listing',$listing);
        return $this->render('geomaps','map_infowindow');
    }

    function _showMap()
    {
        return $this->Geomaps->showMap();
    }

    function _saveGeoData()
    {
        return $this->Geomaps->saveGeocodePopup($this->data);
    }
}