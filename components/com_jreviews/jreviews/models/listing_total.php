<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 20010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingTotalModel extends MyModel {
		
	var $name = 'ListingTotal';
	
	var $useTable = '#__jreviews_listing_totals AS ListingTotal';

	var $primaryKey = 'ListingTotal.listing_id';
	
	var $realKey = 'listing_id';

}
