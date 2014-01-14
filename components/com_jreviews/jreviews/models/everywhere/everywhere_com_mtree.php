<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComMtreeModel extends MyModel  {

	var $UI_name = 'Mosets Tree';

	var $name = 'Listing';

	var $useTable = '#__mt_links AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'link_id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'publish_up';

	var $extension = 'com_mtree';

	var $listingUrl = 'index.php?option=com_mtree&amp;task=viewlink&amp;link_id=%s&amp;Itemid=%s';

	var $cat_url_param = 'cat_id';

	var $fields = array(
		'Listing.link_id AS `Listing.listing_id`',
		'Listing.link_name AS `Listing.title`',
//		'Images.filename AS `Listing.images`',
		"'com_mtree' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.cat_name AS `Category.title`',
		'ExtensionCategory.cat_id AS `Category.cat_id`',
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',
		'Criteria.state AS `Criteria.state`',
        'Criteria.config AS `ListingType.config`',
        'User.id AS `User.user_id`',
        'User.name AS `User.name`',
        'User.username AS `User.username`',
        'User.email AS `User.email`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`'
	);

	/**
	 * Used for detail listing page - not used for 3rd party components
	 */
	var $joins = array(
//		"LEFT JOIN #__mt_images AS Images ON Listing.link_id = Images.link_id",
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.link_id AND Totals.extension = 'com_mtree'",
		"LEFT JOIN #__mt_cl AS ExtensionCategory ON Listing.link_id = ExtensionCategory.link_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.cat_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_mtree'",
		'LEFT JOIN #__mt_cats AS Category ON JreviewsCategory.id = Category.cat_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        "LEFT JOIN #__users AS User ON User.id = Listing.user_id"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
//		"LEFT JOIN #__mt_images AS Images ON Review.pid = Images.link_id",
		"LEFT JOIN #__mt_cl AS ExtensionCategory ON Review.pid = ExtensionCategory.link_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.cat_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_mtree'",
		'LEFT JOIN #__mt_cats AS Category ON JreviewsCategory.id = Category.cat_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
//		"LEFT JOIN #__mt_images AS Images ON Review.pid = Images.link_id",
		"LEFT JOIN #__mt_cl AS ExtensionCategory ON Media.listing_id = ExtensionCategory.link_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.cat_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_mtree'",
		'LEFT JOIN #__mt_cats AS Category ON JreviewsCategory.id = Category.cat_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);


	function __construct() {
		parent::__construct();

		$this->tag = __t("MTREE_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_mtree' . _DS . 'mtree.php');
	}


	function listingUrl($listing)
    {
		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);
	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.user_id AS user_id, User.name, User.email
            FROM
                #__mt_links AS Listing
            LEFT JOIN
                #__users AS User ON Listing.user_id = User.id
            WHERE
                Listing.link_id = " . (int) ($result_id);

        $this->_db->setQuery($query);

        return current($this->_db->loadAssocList());
    }

	function getImage($listing_id) {
		$query = "SELECT Image.filename AS image FROM #__mt_images AS Image WHERE Image.link_id = " . $listing_id . " LIMIT 1";
		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	function afterFind($results) {

        if (empty($results))
        {
            return $results;
        }

		# Find Itemid for component
		$Menu = ClassRegistry::getClass('MenuModel');

		$menu_id = $Menu->getComponentMenuId($this->extension);

		foreach($results AS $key=>$result) {

			// Process component menu id
			$results[$key][$this->name]['menu_id'] = $menu_id;

			// Process listing url
			$results[$key][$this->name]['url'] = $this->listingUrl($results[$key]);

			// Process criteria
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
			$images = $this->getImage($result['Listing']['listing_id']);
			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				if ( @file_exists("components/com_mtree/img/listings/o/" . $images) ) {
				    $imagePath = "components/com_mtree/img/listings/o/" . $images;
				} else {
				    $imagePath = "components/com_mtree/img/listings/o/" . $images;
				}
			} else {
				// Put a noimage path here?
				$imagePath = '';//"components/com_mtree/img/listings/o/" . $images;
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'basepath'=>true
			);

		}
		return $results;
	}

	/**
	 * Returns the current page category for category auto-detect functionality in modules
	 */
	function catUrlParam(){
		return $this->cat_url_param;
	}

	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.cat_id AS value,Component.cat_name as text"
		. "\n FROM #__mt_cats AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.cat_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.cat_id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.cat_name ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.cat_id AS `Component.cat_id`,Component.cat_name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__mt_cats AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.cat_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
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