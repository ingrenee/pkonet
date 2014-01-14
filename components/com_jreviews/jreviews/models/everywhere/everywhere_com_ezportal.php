<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2010 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComEzportalModel extends MyModel  {

	var $UI_name = 'EZ Portal for Joomla 3.0x';

    var $extension = 'com_ezportal';

    var $extension_alias = 'com_ezportal';

	var $name = 'Listing'; // Model association

    /**
    * Listing setup vars
    */

	var $useTable = '#__ezportal AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

    /**
    * Create date column. Used in listings module for most recent ordering
    */
    var $dateKey = 'listdate';

    /**
    * Category admin setup vars - Also update $fields, $joins and $joinsReviews arrays below
    */
    var $catIdColumn = 'id';

    var $catTitleColumn = 'title';

    var $catTable = 'ezportal_catg';

	/**
	 * Used for listing module - latest listings ordering
	 */

	var $listingUrl = 'index.php?option=com_ezportal&view=ezportal&id=%s&Itemid=%s';

	var $cat_url_param = null;

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'CONCAT(Listing.seller_name," - ",Listing.seller_company) AS `Listing.title`',
		'Listing.cid AS `Listing.cat_id`',
		'Listing.seller_image AS `Listing.images`',
        'Listing.cid AS `Category.cat_id`',
		"'com_ezportal' AS `Listing.extension`",
        'Category.title `Category.title`',
		'Criteria.id AS `Criteria.criteria_id`',
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
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_ezportal'",
		'LEFT JOIN #__ezportal_catg AS Category ON Listing.cid = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.cid AND JreviewsCategory.`option` = 'com_ezportal'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
		"LEFT JOIN #__users AS User ON User.id = Listing.uid"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__ezportal AS Listing ON Review.pid = Listing.id',
		'LEFT JOIN #__ezportal_catg AS Category ON Listing.cid = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.cid AND JreviewsCategory.`option` = 'com_ezportal'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $conditions = array();

	var $group = array('Listing.id');

	function __construct() {
		parent::__construct();

		// Used in MyReviews page to differentiate from other component reviews
		$this->tag = __t("EZPORTAL_PROFILE_TAG",true);

		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_ezportal' . _DS . 'ezportal.php');
	}

	function listingUrl($result) {
		return sprintf($this->listingUrl,$result['Listing']['listing_id'],$result['Listing']['menu_id']);
	}

	// Used to check whether reviews can be posted by listing uids
	function getListingOwner($result_id)
    {
		$query = "
            SELECT
                Listing.uid, User.name, User.email
            FROM
                #__ezportal AS Listing
            LEFT JOIN
                #__users AS User ON Listing.uid = User.id
            WHERE
                Listing.id = " . (int) ($result_id)
        ;
		$this->_db->setQuery($query);
        return current($this->_db->loadAssocList());
	}

	function afterFind($results) {

        if (empty($results))
        {
            return $results;
        }

		# Find Itemid for component
		$Menu = ClassRegistry::getClass('MenuModel');
		$menu_id = $Menu->getComponentMenuId($this->extension);

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

			if($images != '' && @file_exists("images/ezportal/avatar/" . $images)) {
				   $imagePath = "images/ezportal/avatar/" . $images;
			} else {
				// Put a noimage path here?
                   $imagePath = "";
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
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension_alias}'";
		$this->_db->setQuery($query);
		$exclude = $this->_db->loadResultArray();
		$exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.{$this->catIdColumn} AS value,Component.{$this->catTitleColumn} as text"
		. "\n FROM #__{$this->catTable} AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.{$this->catIdColumn} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.{$this->catTitleColumn} ASC"
		;

		$this->_db->setQuery($query);

		return $this->_db->loadAssocList();
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.{$this->catIdColumn} AS `Component.cat_id`,Component.{$this->catTitleColumn} as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__{$this->catTable} AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.{$this->catIdColumn} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n LIMIT $this->offset,$this->limit"
		;
		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();
		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension_alias}'"
		;
		$this->_db->setQuery($query);
		$count = $this->_db->loadResult();

		return array('rows'=>$results,'count'=>$count);
	}
}