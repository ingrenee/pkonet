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

	var $useTable = '#__vm_product AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'product_id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'cdate';

	var $extension = 'com_virtuemart';

	var $listingUrl = 'index.php?option=com_virtuemart&amp;page=shop.product_details&amp;product_id=%s&amp;Itemid=%s';

	var $cat_url_param = 'category_id';

	var $fields = array(
		'Listing.product_id AS `Listing.listing_id`',
		'Listing.product_name AS `Listing.title`',
		'Listing.product_full_image AS `Listing.images`',
        'Listing.product_s_desc AS `Listing.metadesc`',
        'Listing.product_publish AS `Listing.state`',
		"'com_virtuemart' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.category_name AS `Category.title`',
		'ProductCategory.category_id AS `Category.cat_id`',
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
	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.product_id AND Totals.extension = 'com_virtuemart'",
		"LEFT JOIN #__vm_product_category_xref AS ProductCategory ON Listing.product_id = ProductCategory.product_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
		'LEFT JOIN #__vm_category AS Category ON JreviewsCategory.id = Category.category_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		"LEFT JOIN #__vm_product_category_xref AS ProductCategory ON Review.pid = ProductCategory.product_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
		'LEFT JOIN #__vm_category AS Category ON JreviewsCategory.id = Category.category_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		"LEFT JOIN #__vm_product_category_xref AS ProductCategory ON Review.pid = ProductCategory.product_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ProductCategory.category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_virtuemart'",
		'LEFT JOIN #__vm_category AS Category ON JreviewsCategory.id = Category.category_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $groupReviews = array(
		"Review.id"
	);

    public static $joinListingState = array(
        'INNER JOIN #__vm_product AS Listing ON Listing.product_id = %s AND Listing.product_publish = 1'
        );

	function __construct() {

		parent::__construct();

		$this->tag = __t("VIRTUEMART_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

	static public function exists() {

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

            # Config overrides
            if(isset($result['ListingType'])) {
                $results[$key]['ListingType']['config'] = json_decode($result['ListingType']['config'],true);
                if(isset($results[$key]['ListingType']['config']['relatedlistings'])) {
                    foreach($results[$key]['ListingType']['config']['relatedlistings'] AS $rel_key=>$rel_row) {
                        isset($rel_row['criteria']) and $results[$key]['ListingType']['config']['relatedlistings'][$rel_key]['criteria'] = implode(',',$rel_row['criteria']);
                    }
                }
            }

			// Process images
			$images = $result['Listing']['images'];
			unset($results[$key]['Listing']['images']);
			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				if ( @file_exists("components/com_virtuemart/shop_image/product/" . $images) ) {
				    $imagePath = "components/com_virtuemart/shop_image/product/" . $images;
				} else {
				    $imagePath = "components/com_virtuemart/shop_image/product/" . $images;
				}
			} else {
				// Put a noimage path here?
				$imagePath = '';//"components/com_virtuemart/shop_image/product/" . $images;
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
	 * This can be used to add post review save actions, like synching with another table
	 */
	function afterSave($status) {}

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

		$query = "SELECT Component.category_id AS value,Component.category_name as text"
		. "\n FROM #__vm_category AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.category_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.category_id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.category_name ASC"
		;

		return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.category_id AS `Component.cat_id`,Component.category_name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__vm_category AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.category_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n LIMIT $this->offset,$this->limit"
		;

		$results = $this->query($query,'loadObjectList');
		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
		;

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}

}
