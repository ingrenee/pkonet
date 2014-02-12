<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComJoomgalleryModel extends MyModel  {

	var $UI_name = 'JoomGallery';

	var $name = 'Listing';

	var $useTable = '#__joomgallery AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	var $extension = 'com_joomgallery';

    var $listingUrl = 'index.php?view=detail&amp;id=%s&amp;option=com_joomgallery&amp;Itemid=%s';

	var $cat_url_param = 'catid';

	var $catid = 'catid';

	var $dateKey = 'imgdate';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.imgtitle AS `Listing.title`',
		'Listing.imgfilename AS `Listing.images`',
		'Category.catpath AS `Listing.images_path`',
		'Listing.catid AS `Listing.cat_id`',
		"'com_joomgallery' AS `Listing.extension`",
		'Category.name AS `Category.title`',
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
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_joomgallery'",
		'LEFT JOIN #__joomgallery_catg AS Category ON Listing.catid = Category.cid',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.cid AND JreviewsCategory.`option` = 'com_joomgallery'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__joomgallery AS Listing ON Review.pid = Listing.id',
		'LEFT JOIN #__joomgallery_catg AS Category ON Listing.catid = Category.cid',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.cid AND JreviewsCategory.`option` = 'com_joomgallery'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		'LEFT JOIN #__joomgallery AS Listing ON Media.listing_id = Listing.id',
		'LEFT JOIN #__joomgallery_catg AS Category ON Listing.catid = Category.cid',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.cid AND JreviewsCategory.`option` = 'com_joomgallery'",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	function __construct() {
		parent::__construct();

		$this->tag = __t("JOOMGALLERY_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_joomgallery' . _DS . 'joomgallery.php');
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
        $imagePath = '';

        # Find the thumbnail path
        $query = "
            SELECT
                jg_paththumbs
            FROM
                #__joomgallery_config
            LIMIT 1
        ";
        $this->_db->setQuery($query);
        $thumbnail_path = $this->_db->loadResult();

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
			$images = $result['Listing']['images'];
			unset($results[$key]['Listing']['images']);
			$results[$key]['Listing']['images'] = array();

			if($images != '') {
                if (file_exists($thumbnail_path . $result['Listing']['images_path'] . '/' . $images) ) {
                    $imagePath = $thumbnail_path . $result['Listing']['images_path'] . '/' . $images;
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

		$query = "SELECT Component.cid AS value,Component.name as text"
		. "\n FROM #__joomgallery_catg AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.cid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.cid NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.name ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.cid AS `Component.cat_id`,Component.name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__joomgallery_catg AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.cid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
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
