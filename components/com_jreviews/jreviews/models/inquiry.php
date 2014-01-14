<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InquiryModel extends MyModel  {

	var $name = 'Inquiry';

	var $useTable = '#__jreviews_inquiry AS Inquiry';

	var $primaryKey = 'Inquiry.inquiry_id';

	var $fields = array('Inquiry.*');

}
