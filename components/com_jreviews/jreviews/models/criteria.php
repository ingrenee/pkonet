<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CriteriaModel extends MyModel  {

	var $name = 'Criteria';

	var $useTable = '#__jreviews_criteria AS Criteria';

	var $primaryKey = 'Criteria.criteria_id';

	var $realKey = 'id';

	var $fields = array(
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.title AS `Criteria.title`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.required AS `Criteria.required`',
		'Criteria.weights AS `Criteria.weights`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.qty AS `Criteria.quantity`',
		'Criteria.groupid AS `Criteria.group_id`',
		'Criteria.state AS `Criteria.state`',
		'Criteria.search AS `Criteria.search`',
        'Criteria.config AS `ListingType.config`'  # Configuration overrides
	);

	function getList() {

		$query = "SELECT * from #__jreviews_criteria order by title ASC";

		$this->_db->setQuery($query);

		$rows = $this->_db->loadObjectList();

		return $rows;

	}

	function getSelectList($options = array()) {

		$criteria_id = null;

		if(is_numeric($options)) {

			$criteria_id = $options;
		}

		$query = "
			SELECT
				id AS value, title AS text
			FROM
				#__jreviews_criteria
		". ($criteria_id ? " WHERE id = " . $criteria_id : '') ."
			ORDER BY
				title ASC
		";

		$results = $this->query($query,'loadObjectList');

		return $results;
	}

	/**
	 * Returns criteria set
	 *
	 * @param array $data has extension, cat_id or criteria_id keys=>values
	 */
	function getCriteria($data)
    {
		if(isset($data['criteria_id'])) {
			$conditions = array('Criteria.id = ' . Sanitize::getInt($data,'criteria_id'));
			$joins = array();
		} elseif(isset($data['cat_id'])) {
			$conditions = array('JreviewCategory.id = ' . Sanitize::getInt($data,'cat_id'));
			$joins = array("INNER JOIN #__jreviews_categories AS JreviewCategory ON Criteria.id = JreviewCategory.criteriaid AND JreviewCategory.`option` = '{$data['extension']}'");
		}
		$queryData = array('conditions'=>$conditions,'joins'=>$joins);

		$results = $this->findRow($queryData);

		if(isset($results['Criteria']['criteria']) && $results['Criteria']['criteria'] != '') {
			$results['Criteria']['criteria'] = explode("\n",$results['Criteria']['criteria']);
		}

		if(isset($results['Criteria']['tooltips']) && $results['Criteria']['tooltips'] != '') {
			$results['Criteria']['tooltips'] = explode("\n",$results['Criteria']['tooltips']);
		}

		if(isset($results['Criteria']['weights']) && $results['Criteria']['weights'] != '') {
			$results['Criteria']['weights'] = explode("\n",$results['Criteria']['weights']);
		}
		return $results;
	}

	function getListingTypeOverridesByListingId($listing_id) {

		$query = "
			SELECT
				config
			FROM
				#__jreviews_criteria AS ListingType
			WHERE
				ListingType.id = (
					SELECT
						JreviewsCategory.criteriaid
					FROM
						#__content AS Listing
					LEFT JOIN
						#__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.catid AND JreviewsCategory.option = 'com_content'
					WHERE
						Listing.id = {$listing_id}
				)
		";

		$config = $this->query($query,'loadResult');

		$config = json_decode($config,true);

		return $config;
	}

    function afterFind($results)
    {
        foreach($results AS $key=>$result)
        {
            isset($result['ListingType']['config']) and $results[$key]['ListingType']['config'] = json_decode($result['ListingType']['config'],true);
        }
        return $results;
    }

    function afterSave($ret)
    {
        clearCache('','__data');

        clearCache('','views');
    }
}