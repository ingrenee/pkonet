<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingTypesController extends MyController {

	var $uses = array('acl','criteria', 'review');

	var $helpers = array('html','form','jreviews','admin/admin_criterias','admin/admin_settings');

    var $components = array('access','config');

	var $autoRender = false;

	var $autoLayout = false;

    var $__listings = array();

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
		$rows = $this->Criteria->getList();

	 	$table = $this->listViewTable($rows);

		$this->set(array('table'=>$table));

		return $this->render();
	}

	function listViewTable($rows)
    {
		foreach($rows AS $key=>$row) {

			$groupList = '';

			$rows[$key]->field_groups = $this->getGroupListFromIds($row->groupid);

		}

		$this->set(array(
			'rows'=>$rows
		));

		return $this->render('listing_types','table');

	}

	function getGroupListFromIds($ids)
	{
		$groupList = '';

		if ($ids != '')
		{
			$groups = explode (",", $ids);

			foreach ($groups as $group) {

				$this->_db->setQuery("
					SELECT
						CONCAT(name,' (',IF(type=\"content\",\"listing\",type),')') AS `group`
					FROM
						#__jreviews_groups
					WHERE groupid = $group"
				);

				$result = $this->_db->loadResult();

				if($result != '') {
					$groupList .= "<li>$result</li>";
				}
			}

			$groupList = "<ul>$groupList</ul>";
		}

		return $groupList;
	}

	function edit()
    {
		$this->name = 'listing_types';

		$this->action = 'edit';

		$this->autoRender = false;

		$criteriaid =  (int) Sanitize::getInt($this->params,'id');

		$reviews = '';

		if($criteriaid)
        {
			$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));

			// check if reviews exist, also used in _save
			$query = "
				SELECT
					COUNT(*)
				FROM
					#__jreviews_comments AS Reviews
				INNER JOIN
					#__content AS Content ON Content.id = Reviews.pid
				INNER JOIN
					#__categories AS Cat ON Cat.id = Content.catid
				INNER JOIN
					#__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Cat.id
				WHERE
					JreviewsCategory.criteriaid = $criteriaid
			";
			$this->_db->setQuery($query);
			$reviews = $this->_db->loadResult();

		} else {
			$criteria = $this->Criteria->emptyModel();
			$criteria['Criteria']['state'] = 1;
			$criteria['Criteria']['group_id'] = '';
			$criteria['ListingType'] = array('config'=>array());
		}

		// create custom field groups select list
		$this->_db->setQuery("
            SELECT
                groupid AS value,
                CONCAT(name,' - ',UPPER(IF(type=\"content\",\"listing\",type))) AS text
            FROM
                `#__jreviews_groups`
            ORDER BY
                type, name"
            );

		$groups = $this->_db->loadObjectList();

		foreach ( array('criteria', 'weights', 'tooltips') as $v )
		{
			$criteriaDisplay[$v] = explode("\n", $criteria['Criteria'][$v]);
		}

        $criteriaDisplay['required'] = explode("\n", $criteria['Criteria']['required']);

		$this->set(
			array(
				'criteria'=>$criteria,
				'groups'=>$groups,
				'criteriaDisplay' => $criteriaDisplay,
				'reviewsExist' => $reviews,
                'accessGroups' => $this->Acl->getAccessGroupList(),
				'accessLevels' => $this->Acl->getAccessLevelList(),
                'rowId' => count($criteriaDisplay['criteria']),
                'listingTypes'=>$this->Criteria->getSelectList()
			)
		);

		return $this->render();
	}

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Criteria->findRow(array('conditions'=>array('Criteria.id = ' . $id)));

		// Process columns that are not just plain text
		S2App::import('Helper','admin/admin_criterias','jreviews');

		$AdminCriterias = ClassRegistry::getClass('AdminCriteriasHelper');

		$row['Criteria']['criteria'] = $AdminCriterias->createListFromString($row['Criteria']['criteria'] );

		$row['Criteria']['field_groups'] = $this->getGroupListFromIds($row['Criteria']['group_id']);

		return cmsFramework::jsonResponse($row);
	}

	function _save()
    {
		$this->action = 'index';

		$criteriaid = $this->data['Criteria']['id'];

		$isNew = !$criteriaid ? true : false;

		$reviews = array();

		$response = array('success'=>false,'str'=>array());

        $apply = Sanitize::getBool($this->params,'apply',false);

		// revert all input arrays to strings
		foreach ( array('criteria', 'required', 'weights', 'tooltips') as $v )
		{
            if($v == 'tooltips') {
                $this->data['Criteria'][$v] = implode("\n", $this->data['__raw']['Criteria'][$v]);
            }
            else {
                $this->data['Criteria'][$v] = implode("\n", $this->data['Criteria'][$v]);
            }
        }

        # Configuration overrides - save as json object
        // Pre-process access overrides first
        $keys = array_keys($this->data['Criteria']['config']);

		$settings = array_keys($this->data['Criteria']['config']);

		$access_settings = $this->Access->__settings_overrides;

		foreach($access_settings AS $setting) {

			if(!in_array($setting, $settings)) {

				$this->data['Criteria']['config'][$setting] = '';
			}
		}

		$this->data['Criteria']['config']['social_sharing_detail'] = Sanitize::getVar($this->data['Criteria']['config'],'social_sharing_detail',array());

        $this->data['Criteria']['config'] = json_encode(Sanitize::getVar($this->data['Criteria'],'config'));

		// Lets remove any blank lines from the new criteria
		$newCriteria = cleanString2Array($this->data['Criteria']['criteria'],"\n");

		// clean Required field
		$newRequired = cleanString2Array($this->data['Criteria']['required'],"\n");

		// Lets remove any blank lines from the new criteria
		$newTooltips = cleanString2Array($this->data['Criteria']['tooltips'],"\n");

		// New weights
		$newWeights = cleanString2Array($this->data['Criteria']['weights'],"\n");

		// Begin basic validation
		if ($this->data['Criteria']['title']=='') {

			$response['str'][] = 'LISTING_TYPE_VALIDATE_TITLE';
		}

		if ($this->data['Criteria']['state'] == 1 ) {

			if ($this->data['Criteria']['criteria']=='') {

				$response['str'][] = 'LISTING_TYPE_VALIDATE_CRITERIA';
			}

			if ($this->data['Criteria']['weights']!='') {

				if (round(array_sum(explode("\n",$this->data['Criteria']['weights']))) != 100 && trim($this->data['Criteria']['weights']) != '' )
					$response['str'][] = 'LISTING_TYPE_VALIDATE_WEIGHTS';
			}

			if (count($newCriteria) != count($newWeights) && count($newWeights) > 0 ) {

				$response['str'][] = 'LISTING_TYPE_VALIDATE_CRITERIA_WEIGHT_COUNT';
			}

			if (count($newTooltips) > count($newCriteria)) {

				$response['str'][] = 'LISTING_TYPE_VALIDATE_CRITERIA_TOOLTIP_COUNT';
			}

			if ( count($newRequired) != count($newCriteria) ) {

				$response['str'][] = 'LISTING_TYPE_VALIDATE_CRITERIA_REQUIRED_COUNT';
			}


		} else {
			// if input invalid default to 0
			if ( !in_array( $this->data['Criteria']['state'], array(0,2) ) )
			{
				$this->data['Criteria']['state'] = 0;
			}
		}

		if (!empty($response['str']))
        {
            return cmsFramework::jsonResponse($response);
		}

		// If this is a new criteria, proceed to save
		if($criteriaid)
        {
            // We are in edit mode so let's check if the number of criteria has changed
            $criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));

            if(count($newCriteria) != count(cleanString2Array($criteria['Criteria']['criteria'])))
            {
                $query = "
                    SELECT
                        COUNT(*)
                    FROM
                        #__jreviews_comments AS Review
                    INNER JOIN
                        #__content AS Content ON Content.id = Review.pid
                    INNER JOIN
                        #__categories AS Cat ON Cat.id = Content.catid
                    INNER JOIN
                        #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Cat.id
                    WHERE
						Review.mode = 'com_content'
						AND
                        JreviewsCategory.criteriaid = $criteriaid
                ";

                $this->_db->setQuery($query);

                $reviews = $this->_db->loadResult();

                // Todo: there are no 'everywhere' checks. will have to go component by component..

                if ($reviews) {  // There are reviews so saving is denied.

                    $response['str'][] = array('LISTING_TYPE_EDIT_NOT_EMPTY',$reviews);

            		return cmsFramework::jsonResponse($response);
                }
            }
		}

        // Lets remove any blank lines from the new criteria
        $newCriteriaArray = cleanString2Array($this->data['Criteria']['criteria'],"\n");

        $this->data['Criteria']['criteria'] = implode("\n",$newCriteriaArray); //Reconstruct the string using the cleaned-up array

        $this->data['Criteria']['qty'] = count($newCriteriaArray);

        // Remove blank lines from weights
        $newWeightsArray = cleanString2Array($this->data['Criteria']['weights'],"\n");

        $this->data['Criteria']['weights'] = implode("\n",$newWeightsArray);

        // for Required
        $newRequiredArray = cleanString2Array($this->data['Criteria']['required'],"\n");

        $this->data['Criteria']['required'] = implode("\n",$newRequiredArray);

        // Convert groupid array to list
        if(isset($this->data['Criteria']['groupid'][0]) && is_array($this->data['Criteria']['groupid'][0])) {

            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid'][0]);
        }
        elseif(isset($this->data['Criteria']['groupid']) && is_array($this->data['Criteria']['groupid'])) {

            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid']);
        }
        else {

            $this->data['Criteria']['groupid'] = '';
        }

        $this->Criteria->store($this->data);

        $response['success'] = true;

        if($apply) {

            return cmsFramework::jsonResponse($response);
        }
        elseif($isNew) {

        	$response['isNew'] = $isNew;
        }

        $response['id'] = $this->data['Criteria']['id'];

		$response['html'] = $this->index();

        return cmsFramework::jsonResponse($response);
	}

	function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid');

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

		// Check if the criteria is being used by a category
		$query = '
			SELECT
				id, `option` AS extension
			FROM
				#__jreviews_categories
			WHERE
				criteriaid IN (' . cleanIntegerCommaList($ids) . ')'
		;

		$categories = $this->Review->query($query,'loadAssocList');

		$count = count($categories);

		if ($count) {

			$response['str'][] = 'LISTING_TYPE_REMOVE_NOT_EMPTY';

        	return cmsFramework::jsonResponse($response);
		}

		// Delete the listing type
		$this->Criteria->delete('id', $ids);

		// Now process dependencies

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

		$response['success'] = true;

		return cmsFramework::jsonResponse($response);
	}

	function _copy()
    {
        $response = array('success'=>false,'str'=>array());

        $copies = Sanitize::getInt($this->params,'copies',1);

        $criteriaid = Sanitize::getInt($this->params,'id');

		if (!$criteriaid){

			$response['str'][] = 'LISTING_TYPE_VALIDATE_COPY_SELECT';

            return cmsFramework::jsonResponse($response);
		}

		$query = "CREATE TEMPORARY TABLE temp_table AS SELECT * FROM #__jreviews_criteria WHERE id = " . $criteriaid;

		$this->Criteria->query($query);


		$query = "UPDATE temp_table SET id = 0, title = CONCAT(title,' [COPY]') WHERE id = " . $criteriaid;

		$this->Criteria->query($query);


		$query = "INSERT INTO #__jreviews_criteria SELECT * FROM temp_table";

		$this->Criteria->query($query);

		$new_id = $this->Criteria->_db->insertid();

		$query = "DROP TEMPORARY TABLE temp_table";

		$this->Criteria->query($query);

		// Reloads the whole list to display the new/updated record
		$fieldrows = $this->Criteria->getList();

		$response['success'] = true;

        $response['html'] = $this->listViewTable($fieldrows);

        $response['id'] = $new_id;

        return cmsFramework::jsonResponse($response);
	}

    /**
     * Recalculates the rating sum based on the weights assigned to each rating
     *
     */
    function refreshReviewRatings()
    {
        error_reporting(E_ALL); ini_set('display_errors','On');

        ini_set('max_execution_time', 600); # 10 minutes

        $validation = array();

        // Get the list of category ids and weights
        $query = "
            SELECT
                criteria.id, criteria.weights, cat.id AS catid, cat.option
            FROM
                #__jreviews_criteria AS criteria
            INNER JOIN
                #__jreviews_categories AS cat on cat.criteriaid = criteria.id
        ";

        $rows = $this->Criteria->query($query,'loadObjectList');

        foreach ($rows as $row) {

            $weights_check = trim($row->weights);

            if ($weights_check != '') {

                if($row->catid>0){

                    // Using $row->option, otherwise may overwrite values if there are dup catid's across components
                    $weights[$row->catid.$row->option] = explode("\n",$row->weights);
                }
            }
        }

        # working in chunks to avoid memory overload
        $query = "SELECT COUNT(*) FROM #__jreviews_comments";

        $reviewCount = $this->Criteria->query($query,'loadResult');

        $leap = 1000; # configurable

        for ( $offset = 0; $offset < $reviewCount; $offset += $leap ) # encompassing with for loop
        {
            // Get list of reviewids, category ids and ratings
            $query = "
                SELECT
                    Review.id, Review.pid AS lid, Review.mode,
                    Rating.ratings, Rating.ratings_sum, Rating.ratings_qty
                FROM
                    #__jreviews_comments AS Review
                LEFT JOIN
                    #__jreviews_ratings AS Rating ON Rating.reviewid = Review.id
                ORDER BY
                    Review.id
                LIMIT $offset, $leap
            "; # using left join, need comments table in any case for comment count

            $rows = $this->Criteria->query($query,'loadObjectList');

            // Recalculate the total rating sum
            foreach ($rows as $key=>$row) {

                if (empty($row->ratings) )
                {
                    continue;
                }

                // Load listings' Everywhere model
                $file_name = 'everywhere' . '_' . $row->mode;

                $class_name = inflector::camelize($file_name).'Model';

                S2App::import('Model',$file_name,'jreviews');

                $this->Listing = new $class_name();

                if(!isset($__listings[$row->mode.$row->lid]))
                    {
                        $listing = $this->Listing->findRow(array(
                            'conditions' => "Listing.{$this->Listing->realKey} = ".$row->lid
                        ),array());

                        $__listings[$row->mode.$row->lid] = $listing;

                    }
                else
                    {
                        $listing = $__listings[$row->mode.$row->lid];
                    }

                if(!is_array($listing) || empty($listing) || !$listing) {

                    continue;
                }

                $row->catid = array_key_exists('catid',$listing['Category']) ? $listing['Category']['cat_id'] : $listing['Listing']['cat_id'];

                $__listings[$row->mode.$row->lid]['cat_id'] = $row->catid;

                $ratings_sum = 0;

                $ratings = explode (",",$row->ratings);

                $quantity = $row->ratings_qty;

                if (isset($weights[$row->catid.$row->mode]) && is_array($weights[$row->catid.$row->mode])) {

                    $sumWeights =
                        array_sum(
                            array_intersect_key(
                                $weights[$row->catid.$row->mode],
                                array_filter(
                                    $ratings,
                                    create_function(
                                        '$el', 'return is_numeric($el);'
                                    )
                                )
                            )
                        )
                    ;

                    if ( $sumWeights > 0 )
                    {
                        foreach ($ratings as $key2=>$rating) {
                            $ratings_sum += $rating * $weights[$row->catid.$row->mode][$key2] / $sumWeights;
                        }

                        $ratings_sum = $ratings_sum*$quantity;
                    }

                    $rows[$key]->ratings_sum = $ratings_sum;
                }
                else {

                    $rows[$key]->ratings_sum = array_sum($ratings);
                }
            }

            // Update database records
            foreach ($rows as $row)
            {
                if ( empty($row->ratings) )
                {
                    continue;
                }

                $query = "UPDATE #__jreviews_ratings SET ratings_sum = '$row->ratings_sum' WHERE reviewid = '$row->id'";

                $this->Criteria->query($query);

                if (!$this->Criteria->query($query))
                {
                    # halting script on first error so to avoid possible further damage to the database data
                    // Clear cache
                    clearCache('', 'views');

                    clearCache('', '__data');

                    return JreviewsLocale::getPHP('DB_ERROR');
                }
            }

            # unset before more db data is loaded into memory
            unset($rows);
        }

        $validation[] = JreviewsLocale::getPHP('REVIEWS_RESYNC_RATINGS_COMPLETE');

        // Update listing totals

        # reloading listing data for all listings. diving into chunks again
        $this->_db->setQuery("SELECT COUNT(DISTINCT pid) FROM #__jreviews_comments");

        $listingCount = $this->_db->loadResult();

        for ( $offset = 0; $offset < $listingCount; $offset += $leap )
        {
            $query = "
                SELECT DISTINCTROW
                    Review.pid as lid, Review.mode
                FROM
                    #__jreviews_comments AS Review
                ORDER BY
                    Review.pid
                LIMIT $offset, $leap
            ";

            $rows = $this->Criteria->query($query,'loadObjectList');

            foreach ( $rows as $row )
            {
                if(isset($__listings[$row->mode.$row->lid]))
                {
                    $catid = $__listings[$row->mode.$row->lid]['cat_id'];

                    if ( !$this->Review->saveListingTotals($row->lid, $row->mode, !empty($weights[$catid.$row->mode]) ? $weights[$catid.$row->mode] : '') )
                    {
                        # halting script on first error so to avoid possible further damage to the database data
                        // Clear cache
                        clearCache('', 'views');

                        clearCache('', '__data');

                    	return JreviewsLocale::getPHP('DB_ERROR');
                    }
                }
            }

            unset($rows);
        }

        // Clear cache
        clearCache('', 'views');

        clearCache('', '__data');

        $validation[] = JreviewsLocale::getPHP('LISTINGS_RESYNC_RATINGS_COMPLETE');

        return '<ul><li>'.implode('</li><li>',$validation).'</li></ul>';
    }
}
