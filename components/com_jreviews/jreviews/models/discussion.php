<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DiscussionModel extends MyModel {

	var $name = 'Discussion';

	var $useTable = '#__jreviews_discussions AS Discussion';

	var $primaryKey = 'Discussion.discussion_id';

	var $realKey = 'discussion_id';

    var $fields = array(
        'Discussion.discussion_id AS `Discussion.discussion_id`',
        'Discussion.type AS `Discussion.type`',
        'Discussion.parent_post_id AS `Discussion.parent_post_id`',
        'Discussion.review_id AS `Discussion.review_id`',
        'Discussion.user_id AS `Discussion.user_id`',
        'Discussion.name AS `Discussion.name`',
        'Discussion.username AS `Discussion.username`',
        'Discussion.ipaddress AS `Discussion.ipaddress`',
        'Discussion.text AS `Discussion.text`',
        'Discussion.created AS `Discussion.created`',
        'Discussion.modified AS `Discussion.modified`',
        'Discussion.approved AS `Discussion.approved`',
		'User.id As `User.user_id`',
        'Discussion.email As `User.email`',
        'CASE WHEN CHAR_LENGTH(User.name) THEN User.name ELSE Discussion.name END AS `User.name`',
        'CASE WHEN CHAR_LENGTH(User.username) THEN User.username ELSE Discussion.username END AS `User.username`'
    );

    var $joins = array(
        'user'=>'LEFT JOIN #__users AS User ON Discussion.user_id = User.id'
    );

    function afterFind($results)
    {
        if (empty($results)) {
            return $results;
        }

        if(!is_numeric(key($results))){ // There's only one row
            $results = array($results);
        }

        if(!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) {
            # Add Community info to results array
            if(class_exists('CommunityModel')) {
                $Community = ClassRegistry::getClass('CommunityModel');
                $results = $Community->addProfileInfo($results, 'User', 'user_id');
            }
        }

        S2App::import('Model','review','jreviews');

        $Review = ClassRegistry::getClass('ReviewModel');

        $results = $Review->addReviewInfo($results,'Discussion','review_id');

		# Find com_content posts and complete the listing info for them
		$listing_ids = array();

		// Extract the listing ids
		foreach($results AS $result) {

        	$result['Listing']['extension'] == 'com_content' and $listing_ids[$result['Listing']['listing_id']] = $result['Listing']['listing_id'];
		}

		// Load the additional listing data
		if(!empty($listing_ids)) {

        	if(!class_exists('EverywhereComContentModel')) {
				S2App::import('Model','everywhere_com_content','jreviews');
			}

        	$ComContentModel = ClassRegistry::getClass('EverywhereComContentModel');

        	$listings = $ComContentModel->getListingById($listing_ids);
		}

        // Merge listing data back into the discussions array
		foreach($results AS $key=>$result) {

        	if($result['Listing']['extension']=='com_content') {

        		$results[$key]['Listing'] = array_merge($result['Listing'],$listings[$result['Listing']['listing_id']]['Listing']);

        		$results[$key]['Category'] = $listings[$result['Listing']['listing_id']]['Category'];

                $results[$key]['ListingType'] = $listings[$result['Listing']['listing_id']]['ListingType'];
			}
		}

//        if(!is_numeric(key($results))){ // There's only one row
//            $results = array_shift($results);
//        }

        return $results;
    }

    function afterSave($status)
    {
        clearCache('','__data');

        clearCache('','views');

        if($status && !isset($this->data['Discussion']['modified']))  // It's a new comment
        {
            switch($this->data['Discussion']['type'])
            {
                case 'review':
                    if($this->data['Discussion']['approved'] == 1) // Increment post count when post is approved
                    {
                        // Update post count in review table
                        S2App::import('Model','review','jreviews');
                        $Review = ClassRegistry::getClass('ReviewModel');
                        $Review->updatePostCount($this->data['Discussion']['review_id'],1);
                    }
                break;
            }
        }
    }

    function beforeDelete($key, $values, $condition)
    {
        // Make all children comments orphans by setting parent column to zero
        $sql = "UPDATE "
                . $this->useTable
            . " SET
                parent_post_id = 0
            WHERE
                parent_post_id = {$values}
        ";
        $this->_db->setQuery($sql);
        $this->_db->query();

        // Get the post type to update the post count in the related table
        $this->fields = array('Discussion.*');

        // Make post variable available to afterDelete and plg callback methods
        $callbacks = array();

        $this->data['Discussion'] = $this->post = $this->findRow(array('conditions'=>array('Discussion.discussion_id = ' . (int)$values)),$callbacks);
    }

    function afterDelete($key, $values, $condition)
    {
        switch($this->post['Discussion']['type'])
        {
            case 'review':
                  S2App::import('Model','review','jreviews');
                  $Review = ClassRegistry::getClass('ReviewModel');
                  $Review->updatePostCount($this->post['Discussion']['review_id'],-1);
            break;
        }
    }

    function getPostOwner($post_id)
    {
        $query = "SELECT user_id FROM #__jreviews_discussions WHERE discussion_id = " . $post_id;
        $this->_db->setQuery($query);
        return $this->_db->loadResult();
    }

    function processSorting($selected = null)
    {
        $order = '';

        switch($selected)
        {
          case 'rdate':
            $order = 'Discussion.created DESC';
            break;
          case 'date':
            $order = 'Discussion.created ASC';
            break;
          default:
            $order = 'Discussion.created ASC';
            break;
        }

        return $order;
    }
}