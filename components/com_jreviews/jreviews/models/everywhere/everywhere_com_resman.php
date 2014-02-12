<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComResmanModel extends MyModel  {

	var $UI_name = 'ResMan';

	var $name = 'Listing';

	var $useTable = '#__resman_details AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'uid';

	var $extension = 'com_resman';

	var $listingUrl = 'index.php?option=com_resman&task=moreinfo&id=%s&Itemid=%s';

	var $dateKey = null;

	var $fields = array(
		'Listing.uid AS `Listing.listing_id`',
		'Listing.name AS `Listing.title`',
		'Images.thumbnailurl AS `Listing.images`',
        'Category.id AS `Listing.cat_id`',
		"'com_resman' AS `Listing.extension`",
		'Category.title AS `Category.title`',
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
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.uid AND Totals.extension = 'com_resman'",
        'LEFT JOIN #__resman_photos AS Images ON Images.uid = Listing.uid AND Images.photoorder = 1',
		'LEFT JOIN #__categories AS Category ON Listing.category = Category.name AND Listing.category <> "" AND Listing.subitemof = 0 AND Category.section = "com_resman"',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_resman'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);


	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__resman_details AS Listing ON Review.pid = Listing.uid',
        'LEFT JOIN #__resman_photos AS Images ON Images.uid = Listing.uid AND Images.photoorder = 1',
		'LEFT JOIN #__categories AS Category ON Listing.category = Category.name AND Category.section = "com_resman"',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_resman'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		'LEFT JOIN #__resman_details AS Listing ON Media.listing_id = Listing.uid',
        'LEFT JOIN #__resman_photos AS Images ON Images.uid = Listing.uid AND Images.photoorder = 1',
		'LEFT JOIN #__categories AS Category ON Listing.category = Category.name AND Category.section = "com_resman"',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_resman'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	function __construct() {
		parent::__construct();

		$this->tag = __t("RESMAN_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_resman' . _DS . 'resman.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

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
			$result['Listing']['menu_id'] = $menu_id;

			// Process listing url
			$results[$key][$this->name]['url'] = $this->listingUrl($result);

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
            $imagePath = '';
            $images = $result['Listing']['images'];

			$results[$key]['Listing']['images'] = array();

			if($images != '') {
                 $imagePath = $images;
			} else {
				// Put a noimage path here?
				$imagePath = WWW_ROOT . 'components/com_resman/images/no_image_small.png';//$images;
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'skipthumb'=>true,
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

		$query = "SELECT Component.id AS value,Component.title as text"
		. "\n FROM #__categories AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude) AND Component.section = '{$this->extension}'" : "WHERE Component.section = '{$this->extension}'")
		. "\n ORDER BY Component.title ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.title as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__categories AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
        . "\n WHERE Component.section = '{$this->extension}'"
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