<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleListingsController extends MyController {

    var $uses = array('menu','field','criteria','media');

    var $helpers = array(/*'cache',*/'paginator','routes','libraries','html','assets','text','jreviews','widgets','time','rating','custom_fields','community','media');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    var $layout = 'module';

    var $abort = false;

    var $distance_metric = array();

    var $distance_in = 'mi';

    function beforeFilter()
    {
        Configure::write('ListingEdit',false);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        $this->distance_in = Sanitize::getString($this->Config,'geomaps.radius_metric','mi');

        $this->distance_metric = array('mi'=>__t("Miles",true),'km'=>__t("Km",true));
    }

    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Listing;
    }

    function index()
    {
        $ids = $currentListing = $conditions = $joins = $order = $having = array();

        $module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

       # Find the correct set of params to use
        if($this->ajaxRequest && Sanitize::getInt($this->params,'listing_id'))
        {
            $currentListing = $this->__processListingTypeWidgets($conditions);
        }
        elseif($this->ajaxRequest && empty($this->params['module']) && $module_id)
        {
            $query = "SELECT params FROM #__modules WHERE id = " . $module_id;

            $params = $this->Listing->query($query,'loadResult');

            $this->params['module'] = stringToArray($params);
        }

        if($this->abort) return '';

        # Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');

        $extension = Sanitize::getString($this->params['module'],'extension');

        $extension = $extension != '' ? $extension : 'com_content';

        $dir_id = Sanitize::getString($this->params['module'],'dir');

        $cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

        $created_by = Sanitize::getString($this->params['module'],'owner');

        $criteria_id = Sanitize::getString($this->params['module'],'criteria');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $compare = Sanitize::getInt($this->params['module'],'compare',0);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        $sort = Sanitize::getString($this->params['module'],'listing_order');

        if(in_array($sort,array('random','featuredrandom'))) {

            srand((float)microtime()*1000000);

            $this->params['rand'] = rand();
        }

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');

        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        if(isset($this->Listing))
        {
            $this->Listing->_user = $this->_user;

            // This parameter determines the module mode
            $custom_order = Sanitize::getString($this->params['module'],'custom_order');

            $custom_where = Sanitize::getString($this->params['module'],'custom_where');

            if($extension != 'com_content' && in_array($sort,array('topratededitor','featuredrandom','rhits'))) {
                echo "You have selected the $sort mode which is not supported for components other than com_content. Please read the tooltips in the module parameters for more info on allowed settings.";
                return;
            }

            # Category auto detect
            if($cat_auto && $extension == 'com_content')
            {
                $ids = CommonController::_discoverIDs($this);

                extract($ids);
            }

            if($custom_where != '') {

                $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);
            }

            # Set conditionals based on configuration parameters
            if($extension == 'com_content')
            {
				// Perform tag replacement for listing_id to allow for related listing queries
				if(Sanitize::getString($this->params,'view') == 'article') {

					$curr_listing_id = Sanitize::getInt($this->params,'id');

                    if($custom_where != '') {

                        $custom_where = str_replace(array('{listing_id}'),array($curr_listing_id),$custom_where);
                    }

                    if(!$this->ajaxRequest && $sort == 'proximity') {

                        $lat = Sanitize::getString($this->Config,'geomaps.latitude');

                        $lon = Sanitize::getString($this->Config,'geomaps.longitude');

                        if($lat != '' && $lon != '') {

                            $query = "
                                SELECT {$lat},{$lon} FROM #__jreviews_content WHERE contentid = {$curr_listing_id}
                            ";

                            $row = $this->Listing->query($query,'loadAssoc');

                            if($row[$lat] != '' && $row[$lon] != '') {

                                $currentListing['Field']['pairs'][$lat]['value'][0] = $row[$lat];

                                $currentListing['Field']['pairs'][$lon]['value'][0] = $row[$lon];

                                $this->Listing->conditions[] = 'Listing.id <> ' . $curr_listing_id;
                            }
                        }
                    }
                }
                elseif(!$this->ajaxRequest && $sort == 'proximity') {

                    // For non-detail pages order listings by most recent by default
                    $sort = 'rdate';
                }

                // Only works for core articles
                $conditions = array_merge($conditions,array(
                    'Listing.state = 1',
                    '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )',
                    '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )'
                ));

                $conditions[] = 'Category.access IN (' . $this->Access->getAccessLevels() . ')';

                $conditions[] = 'Listing.access IN (' . $this->Access->getAccessLevels() . ')';

                // Remove unnecessary fields from model query
                $this->Listing->modelUnbind(array(
                    'Listing.fulltext AS `Listing.description`',
                    'Listing.metakey AS `Listing.metakey`',
                    'Listing.metadesc AS `Listing.metadesc`',
                    'User.email AS `User.email`'
                ));

                if(!empty($cat_id))
                {
                    $conditions[] = 'ParentCategory.id IN ('.cleanIntegerCommaList($cat_id).')';
                }
                else
                {
                    unset($this->Listing->joins['ParentCategory']);
                }

                empty($cat_id) and !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . cleanIntegerCommaList($dir_id) . ')';

                empty($cat_id) and !empty($criteria_id) and $conditions[] = 'JreviewsCategory.criteriaid IN (' . cleanIntegerCommaList($criteria_id) . ')';
            }
            else
            {
                if($cat_auto && method_exists($this->Listing,'catUrlParam'))
                {
                    if($cat_id = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){

                        $conditions[] = 'JreviewsCategory.id IN (' . cleanIntegerCommaList($cat_id). ')';
                    }
                }
                elseif($cat_id)
                {
                    $conditions[] = 'JreviewsCategory.id IN (' .cleanIntegerCommaList($cat_id). ')';
                }
            }

            $listing_id and $conditions[] = "Listing.{$this->Listing->realKey} IN (". cleanIntegerCommaList($listing_id) .")";

            switch($sort)
            {
                case 'random':

                    $order[] = 'RAND('.$this->params['rand'].')';

                    break;

                case 'featured':

                    $conditions[] = 'Field.featured = 1';

                    break;

                case 'featuredrandom':

                    $conditions[] = 'Field.featured = 1';

                    $order[] = 'RAND('.$this->params['rand'].')';

                    break;

                case 'topratededitor':

//                    $conditions[] = 'Totals.editor_rating > 0';

                	$sort = 'editor_rating';

                    break;
                // Editor rating sorting options dealt with in the Listing->processSorting method
            }

            # Custom WHERE
            $tokenMatch and $custom_where and $conditions[] = $custom_where;

            # Filtering options
            $having = array();

            // Listings submitted in the past x days
            $entry_period = Sanitize::getInt($this->params['module'],'filter_listing_period');

            if($entry_period > 0 && $this->Listing->dateKey)
            {
                $conditions[] = "Listing.{$this->Listing->dateKey} >= DATE_SUB('"._CURRENT_SERVER_TIME."', INTERVAL $entry_period DAY)";
            }

            // Listings with reviews submitted in past x days
            $review_period = Sanitize::getInt($this->params['module'],'filter_review_period');

            if($extension != '' && $review_period > 0)
            {
                $joins[] = "
                    INNER JOIN (
                        SELECT
                            Review.pid, Review.mode, count(*)
                        FROM
                            #__jreviews_comments AS Review
                        WHERE
                            Review.created >= DATE_SUB(CURDATE(), INTERVAL $review_period DAY)
                        GROUP BY
                            Review.pid, Review.mode
                    ) AS Review ON Listing.{$this->Listing->realKey} = Review.pid AND Review.mode = '{$extension}'
                ";
            }

            // Listings with review count higher than
            $filter_review_count = Sanitize::getInt($this->params['module'],'filter_review_count');
            $filter_review_count > 0 and $conditions[] = "Totals.user_rating_count >= " . $filter_review_count;

            // Listings with avg rating higher than
            $filter_avg_rating = Sanitize::getFloat($this->params['module'],'filter_avg_rating');
            $filter_avg_rating > 0 and $conditions[] = 'Totals.user_rating  >= ' . $filter_avg_rating;

            $this->Listing->group = array();

            // Exlude listings without ratings from the results
            $join_direction = in_array($sort,array('rating','rrating','topratededitor','reviews')) ? 'INNER' : 'LEFT';

            $this->Listing->joins['Total'] = "$join_direction JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.{$this->Listing->realKey} AND Totals.extension = " . $this->Quote($extension);

            # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables
            if($tokenMatch and $custom_order)
            {
                $this->Listing->order[] = $custom_order;
            }
            elseif(empty($order) && $extension == 'com_content' && $sort != 'proximity')
            {
                $this->Listing->processSorting('module',$sort); // Modifies Listing model order var directly
            }
            elseif(empty($order) && $order = $this->__processSorting($sort, $currentListing))
            {
                $order = array($order);
            }

            $fields = array(
                'Totals.user_rating AS `Review.user_rating`',
                'Totals.user_rating_count AS `Review.user_rating_count`',
                'Totals.user_comment_count AS `Review.review_count`',
                'Totals.editor_rating AS `Review.editor_rating`',
                'Totals.editor_rating_count AS `Review.editor_rating_count`',
                'Totals.editor_comment_count AS `Review.editor_review_count`'
            );

            $queryData = array(
                'fields'=>!isset($this->Listing->fields['editor_rating']) ? $fields : array(),
                'joins'=>$joins,
                'conditions'=>$conditions,
                'limit'=>$total,
                'having'=>$having
            );

            if(!empty($order) && in_array('noresults',$order)) {

                $listings = array();

                $count = 0;
            }
            else {

                isset($order) and !empty($order) and $queryData['order'] = $order;

                $listings = $this->Listing->findAll($queryData);

                $sort == 'proximity' and $listings = $this->injectDistanceGroup($listings);

                $count = count($listings);
            }

        } // end Listing class check
        else {

            $listings = array();

            $count = 0;
        }

        unset($this->Listing);

        # Send variables to view template
        $this->set(array(
                'autodetect_ids'=>$ids,
                'subclass'=>'listing',
                'listings'=>$listings,
                'compare'=>$compare,
                'total'=>$count,
                'limit'=>$limit
        ));

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('modules','listings');

        return $page;
    }

    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'fields'=>'',
            'radius'=>'',
            'distance'=>1,
            'summary'=>false,
            'summary_words'=>10,
            'show_category'=>true,
            'user_rating'=>1,
            'editor_rating'=>1,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>true,
            'tn_position'=>'left',
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

   /**
    * Modifies the query ORDER BY statement based on ordering parameters
    */
    private function __processSorting($selected, & $listing)
    {
        $order = '';

        if($selected == 'proximity') {

            $lat = Sanitize::getString($this->Config,'geomaps.latitude');

            $lon = Sanitize::getString($this->Config,'geomaps.longitude');

            if($lat == '' || $lon == '') {

                return 'noresults';
            }
            else {

                $lat_value = isset($listing['Field']['pairs'][$lat]) ? Sanitize::getVar($listing['Field']['pairs'][$lat]['value'],0) : 0;

                $lon_value = isset($listing['Field']['pairs'][$lon]) ? Sanitize::getVar($listing['Field']['pairs'][$lon]['value'],0) : 0;

                if(!$lat_value || !$lon_value) return 'noresults';

                $center = array('lat'=>$lat_value,'lon'=>$lon_value);
            }
        }

        switch ( $selected )
        {
            case 'rating':

                $order = 'Totals.user_rating DESC, Totals.user_rating_count DESC';

                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;

            case 'rrating':

                $order = 'Totals.user_rating ASC, Totals.user_rating_count DESC';

                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;

            case 'reviews':

                $order = 'Totals.user_comment_count DESC';

                $this->Listing->conditions[] = 'Totals.user_comment_count > 0';
              break;

            case 'rdate':

                $order =  $this->Listing->dateKey ? "Listing.{$this->Listing->dateKey} DESC" : false;
            break;

            case 'proximity':

                $radius = Sanitize::getInt($this->params['module'],'radius');

                $unit = $this->distance_in == 'mi' ? 3956 : 6371; // Mi 3956 // Km 6371

                if($radius > 0) {

                    $degreeDistance = $this->distance_in == 'mi' ? 69.172 : 40076/360;

                    $lat_range = $radius/$degreeDistance;

                    $lon_range = $radius/abs(cos($center['lat']*pi()/180)*$degreeDistance);

                    $min_lat = $center['lat'] - $lat_range;

                    $max_lat = $center['lat'] + $lat_range;

                    $min_lon = $center['lon'] - $lon_range;

                    $max_lon = $center['lon'] + $lon_range;

                    $squareArea = "`Field`.{$lat} BETWEEN {$min_lat} AND {$max_lat} AND `Field`.{$lon} BETWEEN $min_lon AND $max_lon";

                    $this->Listing->conditions[] = $squareArea;
                }
                else {

                    $this->Listing->conditions[] = "`Field`.{$lat} IS NOT NULL AND `Field`.{$lon} IS NOT NULL";
                }

                $this->Listing->fields['distance'] =
                    $unit . " * 2 * ASIN(SQRT(  POWER(SIN(({$center['lat']} - {$lat}) * pi()/180 / 2), 2) +
                    COS({$center['lat']} * pi()/180) *  COS({$lat} * pi()/180) *  POWER(SIN(({$center['lon']} -{$lon}) * pi()/180 / 2), 2)  )) AS `Geomaps.distance`";

                $this->Listing->order[] = '`Geomaps.distance` ASC';

                $this->Listing->having[] = '`Geomaps.distance` >= 0';

            break;
        }

        return $order;
    }

    private function __processListingTypeWidgets(&$conditions)
    {
        $extension = Sanitize::getString($this->params['module'],'extension');

        $extension = $extension != '' ? $extension : 'com_content';

        if($extension != 'com_content') return;

        $widget_type = Sanitize::getString($this->params,'type');

        $key  = Sanitize::getInt($this->params,'key');

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        $listingModel = clone($this->Listing);

        unset($this->Listing->joins['ParentCategory']);

        # Process Listing Type Related Listings settings
        $listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)));

        $this->Listing = $listingModel;

        unset($listingModel);

        $listingTypeSettings = is_array($listing['ListingType']['config'][$widget_type])
            ?
                $listing['ListingType']['config'][$widget_type][$key]
            :
                $listing['ListingType']['config'][$widget_type]
            ;

        if(method_exists($this,'__'.$widget_type)) {

            $this->{'__'.$widget_type}($listing, $listingTypeSettings, $conditions);
        }

        unset($this->params['module']['custom_where'],$this->params['module']['custom_order']);

        $this->params['module'] = array_merge($this->params['module'],$listingTypeSettings);

        // Ensures token validation will pass since we are reading the paramaters directly from the database
        $this->params['token'] = cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false);

        return $listing;
    }

    private function __relatedlistings(&$listing, &$settings, &$conditions)
    {

        $match = Sanitize::getString($settings,'match');

        $curr_fname = Sanitize::getString($settings,'curr_fname');

        $match_fname = Sanitize::getString($settings,'match_fname');

        $created_by = $listing['User']['user_id'];

        $listing_id = $listing['Listing']['listing_id'];

        $cat_id = $listing['Category']['cat_id'];

        $criteria_id = $listing['Criteria']['criteria_id'];

        $title = $listing['Listing']['title'];

        switch($match)
        {
            case 'id':
                // Specified field matches the current listing id
                if($curr_fname != '') {

                    $conditions[] = "`Field`.{$curr_fname} = " . (int) $listing_id;

                    $conditions[] = 'Listing.id <> ' . $listing_id;
                }
                else {

                    $this->abort = true;
                }
            break;

            case 'about':

                // Specified field matches the current listing id
                if($curr_fname != '' && ($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname))) {

                    $value = $field['type'] == 'relatedlisting' ? $field['real_value'][0] : $field['value'][0];

                    $conditions[] = "Listing.id = " . (int) $value;
                }
                else {

                    $this->abort = true;
                }
            break;

            case 'field':
                // Specified field matches the current listing field of the same name
                $field_conditions = array();

                if($curr_fname != '' && ($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname))) {

                    foreach($field['value'] AS $value) {

                        if(in_array($field['type'],array('selectmultiple','checkboxes'))) {

                            $field_conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                        }
                        elseif(in_array($field['type'],array('select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$curr_fname} = " . $this->Quote('*'.$value.'*');
                        }
                        elseif($field['type'] == 'relatedlisting') {

                            $value = $field['real_value'][0];

                            $field_conditions[] = "`Field`.{$curr_fname} = " . (int) $value;
                        }
                        else {

                            $field_conditions[] = "`Field`.{$curr_fname} = " . $this->Quote($value);
                        }
                    }

                    !empty($field_conditions) and $conditions[] = '(' . implode(' OR ', $field_conditions). ')';

                    $conditions[] = 'Listing.id <> ' . $listing_id;
                }
                else {

                    $this->abort = true;
                }
            break;

            case 'diff_field':
                // Specified field matches a different field in the current listing
                $curr_listing_fname = $match_fname;

                $search_listing_fname = $curr_fname;

                $field_conditions = array();

                if($curr_listing_fname != '' && $search_listing_fname != '' && ($curr_field = Sanitize::getVar($listing['Field']['pairs'],$curr_listing_fname))) {

                    if(!($search_field = Sanitize::getVar($listing['Field']['pairs'],$search_listing_fname))) {

                        // Need to query the field type

                        $query = "SELECT fieldid AS field_id,type FROM #__jreviews_fields WHERE name = " . $this->Quote($search_listing_fname);

                        $this->_db->setQuery($query);

                        $search_field = array_shift($this->_db->loadAssocList());

                    }

                    foreach($curr_field['value'] AS $value)
                    {
                        if(in_array($search_field['type'],array('selectmultiple','checkboxes'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                        }
                        elseif(in_array($search_field['type'],array('select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . $this->Quote('*'.$value.'*');
                        }
                        elseif($search_field['type'] == 'relatedlisting' && $curr_field['type'] == 'relatedlisting') {

                            $value = $curr_field['real_value'][0];

                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . (int) $value;
                        }
                        elseif($search_field['type'] == 'relatedlisting' && in_array($curr_field['type'],array('selectmultiple','checkboxes','select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . (int) $value;
                        }
                        else {
                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . $this->Quote($value);
                        }
                    }

                    !empty($field_conditions) and $conditions[] = '(' . implode(' OR ', $field_conditions). ')';

                    $conditions[] = 'Listing.id <> ' . $listing_id;

                }
                else {

                    $this->abort = true;
                }

            break;

            case 'title':
                // Specified field matches the current listing title
                if($curr_fname != '') {

                    // Need to find out the field type. First check if the field exists for this listing type
                    if(!($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname))) {

                        // Need to query the field type

                        $query = "SELECT fieldid AS field_id,type FROM #__jreviews_fields WHERE name = " . $this->Quote($curr_fname);

                        $field = $this->Listing->query($query,'loadAssocList');

                        $field = array_shift($field);
                    }

                    switch($field['type'])
                    {
                        case 'relatedlisting':

                            $this->abort = true;
                        break;

                        case 'text':

                            $conditions[] = "`Field`.{$curr_fname} = " . $this->Quote($title);
                        break;

                        case 'select':
                        case 'selectmultiple':
                        case 'radiobuttons':
                        case 'checkboxes':

                            # Need to find the option value using the option text
                            $query = "
                                SELECT
                                    value
                                FROM
                                    #__jreviews_fieldoptions
                                WHERE
                                    fieldid = " . (int) $field['field_id'] . "
                                    AND
                                    text = " . $this->Quote($title);

                           $value = $this->Listing->query($query,'loadResult');

                           if($value != '') {

                                if(in_array($field['type'],array('select','radiobuttons'))) {

                                    $conditions[] = "`Field`.{$curr_fname} = " . $this->Quote('*'.$value.'*');
                                }
                                else {

                                    $conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                                }
                           }
                           else {

                               $this->abort = true;
                           }
                        break;
                    }

                    $conditions[] = 'Listing.id <> ' . $listing_id;
                }
            break;

            case 'owner':
                // The listing owner matches the current listing owner
                $conditions[] = 'Listing.created_by = ' . $created_by;

                $conditions[] = 'Listing.id <> ' . $listing_id;
            break;

            case 'listing_type':

                // Only filters by listing type
                $conditions[] = 'Listing.id <> ' . $listing_id;
            break;

            case 'cat_auto':

                // Listing category matches the current listing category
                $conditions[] = 'Listing.catid = ' . $cat_id;

                $conditions[] = 'Listing.id <> ' . $listing_id;
            break;
        }
    }

    function injectDistanceGroup($listings)
    {
        foreach($listings AS $key=>$listing) {

            if(!isset($listing['Geomaps']) && !isset($listing['Geomaps']['distance'])) continue;

            $field = array('jr_gm_distance'=>array (
                    'id' => 99999,
                    'group_id' => 'distance',
                    'name' => 'jr_gm_distance',
                    'type' => 'decimal',
                    'title' => __t("Distance",true),
                    'description' => '',
                    'value' => array($listing['Geomaps']['distance']),
                    'text' => array($listing['Geomaps']['distance']),
                    'image' => array(),
                    'properties' => array
                        (
                            'show_title' => 1,
                            'location' => 'content',
                            'contentview' => 0,
                            'listview' => Sanitize::getInt($this->Config,'geomaps.publish_distance',1),
                            'listsort' => 1,
                            'search' => 0,
                            'access' => implode(',',$this->Access->guests),
                            'access_view' => implode(',',$this->Access->guests),
                            'valid_regex' => '',
                            'allow_html' => 0,
                            'click2searchlink' => '',
                            'output_format' => '{FIELDTEXT} '.$this->distance_metric[$this->distance_in],
                            'click2search' => 0,
                            'click2add' => 0
                        ),
                )
            );

            $group = array('Geomaps'=>array(
                'Group'=>array(
                    'group_id'=>'distance',
                    'title'=>'Proximity Search',
                    'name'=> 'Geomaps',
                    'show_title'=>0),
                'Fields'=>$field
            ));

            !empty($listing['Field']['groups']) and $listings[$key]['Field']['groups'] = array_merge($group,$listing['Field']['groups']);

            !empty($listing['Field']['pairs']) and $listings[$key]['Field']['pairs'] = array_merge($field,$listing['Field']['pairs']);
        }

        return $listings;
    }
}
