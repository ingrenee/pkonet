<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PredefinedReplyModel extends MyModel {

	var $name = 'PredefinedReply';

	var $useTable = '#__jreviews_predefined_replies AS PredefinedReply';

	var $primaryKey = 'PredefinedReply.reply_id';

	var $realKey = 'reply_id';

}
