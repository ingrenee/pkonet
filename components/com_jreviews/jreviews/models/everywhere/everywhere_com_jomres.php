<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComJomresModel extends MyModel  {

	var $UI_name = 'Jomres';

	var $name = 'Listing';

	var $useTable = '#__jomres_propertys AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'propertys_uid';

	var $extension = 'com_jomres';

	var $listingUrl = 'index.php?option=com_jomres&amp;task=viewproperty&amp;property_uid=%s&amp;Itemid=%s';

	var $dateKey = 'cdate';

	var $fields = array(
		'Listing.propertys_uid AS `Listing.listing_id`',
		'Listing.property_name AS `Listing.title`',
		'Listing.published AS `Listing.state`',
		'Listing.ptype_id AS `Listing.cat_id`',
        'JreviewsCategory.id AS `Category.cat_id`',
		"'com_jomres' AS `Listing.extension`",
		'Category.ptype AS `Category.title`',
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
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.propertys_uid AND Totals.extension = 'com_jomres'",
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__jomres_propertys AS Listing ON Review.pid = Listing.propertys_uid',
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		// 'LEFT JOIN #__jomres_propertys AS Listing ON Media.listing_id = Listing.propertys_uid',
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

    public static $joinListingState = array(
        'INNER JOIN #__jomres_propertys AS Listing ON Listing.propertys_uid = %s AND Listing.published = 1'
        );

	function __construct() {

		parent::__construct();

		$this->tag = __t("JOMRES_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_jomres' . _DS . 'jomres.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

	function getImage($listing_id)
    {
        $property_image = WWW_ROOT . 'jomres' . _DS . 'images' . _DS . 'jrlogo.png';

        if (file_exists(PATH_ROOT . 'jomres' . DS . 'uploadedimages' . DS . $listing_id . '_property_' . $listing_id . '.jpg') )
        {
            return WWW_ROOT . 'jomres' . _DS . 'uploadedimages' . _DS . $listing_id . '_property_' . $listing_id . '.jpg';
        }

        return '';
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
			$images = $this->getImage($result['Listing']['listing_id']);

			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				    $imagePath = $images;
			} else {
				// Put a noimage path here?
				$imagePath = '';//$images;
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

	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.id AS value,Component.ptype as text"
		. "\n FROM #__jomres_ptypes AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.ptype ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.ptype as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__jomres_ptypes AS Component"
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