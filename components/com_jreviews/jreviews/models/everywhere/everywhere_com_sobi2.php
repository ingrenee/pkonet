<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComSobi2Model extends MyModel  {

	var $UI_name = 'Sobi2';

	var $name = 'Listing';

	var $useTable = '#__sobi2_item AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'itemid';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'publish_up';

	var $extension = 'com_sobi2';

	var $listingUrl = 'index.php?option=com_sobi2&amp;sobi2Task=sobi2Details&amp;catid=%s&amp;sobi2Id=%s&amp;Itemid=%s';

	var $cat_url_param = 'catid';

	var $fields = array(
		'Listing.itemid AS `Listing.listing_id`',
		'Listing.title AS `Listing.title`',
		'Listing.image AS `Listing.images`',
		"'com_sobi2' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.name AS `Category.title`',
		'ExtensionCategory.catid AS `Category.cat_id`',
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
    * Used for listings, not reviews
    */
    var $conditions = array(
        'Listing.published = 1',
        'Listing.approved = 1'
    );

	/**
	 * Used for detail listing page
	 */
	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.itemid AND Totals.extension = 'com_sobi2'",
        "LEFT JOIN #__sobi2_cat_items_relations AS ExtensionCategory ON Listing.itemid = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_sobi2'",
		'LEFT JOIN #__sobi2_categories AS Category ON JreviewsCategory.id = Category.catid',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        "LEFT JOIN #__users AS User ON User.id = Listing.owner"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		"RIGHT JOIN #__sobi2_item AS Listing ON Review.pid = Listing.itemid AND Listing.published = 1 AND Listing.approved = 1",
        "LEFT JOIN #__sobi2_cat_items_relations AS ExtensionCategory ON Review.pid = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_sobi2'",
		'LEFT JOIN #__sobi2_categories AS Category ON JreviewsCategory.id = Category.catid',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		// "RIGHT JOIN #__sobi2_item AS Listing ON Media.listing_id = Listing.itemid AND Listing.published = 1 AND Listing.approved = 1",
        "LEFT JOIN #__sobi2_cat_items_relations AS ExtensionCategory ON Media.listing_id = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_sobi2'",
		'LEFT JOIN #__sobi2_categories AS Category ON JreviewsCategory.id = Category.catid',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

    public static $joinListingState = array(
        'INNER JOIN #__sobi2_item AS Listing ON Listing.itemid = %s AND Listing.published = 1 AND Listing.approved = 1'
        );

	function __construct() {

		parent::__construct();

		$this->tag = __t("SOBI2_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`"; // Comment this line to hide the tag from the output
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_sobi2' . _DS . 'sobi2.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Category']['cat_id'],$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.owner AS user_id, User.name, User.email
            FROM
                #__sobi2_item AS Listing
            LEFT JOIN
                #__users AS User ON Listing.owner = User.id
            WHERE
                Listing.itemid = " . (int) ($result_id);

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

		foreach($results AS $key=>$result)
        {
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

			if($images != '') {
				if ( @file_exists("images/com_sobi2/clients/" . $images) ) {
				    $imagePath = "images/com_sobi2/clients/" . $images;
				} else {
				    $imagePath = "images/com_sobi2/clients/" . $images;
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

		$query = "SELECT Component.catid AS value,Component.name as text"
		. "\n FROM #__sobi2_categories AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.catid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.catid NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.name ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.catid AS `Component.cat_id`,Component.name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__sobi2_categories AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.catid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
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