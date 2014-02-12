<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComponent extends S2Component {

    var $name = 'everywhere';

    var $everywhereModel = null;

    var $published = true;

    var $plugin_order = 0;

    var $validObserverModels = array('Media','Review','ReviewReport','Inquiry');

    function startup(&$controller) {

        $this->c = & $controller;

        $this->app = $this->c->app;

        // Check if there's an observer model in the controller
        if(method_exists($this->c,'getEverywhereModel'))
        {
            $this->everywhereModel = $controller->getEverywhereModel();

            if($this->everywhereModel && in_array($this->everywhereModel->name,$this->validObserverModels)) {


                $this->everywhereModel->addObserver('plgAfterFind',$this);
                $this->everywhereModel->addObserver('plgAfterSave',$this);
            }
        }

        $this->loadListingModel($this->c);
    }

	// Loops through each of the models and queries the listing data for the specified listing ids
	function runEverywhereModels($models, $extensions)
	{
		foreach($extensions AS $key=>$value)
		{
			$extensions[$key] = array_unique($value);
		}

		# Verify model files exist or unset them
		foreach($models AS $extension=>$EveryWhereModel) {

			if(!file_exists(S2Paths::get('jreviews', 'S2_MODELS') . $this->name . DS . $EveryWhereModel . '.php')) {

            	unset($models[$extension]);
			}
		}

		$this->__initModels(array_values($models),$this->app);

		# Loop through extensions found in the current page
		foreach($extensions AS $extension=>$listing_ids)
		{
			if(isset($models[$extension]))
			{
                $class_name = inflector::camelize($models[$extension]).'Model';

                if(!call_user_func(array($class_name,'exists'))) {

                    continue;
                }

				if(!is_array($listing_ids)) $listing_ids = array($listing_ids);
				// Uses the current extension's model to get Listing info for the given listing ids
				$realKey = $this->{inflector::camelize($models[$extension])}->realKey;

				// Stops for all runs unless it's specifically added via the runAfterFindModel method
				$this->{inflector::camelize($models[$extension])}->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder'));

				// Add exceptions to afterFindModel so queries from other models are not always run.
				// For example, it's not necessary to get media info on every listing and review query
				switch($this->everywhereModel->name)
				{
                    case 'Inquiry':


                        break;

					case 'Media':

							if($this->c->action != 'download') {

								$this->{inflector::camelize($models[$extension])}->addRunAfterFindModel('Media');

							}

						break;

					case 'Review':

						if(($this->c->name == 'module_reviews' && $this->c->action == 'index')
                            ||
                            ($this->c->name == 'community_reviews' && $this->c->action == 'index')
                            ||
                            ($this->c->name == 'reviews' && in_array($this->c->action,array('_edit','_save','_saveModeration')))
                            ||
                            ($this->c->name == 'discussions' && in_array($this->c->action,array('review')))
                            ||
                            ($this->c->name == 'feeds' && in_array($this->c->action,array('reviews')))
                            )
						{
								$this->{inflector::camelize($models[$extension])}->addRunAfterFindModel('Media');

						}

						break;

					case 'ReviewReport':


						break;

				}

                if($models[$extension] == 'everywhere_com_content') {
                    unset($this->{inflector::camelize($models[$extension])}->joins['ParentCategory']);
                }

				$listings[$extension] = $this->{inflector::camelize($models[$extension])}->findAll(array('conditions'=>'Listing.' . $realKey . ' IN ('. implode(',',$listing_ids) . ')'));
			}
		}

		return $listings;
	}

    /**
     * Model observer adds listing information to other models
     */
    function plgAfterFind(&$model, $results)
    {
        if(empty($results)) {
            return $results;
        }

        $extensions = array();

        $models = array();

        switch($this->everywhereModel->name)
        {
            case 'Media':

                if(isset($this->c->EverywhereAfterFind) &&  $this->c->EverywhereAfterFind === true)
                {
                    # Build extension and listing_id array
                    foreach($results AS $result) {
                        $extensions[$result['Media']['extension']][] = $result['Media']['listing_id'];
                        $models[$result['Media']['extension']] = $this->name.'_'.$result['Media']['extension'];
                    }

					// Get the listings data for each everywhere extension
					$listings = $this->runEverywhereModels($models, $extensions);

                    # Merge the listing data to the Media Model results
                    foreach($results AS $key=>$result)
                    {
                        if(isset($listings[$result['Media']['extension']])
                            && isset($listings[$result['Media']['extension']][$result['Media']['listing_id']]))
                        {
                            // Second condition above excludes reviews when the listing is not found for the review (i.e. the category for the listing was removed)

							if(defined('MVC_FRAMEWORK_ADMIN')
                                ||
                                ($this->c->name == 'media' && in_array($this->c->action,array('photoGallery','videoGallery')))
                            )
                            {
                                // Exclude listing owner info because it replaces the reviewer info
                                if(isset($listings[$result['Media']['extension']][$result['Media']['listing_id']]['User']))
                                {
                                    if(isset($listings[$result['Media']['extension']][$result['Media']['listing_id']]['User']))
                                    {
                                        $listings[$result['Media']['extension']][$result['Media']['listing_id']]['ListingUser'] = $listings[$result['Media']['extension']][$result['Media']['listing_id']]['User'];
                                        unset($listings[$result['Media']['extension']][$result['Media']['listing_id']]['User']);
                                    }

                                    if(isset($listings[$result['Media']['extension']][$result['Media']['listing_id']]['Community']))
                                    {
                                        $listings[$result['Media']['extension']][$result['Media']['listing_id']]['ListingCommunity'] = $listings[$result['Media']['extension']][$result['Media']['listing_id']]['Community'];
                                        unset($listings[$result['Media']['extension']][$result['Media']['listing_id']]['Community']);
                                    }
                                }
							}

                            # Merge listing and media arrays. When there are duplicates Media keys trump Listing keys (i.e. Review, Rating, Field, ...)
                            $results[$key] = array_insert($listings[$result['Media']['extension']][$result['Media']['listing_id']], $results[$key]);
                        }
                        else
                        {
                            unset($results[$key]); // Removes reviews for extensions without Models
                        }
                    }

                    # Preprocess criteria and rating information
                    $rating_test = current($results);

                    if($this->c->Review && isset($rating_test['Rating']))
                    {
                        $results = $this->c->Review->processRatings($results);
                    }

                    return $results;
                }

                break;

            case 'Inquiry':

                if(Sanitize::getBool($this->c,'EverywhereAfterFind') === true)
                {
                    # Build extension and listing_id array
                    foreach($results AS $result)
                    {
                        $extensions['com_content'][] = $result['Inquiry']['listing_id'];

                    }

                    $models['com_content'] = 'everywhere_com_content';

                    // Get the listings data for each everywhere extension
                    $listings = $this->runEverywhereModels($models, $extensions);

                    # Merge the listing data to the Inquiry Model results
                    foreach($results AS $key=>$result)
                    {
                        if(isset($listings['com_content'][$result['Inquiry']['listing_id']]))
                        {
                            # Merge listing and media arrays. When there are duplicates Media keys trump Listing keys (i.e. Review, Rating, Field, ...)
                            $results[$key] = array_insert($listings['com_content'][$result['Inquiry']['listing_id']], $results[$key]);
                        }
                    }

                }

                break;

            case 'Review':

                if(isset($this->c->EverywhereAfterFind) &&  $this->c->EverywhereAfterFind === true)
                {
                    # Build extension and listing_id array
                    foreach($results AS $result) {

                        $extensions[$result['Review']['extension']][] = $result['Review']['listing_id'];

                        $models[$result['Review']['extension']] = $this->name.'_'.$result['Review']['extension'];
                    }

					if($this->c->action != '_save')
					{
						$runAfterFindModels = array('PaidOrder');
					}

					// Get the listings data for each everywhere extension
					$listings = $this->runEverywhereModels($models, $extensions);

                    # Merge the listing data to the Review Model results
                    foreach($results AS $key=>$result)
                    {
                        if(isset($listings[$result['Review']['extension']])
                            && isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]))
                        {
                            // Second condition above excludes reviews when the listing is not found for the review (i.e. the category for the listing was removed)
                            if( defined('MVC_FRAMEWORK_ADMIN')
                                ||
                                ($this->c->name == 'reviews' && in_array($this->c->action,array('myreviews','_save','_edit','latest','latest_editor','latest_user')))
                                ||
                                ($this->c->name == 'reviews' && $this->c->action == 'moderation') // Admin controller
                                ||
                                ($this->c->name == 'module_reviews' && $this->c->action == 'index')
                                ||
                                ($this->c->name == 'community_reviews' && $this->c->action == 'index')
                                ||
                                ($this->c->name == 'feeds')
                                ||
                                ($this->c->name == 'discussions' && $this->c->action == 'review')
                            )
                            {
                                // Exclude listing owner info because it replaces the reviewer info
                                if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']))
                                {
                                    $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingUser'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['User'];
                                    unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']);

                                    if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community']))
                                    {
                                        $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingCommunity'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community'];
                                        unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community']);
                                    }
                                }

                                // Exclude listing media info because it replaces the reviewer media info
                                if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Media']))
                                {
                                    $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingMedia'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['Media'];

                                    unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Media']);
                                }

                                // Exclude rating info because it replaces the reviewer ratings

                                if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Rating']))
                                {
                                    $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingRating'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['Rating'];

                                    unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Rating']);
                                }

                                if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Review']))
                                {
                                    $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingReview'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['Review'];

                                    unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Review']);
                                }

                            }

                            # Merge listing and review arrays. When there are duplicates Review keys trump Listing keys (i.e. Review, Rating, Field, ...)
                            $results[$key] = array_insert($listings[$result['Review']['extension']][$result['Review']['listing_id']], $results[$key]);
                        }
                        else {

                            unset($results[$key]); // Removes reviews for extensions without Models
                        }
                    }

                    # Preprocess criteria and rating information
                    $rating_test = current($results);

                    if(isset($rating_test['Rating'])){

                        $results = $this->c->Review->processRatings($results);
                    }

                    return $results;
                }

                break;

            case 'ReviewReport':

                    # Build extension and listing_id array
                    foreach($results AS $result)
                    {
                        $extensions[$result['Review']['extension']][] = $result['Review']['listing_id'];
                        $models[$result['Review']['extension']] = $this->name.'_'.$result['Review']['extension'];
                    }

					// Get the listings data for each everywhere extension
					$listings = $this->runEverywhereModels($models, $extensions);

                    # Merge the listing data to the Review Model results
                    foreach($results AS $key=>$result)
                    {
                        if(isset($listings[$result['Review']['extension']]))
                        {
                            $results[$key] = array_insert($results[$key],$listings[$result['Review']['extension']][$result['Review']['listing_id']]);
                        }
                        else
                        {
                            unset($results[$key]); // Removes reviews for extensions without Models
                        }
                    }

                    # Preprocess criteria and rating information
                    $rating_test = current($results);
                    if(isset($rating_test['Rating'])) {
                        $results = $this->c->Review->processRatings($results);
                    }

                    return $results;

                break;

        }

        return $results;
    }

    /**
     * Model observer for review after save actions
     * It can be used to update other extension tables by adding the afterSave method to the Everywhere Models
     */
    function plgAfterSave(&$model) {

//        appLogMessage('Average Rating: '. $model->data['average_rating'],'plgAfterSave');
//        appLogMessage('New: '. $model->data['new'],'plgAfterSave');

        return true;
    }

    /**
     * Used in queries for objects tied to listings (media for now) to retrieve only those for published listings.
     * @param  array $queryData
     * @param  array $keys
     * @return array
     */
    function createUnionQuery($queryData,$keys)
    {
        $queryDataArray = array();

        $extension = Sanitize::getString($queryData,'extension');

        unset($queryData['extension']);

        // Get the list of active Everywhere extensions
        S2App::import('Model','jreviews_category','jreviews');

        $JreviewsCategory = ClassRegistry::getClass('JreviewsCategoryModel');

        $extensions = $JreviewsCategory->getEverywhereExtensions();

        if($extension != '' && isset($extensions[$extension])) {

            $extensions = array($extension=>$extensions[$extension]);
        }

        foreach($extensions AS $extension=>$setup) {

            $queryDataArray[$extension] = $queryData;

            if(isset($setup['listing_join'])) {

                foreach($setup['listing_join'] AS $key=>$join) {

                    $setup['listing_join'][$key] = sprintf($join,$keys['listing_id']);
                }

                $queryDataArray[$extension]['conditions'][] = $keys['extension'] . ' = ' . $this->c->Quote($extension);

                if(!isset($queryData['joins'])) {

                    $queryDataArray[$extension]['joins'] = $setup['listing_join'];
                }
                else {

                    $queryDataArray[$extension]['joins'] = array_merge($setup['listing_join'],$queryDataArray[$extension]['joins']);
                }

            }
        }

        $queryDataArray['union'] = true;

        return $queryDataArray;
    }

    /**
     * Dynamic Listing Model Loading for jReviewsEverywhere extensions
     * Detects which extension is being used to load the correct Listing model
     *
     * @param object $controller
     * @param string $extension
     */
    function loadListingModel(&$controller, $extension = null)
    {

        $menu_id = Sanitize::getInt($controller->params,'Itemid');

       if(in_array($controller->name,array('admin/admin_reviews','reviews')) && in_array($controller->action,array('_save','_saveModeration')))
       {
            $extension = Sanitize::getString($controller->data['Review'],'mode');

            !$extension and isset($controller->data['Listing']) and $extension = Sanitize::getString($controller->data['Listing'],'extension');
       }
       elseif($controller->name == 'media_upload' && $controller->action == 'create') {

            $id = explode(':',base64_decode(urldecode(Sanitize::getString($controller->params,'id'))));

            switch(count($id)) {

                case 2: // Listing

                    $listing_id = (int) array_shift($id);
                break;

                case 3: // Review

                    $listing_id = (int) array_shift($id);

                    $review_id = (int) array_shift($id);
                break;

                default:
                break;
            }

            $extension = array_shift($id);
       }
       elseif($controller->name == 'listings' && $controller->action == 'detail') {

            $menuParams = $controller->Menu->getMenuParams($menu_id);

            $extension = Sanitize::getString($menuParams,'extension');
       }

	   if(!$extension) {

		   $extension =  Sanitize::getString($controller->params,'extension',Sanitize::getString($controller->data,'extension'));
	   }

	   if(!$extension && isset($this->everywhereModel)) {

			if(isset($controller->data[$this->everywhereModel->name])) {

				$extension = Sanitize::getVar($controller->data[$this->everywhereModel->name],'extension',null);
			}
	   }

       // For module/community controllers, override the page extension
       if(isset($controller->params['module']))
	   {
           $cat_auto = Sanitize::getInt($controller->params['module'],'cat_auto');

           $cat_id = Sanitize::getString($controller->params['module'],'cat_id');

           $everywhere_extension = Sanitize::getString($controller->params['module'],'extension');

           // Dealing with Modules ....
           // If extension setting is setup in module, then we use it and stop the autoload feature from loading
           // a different Listing model
           if($everywhere_extension != '') {

                // If cat id is specified then don't load the Listing model here
                $extension = $cat_id ? '' : $everywhere_extension;
           }
           // If cat auto enabled, but no extension given, then we use the one for the page if it ones discovered
           elseif($cat_auto && $extension != '') {

                $controller->params['module']['extension'] = $extension;
           }
           // If cat auto enabled and no extension can be found, then we force it to com_content
           elseif($cat_auto) {

                $controller->params['module']['extension'] = $extension = 'com_content';
           }
           // Don't load the Listing Model
           else {

                $extension = '';
           }
       }


       $extension == ''
            and $controller->name != 'facebook'
            and $controller->name != 'reviews'
            and $controller->name != 'community_reviews'
            and $controller->name != 'module_reviews'
            and $controller->name != 'discussions'
            // admin controllers
            and $controller->name != 'admin/admin_reviews'
            and $controller->name != 'admin/admin_owner_replies'
            and $controller->name != 'admin/admin_reports'
            and $controller->name != 'admin/admin_discussions'
            and $extension = 'com_content';

        // Check if in listing detail page and it's a 3rd party component to dynamically load it's Listing model

		if($extension)
        {
            $name = $this->name . '_' . $extension;

            S2App::import('Model',$name,'jreviews');

            $class_name = inflector::camelize($this->name.'_'.$extension).'Model';

            if(class_exists($class_name))
            {
                $controller->Listing = new $class_name($controller->params);

				$controller->Listing->controller_name = $controller->name;

				$controller->Listing->controller_action = $controller->action;

				if(isset($controller->Review) && $controller->action != '_save')
				{
                    $controller->Review->joins = array_merge($controller->Review->joins,$controller->Listing->joinsReviews);

					if(isset($controller->Listing->groupReviews) && isset($controller->Listing->groupReviews) != '') {

	                    $controller->Review->group = array_merge($controller->Review->group,$controller->Listing->groupReviews);
					}
                }

				// Completes the query data with required joins for category filtering in everywhere extensions
				// Support for media module only for the time being
				if($extension != 'com_content' &&
                    isset($controller->Listing->joinsMedia) &&
					$controller->name == 'module_media'
				){
                    $controller->Media->joins = array_merge($controller->Media->joins,$controller->Listing->joinsMedia);

                    if(isset($controller->Media->groupReviews) && isset($controller->Listing->groupMedia) != '') {

                        $controller->Media->group = array_merge($controller->Media->group,$controller->Listing->groupMedia);
					}
				}

            } else {
                // Extension used in url doesn't have a plugin so we redirect to 404 error page

                $controller->autoLayout = false;

                $controller->autoRender = true;

                cmsFramework::redirect(cmsFramework::route('index.php?option=com_jreviews&url=404'));
            }
        }

    }
}
