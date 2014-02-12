<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UserModel extends MyModel  {

	var $name = 'User';

	var $useTable = '#__users AS `User`';

	var $primaryKey = 'User.id';

	var $realKey = 'id';

	var $fields = array('*');
}
