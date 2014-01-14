<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageFacebookComponent extends S2Component {
	
	public $service = 'Facebook.com';
	
	private $c;

	function __construct(&$controller) 
	{
		parent::__construct();
		$this->c = & $controller;
	}
	
	function getStorageUrl($media_type, $object = '') 
	{
		return 'http://www.facebook.com/v/'.$object;		
	}
}