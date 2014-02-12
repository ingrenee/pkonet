<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class OwnerReplyModel extends MyModel {

	var $name = 'OwnerReply';

	var $useTable = '#__jreviews_comments AS OwnerReply';

	var $primaryKey = 'OwnerReply.review_id';

	var $realKey = 'id';

	var $fields = array(
        'OwnerReply.id AS `Review.review_id`',
        'OwnerReply.pid AS `Review.listing_id`',
        'OwnerReply.`mode` AS `Review.extension`',
        'OwnerReply.title AS `Review.title`',
        'OwnerReply.id AS `OwnerReply.review_id`',
        'OwnerReply.owner_reply_text AS `OwnerReply.owner_reply_text`',
        'OwnerReply.owner_reply_approved AS `OwnerReply.owner_reply_approved`',
        'OwnerReply.owner_reply_created AS `OwnerReply.owner_reply_created`',
        'OwnerReply.owner_reply_note AS `OwnerReply.owner_reply_note`'
    );

	var $joins = array();

	var $conditions = array();

	var $group = array();
}
