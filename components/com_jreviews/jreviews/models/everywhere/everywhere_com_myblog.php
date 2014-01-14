<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComMyblogModel extends MyModel  {

	var $UI_name = 'MyBlog';

	var $name = 'Listing';

	var $useTable = '#__content AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_myblog';

	var $listingUrl = 'index.php?option=com_myblog&show=%s&Itemid=%s';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title AS `Listing.title`',
		'Listing.introtext AS `Listing.summary`',
		'Listing.catid AS `Listing.cat_id`',
		'Permalink.permalink AS `Listing.slug`',
		'\'com_myblog\' AS `Listing.extension`',
		'criteria'=>'Criteria.id AS `Criteria.criteria_id`',
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

	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_myblog'",
		"LEFT JOIN #__categories AS Category ON Listing.catid = Category.id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_myblog'",
		"LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		"LEFT JOIN #__users AS User ON User.id = Listing.created_by",
		"LEFT JOIN #__myblog_permalinks AS Permalink ON Listing.id = Permalink.contentid"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_myblog'",
		"LEFT JOIN #__categories AS Category ON Category.id = Listing.catid",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		'LEFT JOIN #__content AS Listing ON Media.listing_id = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_myblog'",
		"LEFT JOIN #__categories AS Category ON Category.id = Listing.catid",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $conditions = array();

	var $group = array('Listing.id');

	function __construct()
    {
		parent::__construct();
        $User = cmsFramework::getUser();

		// Used in MyReviews page to differentiate from other component reviews
		$this->tag = __t("MYBLOG_TAG",true);

		// Uncomment line below to show tag in My Reviews page
		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

		// Set default WHERE statement
		$this->conditions = array(
			'Listing.state = 1',
			'( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )',
			'( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )',
			'Listing.catid > 0',
		);

	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_myblog' . _DS . 'myblog.php');
	}

	function listingUrl($result) {
		$result['Listing']['slug'] = preg_replace("/([\x80-\xFF])/e", "chr(0xC0|ord('\\1')>>6).chr(0x80|ord('\\1')&0x3F)", $result['Listing']['slug']);
		return sprintf($this->listingUrl,$result['Listing']['slug'],$result['Listing']['menu_id']);
	}

	// Used to check whether reviews can be posted by listing owners
	function getListingOwner($result_id)
    {
		$query = "
            SELECT
                Listing.created_by AS user_id, User.name, User.email
            FROM
                #__content AS Listing
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id
            WHERE
                Listing.id = " . (int) ($result_id);
		$this->_db->setQuery($query);
		appLogMessage($this->_db->getErrorMsg(),'owner_listing');
		return current($this->_db->loadAssocList());
	}

	function beforeFind() {
        if(!defined('MVC_FRAMEWORK_ADMIN'))
        {
            # Shows only links users can access
            $Access = Configure::read('JreviewsSystem.Access');

               $this->conditions[] = 'Listing.access IN ( ' . $Access->getAccessLevels() . ')';
        }
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
			// Gets the src of the first image in the html code for the blog post
			$images = preg_match('/<img.*?src=([\'"])([^"\1]+)\1/i', $result['Listing']['summary'], $matches);
			$results[$key]['images'] = array();

			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				if (@file_exists($matches[2])) {
				    $imagePath = $matches[2];
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

	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.id AS value,Component.title as text"
		. "\n FROM #__categories AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
//		. "\n WHERE Component.title = 'MyBlog'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
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
