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

class ModuleReviewsController extends MyController
{
	var $uses = array('user','menu','category','review','field','criteria','media');

	var $helpers = array(/*'cache',*/'paginator','routes','libraries','html','assets','text','time','jreviews','community','custom_fields','rating','media');

	var $components = array('config','access','everywhere','media_storage');

	var $autoRender = false;

	var $autoLayout = false;

	var $layout = 'module';

	function beforeFilter() {

        Configure::write('ListingEdit',false);

        # Call beforeFilter of MyController parent class
		parent::beforeFilter();

		# Stop AfterFind actions in Review model
		$this->Review->rankList = false;

	}

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }

	function index()
	{
        // return;

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

		$module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

		if(empty($this->params))
        {
            $query = "SELECT params FROM #__modules WHERE id = " . $module_id;

            $params = $this->Review->query($query,'loadResult');

            $this->params['module'] = stringToArray($params);
        }

        $ids = $conditions = $joins = $order = array();

		# Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');

		$extension = Sanitize::getString($this->params['module'],'extension');

        $reviews_type = Sanitize::getString($this->params['module'],'reviews_type');

        $cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        $custom_order = Sanitize::getString($this->params['module'],'custom_order');

        $custom_where = Sanitize::getString($this->params['module'],'custom_where');

		if($extension == 'com_content') {

			$dir_id = Sanitize::getString($this->params['module'],'dir');

            $criteria_id = Sanitize::getString($this->params['module'],'criteria');
		}
        else {

        	$dir_id = null;

        	$criteria_id = null;
		}

        if($custom_where != '') {

            $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);
        }

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');

        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'reviews_order');

        if(in_array($sort,array('random'))) {
            srand((float)microtime()*1000000);
            $this->params['rand'] = rand();
        }

		# Category auto detect
        if($cat_auto && $extension == 'com_content')
		{
            $ids = CommonController::_discoverIDs($this);
            extract($ids);
        }

		$extension != '' and $conditions[] =  "Review.mode = " . $this->Quote($extension);

        // If custom where includes conditions for ReviewField model then we add a join to those tables
        if($custom_where != '' && strstr($custom_where,'ReviewField.')) {

            $joins['ReviewField'] = "LEFT JOIN #__jreviews_review_fields AS ReviewField ON ReviewField.reviewid = Review.id";
        }

		# Set conditionals based on configuration parameters
		if($extension == 'com_content')
		{
            // If custom where includes conditions for Field models then we add a join to those tables
            if($custom_where != '' && strstr($custom_where,'Field.')) {

                $joins['Field'] = "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id";
            }

            // Perform tag replacement for listing_id
            if($custom_where != '' && Sanitize::getString($this->params,'view') == 'article') {

                $curr_listing_id = Sanitize::getInt($this->params,'id');

                $custom_where = str_replace(
                    array('{listing_id}'),
                    array($curr_listing_id),
                    $custom_where);
            }

            $conditions = array_merge($conditions,array(
                'Listing.state = 1',
                '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )',
                '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )'
            ));

            $conditions[] = 'Category.access IN (' . $this->Access->getAccessLevels() . ')';

            $conditions[] = 'Listing.access IN ( ' . $this->Access->getAccessLevels() . ')';

            if(!empty($cat_id))
            {
                $this->Review->joins['ParentCategory'] = "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt";

                $conditions[] = 'ParentCategory.id IN ('.cleanIntegerCommaList($cat_id).')';
            }

            empty($cat_id) and !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . cleanIntegerCommaList($dir_id) . ')';

            empty($cat_id) and !empty($criteria_id) and $conditions[] = 'JreviewsCategory.criteriaid IN (' . cleanIntegerCommaList($criteria_id) . ')';
		}
        elseif ($extension != '') {

			if($cat_auto && isset($this->Listing) && method_exists($this->Listing,'catUrlParam')) {

            	if($cat_id = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){

            		$conditions[] = 'JreviewsCategory.id IN (' . $cat_id. ')';

            	}

            }
            elseif($cat_id) {

            	$conditions[] = 'JreviewsCategory.id IN (' . cleanIntegerCommaList($cat_id). ')';
			}
		}

		$listing_id and $conditions[] = "Review.pid IN ( ". cleanIntegerCommaList($listing_id) .")";

		$conditions[] = 'Review.published > 0';

        # Modify query for correct ordering.
        if($tokenMatch and $custom_order) {

            $order[] = $custom_order;
        }
        else {

            switch($sort) {
                case 'latest':
                    $order[] = $this->Review->processSorting('rdate');
                    break;
                case 'helpful':
                    $order[] = $this->Review->processSorting('helpful');
                    break;
                case 'random':
                    $order[] = 'RAND('.$this->params['rand'].')';
                    break;
                default:
                    $order[] = $this->Review->processSorting('rdate');
                    break;
            }
        }

        switch($reviews_type)
        {
            case 'all':
            break;
            case 'user':
                $conditions[] = 'Review.author = 0';
            break;
            case 'editor':
                $conditions[] = 'Review.author = 1';
            break;
        }

        # Custom WHERE
        $tokenMatch and $custom_where and $conditions[] = $custom_where;

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		// Excludes listing owner info in Everywhere component
		$this->Review->controller = 'module_reviews';

        $reviews = $this->Review->findAll($queryData);

        $count = count($reviews);

		# Send variables to view template
		$this->set(
			array(
                'autodetect_ids'=>$ids,
				'reviews'=>$reviews,
				'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($reviews) ? '' : $this->render('modules','reviews');

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
            'show_comments'=>false,
            'comments_words'=>10,
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
}
