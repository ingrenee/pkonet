<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComZooModel extends MyModel  {

	var $UI_name = 'Zoo';

	var $name = 'Listing';

	var $useTable = '#__zoo_core_item AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_zoo';

	var $listingUrl = 'index.php?Itemid=%s&option=com_zoo&amp;view=item&amp;category_id=%s&amp;item_id=%s';

	var $cat_url_param = 'category_id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.name AS `Listing.title`',
//		'Images.filename AS `Listing.images`',
		"'com_zoo' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.name AS `Category.title`',
		'ExtensionCategory.category_id AS `Category.id`',
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',
		'Criteria.state AS `Criteria.state`',
        'User.id AS `User.user_id`',
        'User.name AS `User.name`',
        'User.username AS `User.username`',
        'User.email AS `User.email`',
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`'
	);

	/**
	 * Used for detail listing page - not used for 3rd party components
	 */
	var $joins = array(
//		"LEFT JOIN #__mt_images AS Images ON Listing.link_id = Images.link_id",
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_zoo'",
		"LEFT JOIN #__zoo_core_category_item AS ExtensionCategory ON Listing.id = ExtensionCategory.item_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_zoo'",
		'LEFT JOIN #__zoo_core_category AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        "LEFT JOIN #__users AS User ON User.id = Listing.created_by"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
//		"LEFT JOIN #__mt_images AS Images ON Review.pid = Images.link_id",
        "LEFT JOIN #__zoo_core_category_item AS ExtensionCategory ON Review.pid = ExtensionCategory.item_id",
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.category_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_zoo'",
        'LEFT JOIN #__zoo_core_category AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);


	function __construct() {
		parent::__construct();

        // Used in MyReviews page to differentiate from other component reviews
		$this->tag = __t("ZOO_TAG",true);
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_zoo' . _DS . 'zoo.php');
	}


	function listingUrl($listing)
    {
		return sprintf($this->listingUrl,$listing['Listing']['menu_id'],$listing['Listing']['cat_id'],$listing['Listing']['listing_id']);
	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.created_by AS user_id, User.name, User.email
            FROM
                #__zoo_core_item AS Listing
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id
            WHERE
                Listing.id = " . (int) ($result_id);

        $this->_db->setQuery($query);

        $owner = $this->_db->loadAssocList();

        return current($owner);
    }

	function getImage($listing_id) {
		$query = "SELECT Image.filename AS image FROM #__mt_images AS Image WHERE Image.link_id = " . $listing_id . " LIMIT 1";
		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	function afterFind($results)
    {
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
            /* Very hard to implement when the content types each have their own separate table with different columns for images */
			$imagePath = '';
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

		$query = "SELECT Component.id AS value,Component.name as text"
		. "\n FROM #__zoo_core_category AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.name ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__zoo_core_category AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
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