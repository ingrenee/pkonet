<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidEmailModel extends MyModel  {

    var $name = 'PaidEmail';

    var $useTable = '#__jreviews_paid_emails AS `PaidEmail`';

    var $primaryKey = 'PaidEmail.email_id';

    var $realKey = 'email_id';

    var $fields = array('PaidEmail.*');

    var $order = array('PaidEmail.ordering ASC');
}