<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComBookmarksModel extends MyModel  {

	var $UI_name = 'Bookmarks';

	var $name = 'Listing';

	var $useTable = '#__bookmarks AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	var $extension = 'com_bookmarks';

	var $listingUrl = 'index.php?option=com_bookmarks&amp;catid=%s&amp;task=detail&amp;id=%s&amp;Itemid=%s';

	var $cat_url_param = 'catid';

	var $dateKey = 'created';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title AS `Listing.title`',
		'Listing.imageurl AS `Listing.images`',
		'Listing.url AS `Listing.bookmark_url`',
		"'com_bookmarks' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.title AS `Category.title`',
		'ExtensionCategory.catid AS `Category.cat_id`',
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
	 * Used for detail listing page
	 */
	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_bookmarks'",
		"LEFT JOIN #__bookmarks_itemcat AS ExtensionCategory ON Listing.id = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_bookmarks'",
		'LEFT JOIN #__bookmarks_categories AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		"LEFT JOIN #__bookmarks_itemcat AS ExtensionCategory ON Review.pid = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_bookmarks'",
		'LEFT JOIN #__bookmarks_categories AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $joinsMedia = array(
		"LEFT JOIN #__bookmarks_itemcat AS ExtensionCategory ON Media.listing_id = ExtensionCategory.itemid",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_bookmarks'",
		'LEFT JOIN #__bookmarks_categories AS Category ON JreviewsCategory.id = Category.id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	function __construct() {
		parent::__construct();

		$this->tag = __t("BOOKMARKS_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_bookmarks' . _DS . 'bookmarks.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['cat_id'],$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

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
			$images = $result['Listing']['images'];
			unset($results[$key]['Listing']['images']);
			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				// From bookmarks.html.php
				$urlkey = '##URL_HERE##';

				// *** Prepare Image url (compute link)
				if ( !( preg_match("/^http:\/\//", $images) || preg_match("/^https:\/\//", $images) || preg_match("/^func:\/\//", $images) ) ) {   // Internal image (not 	HTTP, HTTPS or FUNC) ?
					$imagePath = WWW_ROOT . 'images/stories/' .$images;    // Internal image from Images/Stories
				} else {
					$imagePath = $images;                                              // External image (use url)
				    if ( preg_match("/^func:\/\//", $imagePath) ) {   // Verify if there is a FUNC to process
				        $func = substr($imagePath,7);
				              $func = '$picurl='.str_replace($urlkey,"'".$result['Listing']['bookmark_url']."'",$func);
				        eval($func);   // Compute function
				    } else {
				        if ( preg_match('/'.$urlkey.'/', $imagePath) ) {                               // is the URL Keyword present ?
				           $imagePath = str_replace($urlkey, $result['Listing']['bookmark_url'], $imagePath);         // replace the "URL Keyword" with the URL...
				        }
				    }
				}
			} else {
				// Put a noimage path here?
				$imagePath = '';
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'basepath'=>true,
				'skipthumb'=>true
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

		$query = "SELECT Component.id AS value,Component.title as text"
		. "\n FROM #__bookmarks_categories AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.title ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.title as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__bookmarks_categories AS Component"
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