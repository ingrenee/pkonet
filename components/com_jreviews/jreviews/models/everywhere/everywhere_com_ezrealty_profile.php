<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComEzrealtyProfileModel extends MyModel  {

	var $UI_name = 'Ez Realty Profiles';

    var $extension = 'com_ezrealty';

    var $extension_alias = 'com_ezrealty_profile';

	var $name = 'Listing'; // Model association

    /**
    * Listing setup vars
    */

	var $useTable = '#__ezrealty_profile AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'mid';

    /**
    * Create date column. Used in listings module for most recent ordering
    */
    var $dateKey = null;

    /**
    * Category admin setup vars - Also update $fields, $joins and $joinsReviews arrays below
    */
    var $catIdColumn = 'dealer_type';

//    var $catTitleColumn = 'name'; // Doesn't exist

    var $catTable = 'ezrealty_profile';

    var $profileTypes = array(
        0=>array('value'=>0,'text'=>'Undefined'),
        1=>array('value'=>1,'text'=>'Agent'),
        2=>array('value'=>2,'text'=>'Owner/Seller'),
        3=>array('value'=>3,'text'=>'Broker'),
        4=>array('value'=>4,'text'=>'Builder'),
        5=>array('value'=>5,'text'=>'Foreclosure')
    );

	/**
	 * Used for listing module - latest listings ordering
	 */

	var $listingUrl = 'index.php?option=com_ezrealty&task=showprofile&id=%s&Itemid=%s';

	var $cat_url_param = null;

	var $fields = array(
		'Listing.mid AS `Listing.listing_id`',
		'Listing.dealer_name AS `Listing.title`',
		'Listing.dealer_type AS `Listing.cat_id`',
		'Listing.dealer_image AS `Listing.images`',
		'\'com_ezrealty_profile\' AS `Listing.extension`',
        'Listing.dealer_type AS `Category.cat_id`',
        '\'Profile\' AS `Category.title`',
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
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.mid AND Totals.extension = 'com_ezrealty_profile'",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.dealer_type = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_ezrealty_profile'",
		"LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		"LEFT JOIN #__users AS User ON User.id = Listing.mid"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__ezrealty_profile AS Listing ON Review.pid = Listing.mid',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.dealer_type = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_ezrealty_profile'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		'LEFT JOIN #__ezrealty_profile AS Listing ON Media.listing_id = Listing.mid',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.dealer_type = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_ezrealty_profile'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $conditions = array();

	var $group = array('Listing.mid');

	function __construct() {
		parent::__construct();

		// Used in MyReviews page to differentiate from other component reviews
		$this->tag = __t("EZREALTY_PROFILE_TAG",true);

		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . $this->extension . _DS . str_replace('com_','',$this->extension).'.php');
	}

	function listingUrl($result) {
		return sprintf($this->listingUrl,$result['Listing']['listing_id'],$result['Listing']['menu_id']);
	}

	// Used to check whether reviews can be posted by listing owners
	function getListingOwner($result_id)
    {
		$query = "
            SELECT
                Listing.mid, User.name, User.email
            FROM
                #__ezrealty_profile AS Listing
            LEFT JOIN
                #__users AS User ON Listing.mid = User.id
            WHERE
                Listing.mid = " . (int) ($result_id);
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

			if($images != '' && @file_exists("components/com_ezrealty/profiles/" . $images)) {
				   $imagePath = "components/com_ezrealty/profiles/" . $images;
			} else {
				// Put a noimage path here?
                   $imagePath = "components/com_ezrealty/profiles/nothumb.jpg";
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

        $exclude = $this->query($query,'loadColumn');

        $categories = $this->profileTypes;

        foreach($categories AS $key=>$row){
            if(in_array($row['value'],array_values($exclude))){
                unset($categories[$key]);
            }
        }

        return $categories;
	}

	function getUsedCategories()
	{
        $query = "SELECT JreviewCategory.id AS `Component.cat_id`, Criteria.title AS `Component.criteria_title`"
        . "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension_alias}'"
        . "\n LIMIT $this->offset,$this->limit"
		;
		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();
        $results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

        foreach($results AS $key=>$row){
            if(!is_integer($key)){
                $row['Component']['cat_id'] = 0;
                $results[0]=$row;
                unset($results[$key]);
            }
        }

        foreach($this->profileTypes AS $cat_id=>$row){
            if(isset($results[$cat_id]))
            $results[$cat_id]['Component']['cat_title'] = $this->profileTypes[$cat_id]['text'];
        }

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension_alias}'"
		;
		$this->_db->setQuery($query);
		$count = $this->_db->loadResult();

		return array('rows'=>$results,'count'=>$count);
	}
}
