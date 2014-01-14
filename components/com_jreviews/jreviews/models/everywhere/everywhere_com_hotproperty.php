<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/*        'com_hotproperty'=>array('name'=>'Hot Property', 'values'=>array(
            'tag'=>'_JR_COM_HOTPROPERTY',
            'cat_table'=>'#__hp_prop_types',
            'cat_catid'=>'id',
            'cat_title'=>'name',
            'entry_table'=>'#__hp_properties',
            'entry_id'=>'id',
            'entry_title'=>'name',
            'entry_cat_query'=>'SELECT type FROM #__hp_properties WHERE id = {ENTRY_ID}',
            'entry_url'=>'index.php?option=com_hotproperty&task=view&id={ENTRY_ID}'
            )
        ),*/

class EverywhereComHotpropertyModel extends MyModel  {

	var $UI_name = 'Hot Property';

	var $name = 'Listing';

	var $useTable = '#__hp_properties AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'publish_up';

	var $extension = 'com_hotproperty';

	var $listingUrl = 'index.php?option=com_hotproperty&amp;task=view&amp;id=%s&amp;Itemid=%s';

	var $cat_url_param = 'id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.name AS `Listing.title`',
//		'Images.filename AS `Listing.images`',
		"'com_hotproperty' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.name AS `Category.title`',
		'Category.id AS `Category.cat_id`',
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
//		"LEFT JOIN #__mt_images AS Images ON Listing.link_id = Images.link_id",
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_hotproperty'",
        'LEFT JOIN #__hp_prop_types AS Category ON Listing.type = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_hotproperty'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
//		"LEFT JOIN #__mt_images AS Images ON Review.pid = Images.link_id",
		"LEFT JOIN #__hp_properties AS Listing ON Media.listing_id = Listing.id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.type = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_hotproperty'",
		'LEFT JOIN #__hp_prop_types AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	function __construct() {
		parent::__construct();

		$this->tag = __t("HOTPROPERTY_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		//$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . DS . 'com_hotproperty' . DS . 'hotproperty.php');
	}


	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

	function getImage($listing_id)
    {
		$query = "SELECT Image.thumb AS image FROM #__hp_photos AS Image WHERE Image.property = " . $listing_id . " AND ordering = 1 LIMIT 1";
		$this->_db->setQuery($query);
		$image = $this->_db->loadResult();
        return $image;
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
			$imagePath = '';
            $images = $this->getImage($result['Listing']['listing_id']);
			$results[$key]['Listing']['images'] = array();

            if($images != '') {
                if ( @file_exists("media/com_hotproperty/images/thb/" . $images) ) {
                    $imagePath = "media/com_hotproperty/images/thb/" . $images;
                } elseif ( @file_exists("components/com_hotproperty/img/thb/" . $images) ) { // v0.9
                    $imagePath = "components/com_hotproperty/img/thb/" . $images;
                }

            } else {
                if ( @file_exists('media/com_hotproperty/images/noimage_thb.png') ) {
                // Put a noimage path here?
                    $imagePath = "media/com_hotproperty/images/noimage_thb.png";
                } elseif ( @file_exists("components/com_hotproperty/img/thb/noimage.npg")) {  // v0.9
                    $imagePath = "components/com_hotproperty/img/thb/noimage.npg";
                }
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

		$query = "SELECT Component.id AS value,Component.name as text"
		. "\n FROM #__hp_prop_types AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.name ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__hp_prop_types AS Component"
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
