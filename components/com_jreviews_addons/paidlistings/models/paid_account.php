<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidAccountModel extends MyModel  {

    var $name = 'PaidAccount';

    var $useTable = '#__jreviews_paid_account AS `PaidAccount`';

    var $primaryKey = 'PaidAccount.account_id';

    var $realKey = 'account_id';

    var $fields = array('PaidAccount.*');

}