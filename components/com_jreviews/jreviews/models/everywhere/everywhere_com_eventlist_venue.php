<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComEventlistVenueModel extends MyModel  {

	var $UI_name = 'EventList Venues';

	var $name = 'Listing';

	var $useTable = '#__eventlist_venues AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_eventlist_venue';

	var $listingUrl = 'index.php?view=venueevents&id=%s:%s&option=com_eventlist&Itemid=%s';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.venue AS `Listing.title`',
		'Listing.alias AS `Listing.slug`',
		'1 AS `Listing.cat_id`',
		'Listing.locimage AS `Listing.images`',
		'\'com_eventlist_venue\' AS `Listing.extension`',
		'criteria'=>'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',
		'Criteria.state AS `Criteria.state`',
        'Criteria.config AS `ListingType.config`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`'
	);

	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_eventlist_venue'",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON 1 = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_eventlist_venue'",
		"LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		"LEFT JOIN #__users AS User ON User.id = Listing.created_by"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__eventlist_events AS Listing ON Review.pid = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON 1 = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_eventlist_venue'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		// 'LEFT JOIN #__eventlist_events AS Listing ON Media.listing_id = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON 1 = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_eventlist_venue'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $conditions = array();

	var $group = array('Listing.id');

    public static $joinListingState = array(
        'INNER JOIN #__eventlist_venues AS Listing ON Listing.id = %s AND Listing.published = 1'
        );

	function __construct() {

		parent::__construct();

		// Used in MyReviews page to differentiate from other component reviews
		$this->tag = __t("EVENTLIST_VENUE_TAG",true);

		// Uncomment line below to show tag in My Reviews page
		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_eventlist' . _DS . 'eventlist.php');
	}

	function listingUrl($result) {
		$result['Listing']['slug'] = preg_replace("/([\x80-\xFF])/e", "chr(0xC0|ord('\\1')>>6).chr(0x80|ord('\\1')&0x3F)", $result['Listing']['slug']);
		return sprintf($this->listingUrl,$result['Listing']['listing_id'],$result['Listing']['slug'],$result['Listing']['menu_id']);
	}

	// Used to check whether reviews can be posted by listing owners
	function getListingOwner($result_id)
    {
		$query = "
            SELECT
                Listing.created_by AS user_id, User.name, User.email
            FROM
                #__eventlist_venues AS Listing
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id
            WHERE
                Listing.id = " . (int) ($result_id);
		$this->_db->setQuery($query);
		appLogMessage($this->_db->getErrorMsg(),'owner_listing');
        return current($this->_db->loadAssocList());

	}

	function afterFind($results) {

        if (empty($results))
        {
            return $results;
        }

		# Find Itemid for component
		$Menu = ClassRegistry::getClass('MenuModel');
		$menu_id = $Menu->getComponentMenuId('com_eventlist');

		# Reformat image and criteria info
		foreach ($results AS $key=>$result) {

			// Process component menu id
			$results[$key][$this->name]['menu_id'] = $menu_id;

			$results[$key][$this->name]['url'] = $this->listingUrl($results[$key]);

			if(isset($result['Criteria']['criteria']) && $result['Criteria']['criteria'] != '') {
				$results[$key]['Criteria']['criteria'] = explode("\n",$result['Criteria']['criteria']);
			}

			if(isset($result['Criteria']['tooltips']) && $result['Criteria']['tooltips'] != '') {
				$results[$key]['Criteria']['tooltips'] = explode("\n",$result['Criteria']['tooltips']);
			}

			if(isset($result['Criteria']['weights']) && $result['Criteria']['weights'] != '') {
				$results[$key]['Criteria']['weights'] = explode("\n",$result['Criteria']['weights']);
			}

			// Process images
			$images = $result['Listing']['images'];
			unset($results[$key]['Listing']['images']);
			$results[$key]['Listing']['images'] = array();

			if($images != '' && @file_exists("images/eventlist/venues/" . $images)) {
				   $imagePath = "images/eventlist/venues/" . $images;
			} else {
				// Put a noimage path here?
				$imagePath = '';
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'basepath'=>true
			);
		}

		return $results;
	}


	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$categories = array(array('value'=>1,'text'=>'All venues'));

		return $categories;
	}

	function getUsedCategories()
	{
		$query = "SELECT JreviewCategory.id AS `Component.cat_id`,'All Venues' as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n WHERE JreviewCategory.id = 1 AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LIMIT $this->offset,$this->limit"
		;
		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();

		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
		;
		$this->_db->setQuery($query);
		$count = $this->_db->loadResult();

		return array('rows'=>$results,'count'=>$count);
	}
}
