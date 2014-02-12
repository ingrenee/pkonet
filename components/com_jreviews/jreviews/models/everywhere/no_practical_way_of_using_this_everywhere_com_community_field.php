<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComCommunityFieldModel extends MyModel  {

	var $UI_name = 'JomSocial - Custom Field';

	/**
	 * Create a field with the name shown below, or change it's value to an existing field name.
	 * The options of this field will be used as categories
	 * Make sure this is a required field and shown at registration time
	 */
	var $customField = 'FIELD_MEMBERTYPE';

	var $name = 'Listing';

	var $useTable = '#__users AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'registerDate';

	/**
	 * This is the component's url option parameter
	 *
	 * @var string
	 */
	var $extension = 'com_community';

	/**
	 * This is the value stored in the reviews table to differentiate the source of the reviews
	 *
	 * @var string
	 */
	var $extension_alias = 'com_community_field';

	var $listingUrl = 'index.php?option=com_community&view=profile&userid=%s&Itemid=%s';

	var $categoryPrimaryKey;

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.username AS `Listing.title`',
		'Community.thumb AS `Listing.images`',
		"'com_community' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.name AS `Category.title`',
		'JreviewsCategory.id AS `Category.cat_id`',
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
		'Criteria.state AS `Criteria.state`'
	);

	// Done in the __construct function below because it's CMS dependent for categoryPrimaryKey
	var $joins = array();

	var $joinsReviews = array();

	// Module controller includes the basic joins for review and rating information and others can be added here
	// depending on the fields used in the query
	var $joinsListingsModule = array();

	function __construct() {

		parent::__construct();

		$this->joins = array(
			'INNER JOIN #__comprofiler AS CommunityBuilder ON Listing.id = CommunityBuilder.id',
			"LEFT JOIN #__comprofiler_field_values AS Category ON CommunityBuilder.{$this->customField} = Category.fieldtitle",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.fieldvalueid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'",
			'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
		);

		$this->joinsReviews = array(
//					"LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
			'INNER JOIN #__comprofiler AS CommunityBuilder ON Review.pid = CommunityBuilder.id',
			"LEFT JOIN #__comprofiler_field_values AS Category ON CommunityBuilder.{$this->customField} = Category.fieldtitle",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.fieldvalueid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'",
			'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
		);

		$this->tag = __t("COMMUNITY_BUILDER_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";


	}

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_community' . _DS . 'community.php');
	}

	function listingUrl($listing) {
		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);
	}

	function afterFind($results) {

		if (empty($results) || defined('MVC_FRAMEWORK_ADMIN')) {
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
				if ( @file_exists("images/comprofiler" . $images) ) {
				    $imagePath = "images/comprofiler" . $images;
				} else {
				    $imagePath = "images/comprofiler/" . $images;
				}
			} else {
				// Put a noimage path here?
				$imagePath = ''; //"images/comprofiler/" . $images;
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'basepath'=>true
			);

		}

		return $results;
	}

	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension_alias}'";

       	$exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.fieldvalueid AS value, Component.fieldtitle as text"
		. "\n FROM #__community_fields AS Component"
		. "\n INNER JOIN #__comprofiler_fields AS Field ON Component.fieldid = Field.fieldid AND Field.fieldcode = '{$this->customField}'"
		. ($exclude != '' ? "\n WHERE Component.fieldvalueid NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.fieldtitle ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.fieldvalueid AS `Component.cat_id`, Component.fieldtitle as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__comprofiler_field_values AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.fieldvalueid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n LIMIT $this->offset,$this->limit"
		;
		$this->_db->setQuery($query);
		appLogMessage("getUsedCategories\n".$this->_db->getQuery(),'everywhere');

		$results = $this->_db->loadObjectList();
		appLogMessage($this->_db->getErrorMsg(),'everywhere');

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