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

class ModuleMediaController extends MyController {

    var $uses = array('menu','media','user','review');

    var $helpers = array('cache','libraries','html','assets','paginator','form','routes','text','time','community','media');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    var $layout = 'module';

    var $abort = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Media;
    }

	function index()
	{
/*        if($this->_user->id === 0)
        {
            $this->cacheAction = Configure::read('Cache.expires');
        }   */
// return;
		$joins = array();

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

		$module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

		if(empty($this->params))
        {
            $query = "SELECT params FROM #__modules WHERE id = " . $module_id;

            $params = $this->Media->query($query,'loadResult');

            $this->params['module'] = stringToArray($params);
        }

        $ids = $conditions = $joins = $order = array();

		# Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');

		$extension = Sanitize::getString($this->params['module'],'extension');

        $media_type = Sanitize::getString($this->params['module'],'media_type');

		$cat_id = Sanitize::getString($this->params['module'],'category');

		$listing_id = Sanitize::getString($this->params['module'],'listing');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        $custom_order = Sanitize::getString($this->params['module'],'custom_order');

        $custom_where = Sanitize::getString($this->params['module'],'custom_where');

        $media_by = Sanitize::getString($this->params['module'],'media_by');

		if($extension == 'com_content') {

			$dir_id = Sanitize::getString($this->params['module'],'dir');

            $criteria_id = Sanitize::getString($this->params['module'],'criteria');
		}
        else {

            $dir_id = null;

            $criteria_id = null;
		}

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');

        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'media_order');

		# Category auto detect
        if($cat_auto && $extension == 'com_content')
		{
            $ids = CommonController::_discoverIDs($this);

            extract($ids);
        }

		$extension != '' and $conditions[] =  "Media.Extension = " . $this->Quote($extension);

        if($custom_where != '') {

            $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);
        }

		# Set conditionals based on configuration parameters
		if($extension == 'com_content')
		{
			$subquery_conditions = $subquery_joins = array();

            // If custom where includes conditions for Listing or Field models then we add a join to those tables
            if($custom_where != '' && (strstr($custom_where,'Field.') || strstr($custom_where,'Listing.'))) {

                $joins['Field'] = "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Media.listing_id";
            }

            // Perform tag replacement for listing_id
            if($custom_where != '' && Sanitize::getString($this->params,'view') == 'article') {

                $curr_listing_id = Sanitize::getInt($this->params,'id');

                $custom_where = str_replace(
                    array('{listing_id}'),
                    array($curr_listing_id),
                    $custom_where);
            }

            $access_levels = $this->Access->getAccessLevels();

            $subquery_conditions[] = 'Media.access IN ( ' . $access_levels . ')';

            $subquery_conditions[] = 'Category.access IN (' . $access_levels . ')';

            $subquery_conditions[] = 'Listing.access IN ( ' . $access_levels . ')';

            if(!empty($cat_id))
            {
                $subquery_joins[] = "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt";

                $subquery_conditions[] = 'ParentCategory.id IN ('.cleanIntegerCommaList($cat_id).')';
            }

            empty($cat_id) and !empty($dir_id) and $subquery_conditions[] = 'JreviewsCategory.dirid IN (' . cleanIntegerCommaList($dir_id) . ')';

            empty($cat_id) and !empty($criteria_id) and $subquery_conditions[] = 'JreviewsCategory.criteriaid IN (' . cleanIntegerCommaList($criteria_id) . ')';

			// Filter for published listings and valid view access
			$conditions[] = "Media.listing_id IN (

				SELECT
					Listing.id
				FROM
					#__content AS Listing
				LEFT JOIN
					#__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.option = 'com_content'
				LEFT JOIN
					#__categories AS Category ON Category.id = JreviewsCategory.id
				" . (!empty($subquery_joins) ? implode(' ',$subquery_joins) : '') . "
				WHERE
					Listing.state = 1
					AND
					( Listing.publish_up = '".NULL_DATE."' OR Listing.publish_up <= '"._END_OF_TODAY."' )
					AND
					( Listing.publish_down = '".NULL_DATE."' OR Listing.publish_down >= '"._TODAY."' )

				" . (!empty($subquery_conditions) ? ' AND ' . implode(' AND ',$subquery_conditions) : '') . "

			)
			";

            if($media_by == 'owner') {

                $conditions[] = "Media.user_id = Listing.created_by";
            }
            elseif($media_by == 'user') {

                $conditions[] = "Media.user_id != Listing.created_by";
            }
		}
        else
        {
			if($cat_auto && isset($this->Listing) && method_exists($this->Listing,'catUrlParam'))
			{
				if($cat_id = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){

					$conditions[] = 'JreviewsCategory.id IN (' . $cat_id. ')';
				}

			}
			elseif($cat_id)  {

				$conditions[] = 'JreviewsCategory.id IN (' . cleanIntegerCommaList($cat_id). ')';
			}

		}

		$listing_id and $conditions[] = "Media.listing_id IN ( ". cleanIntegerCommaList($listing_id) .")";

		$conditions[] = 'Media.published > 0 AND Media.approved > 0';

        # Modify query for correct ordering.
        if($tokenMatch and $custom_order) {

            $order[] = $custom_order;
        }
        else {

            switch($sort) {
                case 'recent':
                    $order[] = $this->Media->processSorting('newest');
                    break;
                case 'liked':
                    $order[] = $this->Media->processSorting('liked');
                    break;
                case 'views':
                    $order[] = $this->Media->processSorting('popular');
                    break;
            }
        }

        switch($media_type)
        {
            case 'all':
            break;
            default:
                $conditions[] = 'Media.media_type = ' . $this->Quote($media_type);
            break;
        }

        # Custom WHERE
        $tokenMatch and $custom_where and $conditions[] = $custom_where;

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total,
            'extension'=>$extension
		);

        // Makes sure only media for published listings is shown
        $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Media.listing_id','extension'=>'Media.extension'));

		$media = $this->Media->findAll($queryData);

        $count = count($media);

		# Send variables to view template
		$this->set(
			array(
                'autodetect_ids'=>$ids,
				'entries'=>$media,
				'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = empty($media) ? '' : $this->render('modules','media');

/*        if($this->_user->id === 0 && $this->ajaxRequest)
        {
            $path = $this->here;

            $this->here == '/' and $path = 'home';

            $cache_fname = Inflector::slug($path) . '.php';

            $now = time();

            $cacheTime = is_numeric($this->cacheAction) ? $now + $this->cacheAction : strtotime($this->cacheAction, $now);

            $fileHeader = '<!--cachetime:' . $cacheTime . '-->';

            cache('views' . DS . $cache_fname, $fileHeader . $this->ajaxResponse($page,false), $this->cacheAction);
        }*/

        return $page;
	}


    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'summary'=>false,
            'summary_words'=>10,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>true,
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom',
            'media_type_icon'=>1,
            'media_by'=>'all'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

   /**
    * Modifies the query ORDER BY statement based on ordering parameters
    */
     private function __processSorting($selected)
    {
        $order = '';

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
        }

        return $order;
    }

}
