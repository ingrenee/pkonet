<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComVirtuemartModel extends MyModel  {

	var $UI_name = 'Virtuemart';

	var $name = 'Listing';

	var $useTable;

	var $catTable;

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'virtuemart_product_id';

	var $locale = 'en_gb';
	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'cdate';

	var $extension = 'com_virtuemart';

	var $listingUrl = 'index.php?option=com_virtuemart&amp;page=shop.product_details&amp;virtuemart_product_id=%s&amp;Itemid=%s';

	var $cat_url_param = 'category_id';

	var $fields = array(
		'Listing.virtuemart_product_id AS `Listing.listing_id`',
		'Listing.product_name AS `Listing.title`',
		'Media.file_url_thumb AS `Listing.images`',
        'Listing.product_s_desc AS `Listing.metadesc`',
		"'com_virtuemart' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.category_name AS `Category.title`',
		'ProductCategory.virtuemart_category_id AS `Category.cat_id`',
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

	/**
	 * Used for detail listing page - not used for 3rd party components
	 */
	var $joins = array();

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array();

	var $joinsMedia = array();

	var $group = array('Listing.virtuemart_product_id');

	var $groupReviews = array(
		"Review.id"
	);

	function __construct() {

		parent::__construct();

		$this->catTable = '#__virtuemart_categories_'.$this->locale;

		$this->useTable = '#__virtuemart_products_'.$this->locale.' AS Listing';

		$this->joins = array(
	        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.virtuemart_product_id AND Totals.extension = 'com_virtuemart'",
			"LEFT JOIN #__virtuemart_product_categories AS ProductCategory ON Listing.virtuemart_product_id = ProductCategory.virtuemart_product_id",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.virtuemart_category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
			"LEFT JOIN {$this->catTable} AS Category ON JreviewsCategory.id = Category.virtuemart_category_id",
			'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
			'LEFT JOIN #__virtuemart_product_medias AS ProductMedia ON ProductMedia.virtuemart_product_id = Listing.virtuemart_product_id AND ProductMedia.ordering = 1',
			'LEFT JOIN #__virtuemart_medias AS Media ON Media.virtuemart_media_id = ProductMedia.virtuemart_media_id'
		);

		/**
		 * Used to complete the listing information for reviews based on the Review.pid
		 */
		$this->joinsReviews = array(
			"LEFT JOIN #__virtuemart_product_categories AS ProductCategory ON Review.pid = ProductCategory.virtuemart_product_id",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.virtuemart_category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
			"LEFT JOIN {$this->catTable} AS Category ON JreviewsCategory.id = Category.virtuemart_category_id",
			'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
			'LEFT JOIN #__virtuemart_product_medias AS ProductMedia ON ProductMedia.virtuemart_product_id = Listing.virtuemart_product_id AND ProductMedia.ordering = 1',
			'LEFT JOIN #__virtuemart_medias AS Media ON Media.virtuemart_media_id = ProductMedia.virtuemart_media_id'
		);

		$this->joinsMedia = array(
			"LEFT JOIN #__virtuemart_product_categories AS ProductCategory ON Media.listing_id = ProductCategory.virtuemart_product_id",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.virtuemart_category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
			"LEFT JOIN {$this->catTable} AS Category ON JreviewsCategory.id = Category.virtuemart_category_id",
			'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
			'LEFT JOIN #__virtuemart_product_medias AS ProductMedia ON ProductMedia.virtuemart_product_id = Listing.virtuemart_product_id AND ProductMedia.ordering = 1',
			'LEFT JOIN #__virtuemart_medias AS Media ON Media.virtuemart_media_id = ProductMedia.virtuemart_media_id'
		);

		$this->tag = __t("VIRTUEMART_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_virtuemart' . _DS . 'virtuemart.php');
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
			$images = $result['Listing']['images'];

			unset($results[$key]['Listing']['images']);

			$results[$key]['Listing']['images'] = array();

			if($images != '' && @file_exists(PATH_ROOT . $images)) {
				$imagePath = $images;
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

		$this->_db->setQuery($query);

       	$exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "
			SELECT
				Component.virtuemart_category_id AS value,
				CONCAT(REPEAT('- ', IF(ParentCategory.category_parent_id>0,1,0)), Component.category_name) AS text
			FROM
				{$this->catTable} AS Component
			LEFT JOIN
				#__virtuemart_category_categories AS ParentCategory ON ParentCategory.id = Component.virtuemart_category_id
			LEFT JOIN
				#__virtuemart_categories AS Category ON Category.virtuemart_category_id = Component.virtuemart_category_id
			LEFT JOIN
				#__jreviews_categories AS JreviewCategory ON Component.virtuemart_category_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'
			". ($exclude != '' ? " WHERE Component.virtuemart_category_id NOT IN ($exclude)" : '') . "
			ORDER BY
				Category.ordering ASC"
		;

		return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "
			SELECT
				Component.virtuemart_category_id AS `Component.cat_id`,
				CONCAT(REPEAT('- ', IF(ParentCategory.category_parent_id>0,1,0)), Component.category_name) AS `Component.cat_title`,
				Criteria.title AS `Component.criteria_title`
			FROM
				{$this->catTable} AS Component
			LEFT JOIN
				#__virtuemart_category_categories AS ParentCategory ON ParentCategory.id = Component.virtuemart_category_id
			LEFT JOIN
				#__virtuemart_categories AS Category ON Category.virtuemart_category_id = Component.virtuemart_category_id
			INNER JOIN
				#__jreviews_categories AS JreviewCategory ON Component.virtuemart_category_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'
			LEFT JOIN
				#__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id
			LIMIT $this->offset,$this->limit
		";

		$results = $this->query($query,'loadObjectList');

		$results = $this->__reformatArray($results);

		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "
			SELECT
				count(JreviewCategory.id)
			FROM
				#__jreviews_categories AS JreviewCategory
			WHERE
				JreviewCategory.`option` = '{$this->extension}'
		";

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}

}