<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityReviewsController extends MyController {

	var $uses = array('user','menu','category','review','field','criteria','media');

	var $helpers = array('routes','paginator','libraries','html','assets','text','time','jreviews','community','custom_fields','rating','media');

	var $components = array('config','access','everywhere','media_storage');

	var $autoRender = false;

	var $autoLayout = false;

	var $layout = 'module';

	function beforeFilter() {

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
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
		$module_id = Sanitize::getVar($this->params,'module_id',Sanitize::getVar($this->data,'module_id'));

		if(!Sanitize::getVar($this->params['module'],'community')) {
			cmsFramework::noAccess();
			return;
		}

		$conditions = array();
		$joins = array();
		$order = array();

		// Initialize variables
		$id = Sanitize::getInt($this->params,'id');
		$option = Sanitize::getString($this->params,'option');
		$view = Sanitize::getString($this->params,'view');
		$task = Sanitize::getString($this->params,'task');
		$menu_id = Sanitize::getString($this->params,'Itemid');

		# Read module parameters
		$extension = Sanitize::getString($this->params['module'],'extension');
		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);
        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);
        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));

		if(!$user_id && !$this->_user->id) {
			cmsFramework::noAccess();
			return;
		}

		$cat_id = Sanitize::getString($this->params['module'],'category');
		$listing_id = Sanitize::getString($this->params['module'],'listing');

		if($extension == 'com_content')
        {
			$dir_id = Sanitize::getString($this->params['module'],'dir');
			$criteria_ids = Sanitize::getString($this->params['module'],'criteria');
		}
        else
        {
			$dir_id = null;
			$criteria_ids = null;
		}

		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'reviews_order');

		# Remove unnecessary fields from model query
//		$this->Review->modelUnbind();
		!empty($extension) and $conditions[] =  "Review.mode = '$extension'";

		$conditions[] = "Review.userid = " . (int) $user_id;

		# Set conditionals based on configuration parameters
		if($extension == 'com_content')
		{ // Only works for core articles
            $conditions = array_merge($conditions,array(
                '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )',
                '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )',
                'Listing.catid > 0'
            ));

            $conditions[] = 'Category.access IN (' . $this->Access->getAccessLevels() . ')';

            $conditions[] = 'Listing.access IN ( ' . $this->Access->getAccessLevels() . ')';

            $conditions[] = $this->Access->canEditListing() ? 'Listing.state >= 0' :  'Listing.state = 1';

			!empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . $dir_id . ')';

            if(!empty($cat_id))
            {
                $conditions[] = 'ParentCategory.id IN ('.$cat_id.')';
            }
            else
            {
                unset($this->Listing->joins['ParentCategory']);
            }
        }
        else
        {
			if(Sanitize::getInt($this->params['module'],'cat_auto') && method_exists($this->Listing,'catUrlParam'))
            {
				if($cat_id = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam()))
                {
					$conditions[] = 'JreviewsCategory.id IN (' . $cat_id. ')';
				}
			}
            elseif($cat_id)
            {
				$conditions[] = 'JreviewsCategory.id IN (' . $cat_id . ')';
			}
		}

		!empty($listing_id) and $conditions[] = "Review.pid IN ($listing_id)";

		$conditions[] = 'Review.published > 0';

		switch($sort) {
			case 'latest':
				$order[] = $this->Review->processSorting('rdate');
				break;
			case 'helpful':
				$order[] = $this->Review->processSorting('helpful');
				break;
			case 'random':
                srand((float)microtime()*1000000);
                $this->params['rand'] = rand();
				$order[] = 'RAND('.$this->params['rand'].')';
				break;
			default:
				$order[] = $this->Review->processSorting('rdate');
				break;
		}

		$queryData = array(
			'fields'=>array(
//				'Review.mode AS `Review.extension`'
			),
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
                'module_id'=>$module_id,
				'reviews'=>$reviews,
				'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($reviews) ? '' : $this->render('community_plugins','community_myreviews');

        return $page;
	}

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
            'nav_position'=>'bottom',
            'link_title'=>'{listing_title}'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

}
