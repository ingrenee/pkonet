<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingsController extends MyController {

    var $uses = array('article','user','menu','claim','category','jreviews_category','review','favorite','field','criteria','captcha','vote','media');

    var $helpers = array('cache','routes','libraries','html','text','assets','form','time','jreviews','community','editor','custom_fields','rating','paginator','widgets','media');

    var $components = array('config','access','everywhere','media_storage');

    var $formTokenKeys = array('id'=>'listing_id');

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        # Make configuration available in models
        $this->Listing->Config = &$this->Config;
    }

    function getPluginModel() {
        return $this->Listing;
    }

    function getNotifyModel() {
        return $this->Listing;
    }

    function getEverywhereModel() {
        // Completes the review with listing info for each Everywhere component
        return $this->Review;
    }

    // Need to return object by reference for PHP4
/*    function &getObserverModel()
    {
        return $this->Listing;
    }    */

    function detail()
    {
        $this->viewVarsAssets = array('listing');

        if($this->_user->id === 0) {
            $this->cacheAction = Configure::read('Cache.expires');
        }

        $this->autoRender = true;

        $this->autoLayout = true;

        $this->layout = 'detail';

        # Initialize vars
        $editor_review = array();

        $review_fields = array();

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $listing_id = Sanitize::getInt($this->params,'id');

        $extension = Sanitize::getString($this->params,'extension');

        if($extension == '' && $menu_id) {

            $menuParams = $this->Menu->getMenuParams($menu_id);

            $extension = Sanitize::getString($menuParams,'extension');
        }

        $extension == '' and $extension = 'com_content';

        $listing = $this->Listing->findRow(array('conditions'=>array("Listing.{$this->Listing->realKey} = ". $listing_id)));

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        $sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla')
                        && Sanitize::getString($this->params,'order') != '';

        $sort == '' and $sort = $this->Config->user_review_order;

        $this->params['order'] = $sort;

        if(!$listing || empty($listing))
        {
            echo cmsFramework::noAccess();
            $this->autoRender = false;
            return;
        }

        // Make sure variables are set
        $listing['Listing']['summary'] = Sanitize::getString($listing['Listing'],'summary');
        $listing['Listing']['description'] = Sanitize::getString($listing['Listing'],'description');
        $listing['Listing']['metakey'] = Sanitize::getString($listing['Listing'],'metakey');
        $listing['Listing']['metadesc'] = Sanitize::getString($listing['Listing'],'metadesc');

        $listing['Listing']['text'] = $listing['Listing']['summary'] . $listing['Listing']['description'];

        $regex = '/{.*}/';

        $listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );

        # Get editor review data
        if ($extension == 'com_content' && $this->Config->author_review)
        {
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'
            );

            $conditions = array(
                'Review.pid = '. $listing['Listing']['listing_id'],
                'Review.author = 1',
                'Review.published = 1'
            );

            $editor_review = $this->Review->findRow(array(
                'fields'=>$fields,
                'conditions'=>$conditions
            ));
        }

        # Get user review data or editor reviews data in multiple editor review mode
        $reviewType = (int) ( $this->Config->author_review && $extension == 'com_content' && Sanitize::getString($this->params,'reviewType','user') == 'editor' );

        if ( $extension != 'com_content' || $this->Config->user_reviews || $reviewType )
        {
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'
            );

            $conditions = array(
                'Review.pid= '. $listing['Listing']['listing_id'],
                'Review.author = '.$reviewType,
                'Review.published = 1',
                'Review.mode = "'.$extension.'"'
            );

            $order[] = $this->Review->processSorting($sort);

            $queryData = array
            (
                'fields'=>$fields,
                'conditions'=>$conditions,
                'offset'=>$this->offset,
                'limit'=>$this->limit,
                'order'=>$order
            );

            $reviews = $this->Review->findAll($queryData);

            //Remove unnecessary query parameters for findCount
            $this->Review->joins = array(); // Only need to query comments table
            $review_count = $this->Review->findCount($queryData);

            $listing['Review']['review_count'] = $review_count;
        }

        // Two lines below allow showing the ratings summary in jReviews listings page
        // It requires removing the if statement in the detail.thtml which prevents the summary from showing
        $ratings_summary = array(
            'Rating' => array(
                'average_rating' => $listing['Review']['user_rating']
                #,'ratings' => explode(',', @$listing['Review']['user_criteria_rating'])
            ),
            'Criteria' => $listing['Criteria']
        );

        # Get custom fields for review form if form is shown on page
        $review_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'review');

        $security_code = '';

        if($this->Access->showCaptcha()) {

            $captcha = $this->Captcha->displayCode();

            $security_code = $captcha['image'];
        }

        # Initialize review array and set Criteria and extension keys
        $review = $this->Review->init();
        $review['Criteria'] = $listing['Criteria'];
        $review['Review']['extension'] = $extension;

        $this->_user->duplicate_review = false;

        $review_type = Sanitize::getString($this->params,'reviewtype','user');

        $page = array(
            'title_seo'=>$listing['Listing']['title'],
            'keywords'=>$listing['Listing']['metakey'],
            'description'=>$listing['Listing']['metadesc']
        );

        if($review_type == 'user') {

            $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LISTING_USER_REVIEWS_TITLE_SEO'),$page['title_seo']);
        }
        elseif($review_type == 'editor') {

            $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LISTING_EDITOR_REVIEWS_TITLE_SEO'),$page['title_seo']);
        }

        /**
         * Generate canonical tag for urls with order param
         */
        $RoutesHelper = ClassRegistry::getClass('RoutesHelper');

        $RoutesHelper->name = $this->name;

        $RoutesHelper->action = $this->action;

        $RoutesHelper->params = $this->params;

        if(Sanitize::getBool($this->Config,'viewallreviews_canonical')) {

            $page['canonical'] = $RoutesHelper->content('',$listing,array('return_url'=>true));
        }
        elseif($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl(array('order','listview','tmpl_suffix'));
        }

        $this->set(array(
                'extension'=>$extension,
                'User'=>$this->_user,
                'listing'=>$listing,
                'editor_review'=>$editor_review,
                'reviews'=>$reviews,
                'ratings_summary'=>$ratings_summary,
                'review_fields'=>$review_fields,
                'review'=>$review,
                'review_count'=>$review_count,
                'reviewType' => empty($reviewType) ? 0 : 1, # used to set title for 'View all reviews' page
                'captcha'=>$security_code,
                'page'=>$page,
                'pagination'=>array(
                    'total'=>$review_count
                )

            )
        );

    }

    function create()
    {
        $this->autoRender = true;
        $dir_id = Sanitize::getInt($this->params,'dir');
        $cat_id = Sanitize::getString($this->params,'cat');
        $content_id = null;
        $option = 'com_content';
        $categories = array();

		if($cat_id > 0)
        {
            $category = $this->Category->findRow(
                array(
                    'conditions'=>array('Category.id = ' . $cat_id)
                )
            );
            # Override global configuration
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);
        }

        if (!$this->Access->canAddListing()) {

            if($this->_user->id > 0) {
                cmsFramework::noAccess();
                $this->autoRender = false;
                return;
            }

            $this->autoRender = false;

            $this->layout = 'page';

            $this->autoLayout = true;

            $this->set('access_submit', false);

            return $this->render('elements','login');
        }

        if($cat_id)
        {
            // Find parent categories of pre-selected cat to show the correct category select lists in the form
			$parent_categories = $this->Category->findParents($cat_id);

			foreach($parent_categories AS $key=>$row)
            {
                $categories[$key] = $this->Category->getCategoryList(array(
                    'disabled'=>false,
                    'indent'=>false,
                    'level'=>$row['Category']['level'],
                    'parent_id'=>$row['Category']['parent_id'],
                    'dir_id'=>$dir_id,
					'listing_type'=>true
                ));
            }
        }
        else
        {
            $categories = $this->Category->getCategoryList(array(
                'level'=>1,
                'disabled'=>false,
                'dir_id'=>$dir_id,
				'listing_type'=>true
            ));
        }

		if(!empty($categories))
        {
			// Remove categories without submit access
			foreach($categories AS $key => $subcategories)
			{
				if(is_array($subcategories))
				{
					foreach($subcategories AS $subkey=>$row)
					{
						$overrides = json_decode($row->config,true);

						if(!$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))) {

							// if($cat_id && $cat_id == $subkey) {

							// 	cmsFramework::noAccess();
							// 	$this->autoRender = false;
							// 	return;
							// }

							unset($categories[$key][$subkey]);
						}
					}
				}
				else {

					$overrides = json_decode($subcategories->config,true);

					if(!$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))) {

						unset($categories[$key]);
					}
				}
			}
		}

        $this->set(
            array(
                'menu_id'=>$this->Menu->get($this->app.'_public'), // Public JReviews menu to be used in submit form action
                'submit_step'=>array(1),
                'access_submit'=>true,
                'User'=>$this->_user,
                'categories'=>$categories,
                'listing'=>array('Listing'=>array(
                        'listing_id'=>null,
                        'cat_id'=>$cat_id ? $cat_id : null,
                        'title'=>'',
                        'summary'=>'',
                        'description'=>'',
                        'metakey'=>'',
                        'metadesc'=>''
                    ))
            )
        );

    }

    function edit()
    {
        $this->autoRender = false;

        $listing_id = Sanitize::getInt($this->params,'id');
        $categories = array();

        Configure::write('ListingEdit',true); // Read in Fields model for PaidListings integration

        $listing = $this->Listing->findRow(
            array(
                'fields'=>array('Listing.metakey AS `Listing.metakey`','Listing.metadesc AS `Listing.metadesc`'),
                'conditions'=>'Listing.id = ' . $listing_id
            )
        );

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        // Clear listing expiration if value is not set
        if($listing['Listing']['publish_down'] == NULL_DATE) {

            $listing['Listing']['publish_down'] = '';
        }

        # Set the theme suffix
        $this->Theming->setSuffix(array('cat_id'=>$listing['Category']['cat_id']));

        if (!$this->Access->canEditListing($listing['Listing']['user_id'])) {
            cmsFramework::noAccess();
            $this->autoRender = false;
            return;
        }

        # Get listing custom fields
        $listing_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'listing', $listing);

        // Show category lists if user is editor or above.
        if ($this->Access->isEditor() && Sanitize::getInt($listing['Criteria'],'criteria_id'))
        {
            $categories = $this->Category->getCategoryList(array(
				'disabled'=>true,
				'type_id'=>array(0,$listing['Criteria']['criteria_id']),
				'listing_type'=>true
                ,'dir_id'=>$listing['Directory']['dir_id'] // Shows only categories from the same directory
			));

        	if(!empty($categories))
        	{
				// Remove categories without submit access
				foreach($categories AS $key => $row)
				{
					$overrides = json_decode($row->config,true);
					if(!$this->Access->canAddListing($overrides['addnewaccess'])) {
						unset($categories[$key]);
					}
				}
			}
        }

        // Needed to preserve line breaks when not using wysiwyg editor
        if(!$this->Access->loadWysiwygEditor()) {
            $listing['Listing']['summary'] = $listing['Listing']['summary'];
            $listing['Listing']['description '] = $listing['Listing']['description'];
        }

        $this->set(
            array(
                'submit_step'=>array(1,2),
                'User'=>$this->_user,
                'listing'=>$listing,
                'categories'=>$categories,
                'listing_fields'=>$listing_fields,
                'formTokenKeys'=>$this->formTokenKeys
            )
        );

        return $this->render('listings','create');
    }

    function _getList()
    {
        $limit = Sanitize::getInt($this->params,'limit',15);

        $search = $this->Listing->makeSafe(mb_strtolower(Sanitize::getString($this->params,'search'),'utf-8'));

        $id = Sanitize::getInt($this->params,'id');

        if (!$id && !$search) return '[]';

        if($id) {

            $conditions = array('Listing.id = ' . $id);
        }
        else {

            $conditions = array('Listing.title LIKE ' . $this->QuoteLike($search));
        }

        $this->Listing->addStopAfterFindModel(array('Field','Media','Favorite'));

        unset(
            $this->Listing->joins['Field'],
            $this->Listing->joins['Claim'],
            $this->Listing->joins['ParentCategory']
        );

        $conditions[] = 'Listing.state = 1';

        $conditions[] = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )';

        $conditions[] = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )';

        $conditions[] = 'Category.access = 1';

        $conditions[] = 'Listing.access = 1';

        $this->Listing->fields = array(
            'Listing.id AS `Listing.listing_id`',
            'Listing.title AS `Listing.title`',
            'Listing.catid AS `Listing.cat_id`',
            'Listing.alias AS `Listing.slug`',
            'Listing.state AS `Listing.state`',
            'Listing.publish_up AS `Listing.publish_up`',
            'Listing.publish_down AS `Listing.publish_down`',
            '\'com_content\' AS `Listing.extension`',
            'Category.alias AS `Category.slug`',
            'Totals.user_rating_count AS `Listing.user_review_count`',
            'Totals.editor_rating_count AS `Listing.editor_review_count`'
        );

        $listings = $this->Listing->findAll(array(
                'conditions'=>$conditions,
                'limit'=>$limit
            ),array('afterFind'));

        $results = array();

        foreach($listings AS $key=>$listing) {

            extract($listing['Listing']);

            $sefurl = cmsFramework::makeAbsUrl($this->Listing->Routes->content('',$listing,array('return_url'=>true)));

            $results[] = array(
                'id'=>$listing_id,
                'value'=>$title,
                'title'=>$title,
                'alias'=>$slug,'url'=>$sefurl,
                'user_review_count'=>(int)$user_review_count,
                'editor_review_count'=>(int)$editor_review_count
            );
        }

        return cmsFramework::jsonResponse($results);
    }

    function _favoritesAdd()
    {
        $response = array('success'=>false,'str'=>array());

        if(!$this->_user->id) {

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = (int) $this->_user->id;

        // Force plugin loading on Review model
        $this->_initPlugins('Favorite');

        $this->Favorite->data = $this->data;

        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);

        // Insert new and update display
        if ($this->Favorite->add($listing_id,$user_id) > 0)
        {
            $favored++;

            $response['success'] = true;

            $response['count'] = $favored;

            return cmsFramework::jsonResponse($response);
        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _favoritesDelete()
    {
        $response = array('success'=>false,'str'=>array());

        if(!$this->_user->id) {

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = $this->_user->id;

        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);

        if ($favored > 0)
        {
            // Force plugin loading on Review model
            $this->_initPlugins('Favorite');

            $this->Favorite->data = $this->data;

            // Delete favorite
            $deleted = $this->Favorite->remove($listing_id, $user_id);

            if($deleted)
            {
                $favored--;

                $response['success'] = true;

                $response['count'] = $favored;

                return cmsFramework::jsonResponse($response);
            }

        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _feature()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $response = $this->Listing->feature($listing_id);

        if($response['success'])
        {
            return cmsFramework::jsonResponse($response);
        }

        $response['str'][] = $response['access'] ? 'ACCESS_DENIED' : 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _publish()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

         # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $response = $this->Listing->publish($listing_id);

        if($response['success'])
        {
            return cmsFramework::jsonResponse($response);
        }

        $response['success'] = false;

        $response['str'][] = !$response['access'] ? 'ACCESS_DENIED' : 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        # Load all listing info because we need to get the override settings
        $listing = $this->Listing->getListingById($listing_id);

        $user_id = $listing['Listing']['user_id'];

        $overrides = $listing['ListingType']['config'];

        # Check access
        if(!$this->Access->canDeleteListing($user_id, $overrides))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        # Delete listing and all associated records
        if($this->Listing->del($listing_id))
        {
            return cmsFramework::jsonResponse(array('success'=>true));
        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    /*
    * Loads the new item form with the review form and approriate custom fields
    */
    function _loadForm()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $this->plgResponse = $response = array();

        $dateFieldsEntry = $dateFieldsReview = array();

        $isLeaf = false;

        $level = Sanitize::getInt($this->data,'level');

        $cat_id = Sanitize::getInt($this->data,'catid');

        $cat_id_array =  Sanitize::getVar($this->data['Listing'],'catid');

        # No category selected
        if(!$cat_id)
        {
			// Check if there's a new cat id we can use
			$catArray = Sanitize::getVar($this->data['Listing'],'catid',array());

            $catArray = array_slice($catArray, 0, array_search(0, $catArray));

			if(!empty($catArray)) {
				$level = count($catArray);
				$cat_id = array_pop($catArray);
			}
		}

        # Category selected is not leaf. Need to show new category list with children, but clear every list to the right first!
        if(!$this->Category->isLeaf($cat_id))
        {
            $categories = $this->Category->getCategoryList(array('parent_id'=>$cat_id,'indent'=>false,'disabled'=>false,'listing_type'=>true));

            if(!empty($categories))
            {
				// Remove categories without submit access
				foreach($categories AS $key => $row)
				{
					$overrides = json_decode($row->config,true);

					if(!$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))) {
						unset($categories[$key]);
					}
				}

				if(!empty($categories))
				{
					$cat = reset($categories);
					S2App::import('Helper','form','jreviews');
					$Form = ClassRegistry::getClass('FormHelper');
					$attributes = array('id'=>'cat_id'.$cat->level,'class'=>'jr-cat-select jrSelect','size'=>'1');
					$select_list = $Form->select(
						'data[Listing][catid][]',
						array_merge(array(array('value'=>null,'text'=>JreviewsLocale::getPHP('LISTING_SELECT_CAT'))),$categories),
						null,
						$attributes
					);

					if($level >= 1 && count($cat_id_array) > 1) {
						$response['level'] = $level - 1;
					}

                    $response['select'] = $select_list;
				}
				else {

                    $response['action'] = 'no_access';

                    return cmsFramework::jsonResponse($response);
				}

            }

            # Checks if this category is setup with a listing type. Otherwise hides the form.
            if(!$this->Category->isJReviewsCategory($cat_id))
            {
                $response['action'] = 'hide_form';

                return cmsFramework::jsonResponse($response);
            }
        }
        else
        {
            $isLeaf = true;
        }

        # Category selected is leaf or set up with listing type, so show form
        if ($cat_id)
        {
            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));

            $name_choice = $this->Config->name_choice;

            if ($name_choice == 'alias') {
                $name = $this->_user->username;
            } else {
                $name = $this->_user->name;
            }

            # Get criteria info for selected category
            $category  = $this->JreviewsCategory->findRow(array(
                'conditions'=>array('JreviewsCategory.id = ' . $cat_id,'JreviewsCategory.option = "com_content"')
            ));

            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);

            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));

            $criteria = $category['ListingType'];
            $criteria['criteria'] = explode("\n",$category['ListingType']['criteria']);
            $criteria['tooltips'] = explode("\n",$category['ListingType']['tooltips']);
            $criteria['required'] = explode("\n",$category['ListingType']['required']);

            // every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
            if ( count($criteria['criteria']) != count($criteria['required']) )
            {
                $criteria['required'] = array_fill(0, count($criteria['criteria']), 1);
            }
            # Get listing custom fields
            $listing_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'listing', 'getForm');

            # Get review custom fields
            $review_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'review', 'getForm');

            // Captcha security image
            if ($this->Access->showCaptcha()) {
                $captcha = $this->Captcha->displayCode();
            } else {
                $captcha = array('image'=>null);
            }

            $this->set(array(
                    'User'=>$this->_user,
                    'name'=>$name,
                    'captcha'=>$captcha['image'],
                    'listing_fields'=>$listing_fields,
                    'review_fields'=>$review_fields,
                    'criteria'=>$criteria,
                    'listing'=>array('Listing'=>array(
                            'listing_id'=>0,
                            'title'=>'',
                            'summary'=>'',
                            'description'=>'',
                            'metakey'=>'',
                            'metadesc'=>'',
                            'cat_id'=>(int) $this->data['Listing']['catid']
                    ))
            ));

            $response['rating_inc'] = Sanitize::getVar($this->Config,'rating_increment',1);

           // Remove cat select lists to the right of current select list if current selection is a leaf
            if($level && $isLeaf)
            {
                $response['level'] = $level - 1;
            }

            $response['action'] = 'show_form';

            $response['html'] = $this->render('listings','create_form');

            $response = array_merge($response,$this->plgResponse /* from plugins */);

            return cmsFramework::jsonResponse($response);
        }

        # No category selected
        $response['level'] = 0;

        $response['action'] = 'hide_form';

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $this->autoRender = false;

		$this->autoLayout = false;

		$response = array('success'=>false,'str'=>array());

		$validation = '';

		$mediaForm = '';

        $listing_id = Sanitize::getInt($this->data['Listing'],'id',0);

		$isNew = $this->Listing->isNew = $listing_id == 0 ? true : false;

        $user_id = $this->_user->id;

        $this->data['isNew'] = $isNew;

		$this->data['email'] = Sanitize::getString($this->data,'email');

		$this->data['name'] = Sanitize::getString($this->data,'name');

		$this->data['categoryid_hidden'] = Sanitize::getInt($this->data['Listing'],'categoryid_hidden');

		$cat_id = Sanitize::getVar($this->data['Listing'],'catid');

        if(is_array($cat_id)) {

            $cat_id = array_filter($cat_id);

            $this->data['Listing']['catid'] = (int) array_pop($cat_id);
        }
        else {

            $this->data['Listing']['catid'] = (int) $cat_id; /*J16*/
        }

		$this->data['Listing']['title'] = Sanitize::getString($this->data['Listing'],'title','');

        $this->data['Listing']['created_by_alias'] = Sanitize::getString($this->data,'name','');

        if($isNew)
	    {
		    $this->data['Listing']['language'] = '*';

            $this->data['Listing']['access'] = 1;
	    }

        $category_id = $this->data['Listing']['catid'] ? $this->data['Listing']['catid'] : $this->data['categoryid_hidden'];

        # Get criteria info
        $criteria = $this->Criteria->findRow(array(
            'conditions'=>array('Criteria.id =
                (SELECT criteriaid FROM #__jreviews_categories WHERE id = '.(int) $category_id.' AND `option` = "com_content")
            ')
        ));

        if(!$criteria)
        {
            $response['str'][] = 'LISTING_SUBMIT_CAT_INVALID';

            return cmsFramework::jsonResponse($response);
        }

        $this->data['Criteria']['id'] = $criteria['Criteria']['criteria_id'];

        # Override global configuration
        isset($criteria['ListingType']) and $this->Config->override($criteria['ListingType']['config']);

        # Perform access checks
        if($isNew && !$this->Access->canAddListing())
        {
            $response['success'] = false;

            $response['str'][] = 'LISTING_SUBMIT_DISALLOWED';

            return cmsFramework::jsonResponse($response);
        }
        elseif(!$isNew)
        {
             $query = "SELECT created_by FROM #__content WHERE id = " . $listing_id;

             $this->_db->setQuery($query);

             $listing_owner = $this->_db->loadResult();

             if(!$this->Access->canEditListing($listing_owner))
             {
                $response['str'][] = 'ACCESS_DENIED';

                return cmsFramework::jsonResponse($response);
             }
        }

        # Load the notifications observer model component and initialize it.
        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security','notifications');

		$this->__initComponents();

		if($this->invalidToken == true)
        {
            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

        # Override configuration
        $category = $this->Category->findRow(array('conditions'=>array('Category.id = ' . $this->data['Listing']['catid'])));

		$this->Config->override($category['ListingType']['config']);

        if ($this->Access->loadWysiwygEditor())
        {
            $this->data['Listing']['introtext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'introtext'),ENT_QUOTES,cmsFramework::getCharset());

            $this->data['Listing']['fulltext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'fulltext'),ENT_QUOTES,cmsFramework::getCharset());

            // Less restrictive on server side clean up with Joomla Editors and above.
            // This allows iframes and other tags allowed via the editor so they are not removed server side.
            if($this->Access->isEditor()) {

                $this->data['Listing']['introtext'] = Sanitize::stripWhitespace($this->data['Listing']['introtext']);

                $this->data['Listing']['fulltext'] = Sanitize::stripWhitespace($this->data['Listing']['fulltext']);
            }
            else {

                $this->data['Listing']['introtext'] = Sanitize::stripScripts(Sanitize::stripWhitespace(Sanitize::getVar($this->data['Listing'],'introtext')));

                $this->data['Listing']['fulltext'] = Sanitize::stripScripts(Sanitize::stripWhitespace(Sanitize::getVar($this->data['Listing'],'fulltext')));
            }
        }
        else {

            $this->data['Listing']['introtext'] = Sanitize::stripAll($this->data['Listing'],'introtext','');

            if(isset($this->data['Listing']['fulltext']))
            {
                $this->data['Listing']['fulltext'] = Sanitize::stripAll($this->data['Listing'],'fulltext','');
            } else {
                $this->data['Listing']['fulltext'] = '';
            }
        }

        $this->data['Listing']['introtext'] = str_replace( '<br>', '<br />', $this->data['Listing']['introtext'] );

		$this->data['Listing']['fulltext']     = str_replace( '<br>', '<br />', $this->data['Listing']['fulltext'] );

        if($this->Access->canAddMeta())
        {
            $this->data['Listing']['metadesc'] = Sanitize::getString($this->data['Listing'],'metadesc');

            $this->data['Listing']['metakey'] = Sanitize::getString($this->data['Listing'],'metakey');
        }

        // Title alias handling
        $slug = '';

        $alias = Sanitize::getString($this->data['Listing'],'alias');

        if($isNew && $alias == '')
            {
                $slug = S2Router::sefUrlEncode($this->data['Listing']['title']);
                if(trim(str_replace('-','',$slug)) == '') {
                    $slug = date("Y-m-d-H-i-s");
                }
            }
        elseif($alias != '')
            {
                // Alias filled in so we convert it to a valid alias
                $slug = S2Router::sefUrlEncode($alias);
                if(trim(str_replace('-','',$slug)) == '') {
                    $slug = date("Y-m-d-H-i-s");
                }
            }

        $slug != '' and $this->data['Listing']['alias'] = $slug;

        # Check for duplicates
        switch($this->Config->content_title_duplicates)
        {
            case 'category': // Checks for duplicates in the same category

                $query = "
                    SELECT
                        count(*)
                    FROM
                        #__content AS Listing WHERE Listing.title = " . $this->_db->Quote($this->data['Listing']['title']) . "
                        AND Listing.state >= 0
                        AND Listing.catid = " . $this->data['Listing']['catid']
                        . (!$isNew ? " AND Listing.id <> " . $listing_id : '')
                    ;

                $this->_db->setQuery($query);

                $titleExists = $this->_db->loadResult();

            break;

            case 'no': // Checks for duplicates all over the place

                $query = "
                    SELECT
                        count(*)
                    FROM
                        #__content AS Listing
                    WHERE
                        Listing.title = " . $this->_db->Quote($this->data['Listing']['title']) . "
                       AND Listing.state >= 0
                       " . (!$isNew ? " AND Listing.id <> " . $listing_id : '')
                ;

                $this->_db->setQuery($query);

                $titleExists = $this->_db->loadResult();

            break;

            case 'yes': // Duplicates are allowed, no checking necessary

                $titleExists = false;

            break;
        }

        if ($titleExists /*&& $isNew */ && $this->data['Listing']['title'] != '')
        {// if listing exists
            $response['str'][] = 'LISTING_SUBMIT_DUPLICATE';

			return cmsFramework::jsonResponse($response);
        }

        // Review form display check logic used several times below
        $revFormSetting = $this->Config->content_show_reviewform;

        if($revFormSetting == 'noteditors' && !$this->Config->author_review) {

            $revFormSetting = 'all';
        }

        $revFormEnabled = !isset($this->data['review_optional'])
            && $this->Access->canAddReview()
            && $isNew
            && (   ($revFormSetting == 'all' && ($this->Config->author_review || $this->Config->user_reviews))
                || ($revFormSetting == 'authors'  && $this->Access->isJreviewsEditor($user_id))
                || ($revFormSetting == 'noteditors' && !$this->Access->isJreviewsEditor($user_id))
            );

        // Validation of content default input fields
        !$this->data['Listing']['catid'] and $this->Listing->validateSetError("sec_cat", 'LISTING_VALIDATE_SELECT_CAT');

        // Validate only if it's a new listing
        if ($isNew)
        {
            if (!$this->_user->id) {

                $username = Sanitize::getString($this->data,'username') != '' || Sanitize::getInt($this->Config,'content_username');

                $register_guests = Sanitize::getBool($this->viewVars,'register_guests');

                $this->Listing->validateInput(Sanitize::getString($this->data,'name'), "name", "text", 'VALIDATE_NAME', ($register_guests && $username) || $this->Config->content_name == "required" ? 1 : 0);

                $this->Listing->validateInput(Sanitize::getString($this->data,'username'), "username", "username", 'VALIDATE_USERNAME', $register_guests && $username);

                $this->Listing->validateInput(Sanitize::getString($this->data,'email'), "email", "email", 'VALIDATE_EMAIL', ($register_guests && $username)  || $this->Config->content_email == "required" ? 1 : 0);

                $this->data['name'] = Sanitize::getString($this->data,'name','');

                $this->data['email'] = Sanitize::getString($this->data,'email','');

            } else {

                $this->data['name'] = $this->_user->name;

                $this->data['email'] = $this->_user->email;
            }
        }

        $this->Listing->validateInput($this->data['Listing']['title'], "title", "text", 'LISTING_VALIDATE_TITLE', 1);

        # Validate listing custom fields
        $listing_valid_fields = $this->Field->validate($this->data,'listing',$this->Access);

		$this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);

		$this->Listing->validateInput($this->data['Listing']['introtext'], "introtext", "text", 'LISTING_VALIDATE_SUMMARY', $this->Config->content_summary == "required" ? 1 : 0);

		$this->Listing->validateInput($this->data['Listing']['fulltext'], "fulltext", "text", 'LISTING_VALIDATE_DESCRIPTION', $this->Config->content_description == "required" ? 1 : 0);

        # Validate review custom fields
        if ($revFormEnabled && $criteria['Criteria']['state'])
        {
            // Review inputs
            $this->data['Review']['userid'] = $user_id;

            $this->data['Review']['email'] = $this->data['email'];

			$this->data['Review']['name'] = $this->data['name'];

			$this->data['Review']['username'] = Sanitize::getString($this->data,'name','');

            $this->data['Review']['title'] = Sanitize::getString($this->data['Review'],'title');

            $this->data['Review']['location'] = Sanitize::getString($this->data['Review'],'location'); // deprecated

            $this->data['Review']['comments'] = Sanitize::getString($this->data['Review'],'comments');

            // Review standard fields
            $this->Listing->validateInput($this->data['Review']['title'], "rev_title", "text", 'REVIEW_VALIDATE_TITLE', ($this->Config->reviewform_title == 'required' ? true : false));

            if( $criteria['Criteria']['state'] == 1 ) //ratings enabled
            {
                $criteria_qty = $criteria['Criteria']['quantity'];

                $ratingErr = 0;

                if(!isset($this->data['Rating']))
                {
                    $ratingErr = $criteria_qty;
                }
                else
                {
                    for ( $i = 0;  $i < $criteria_qty; $i++ )
                    {
                        if (!isset($this->data['Rating']['ratings'][$i])
                            ||
                            (empty($this->data['Rating']['ratings'][$i])
                                || $this->data['Rating']['ratings'][$i] == 'undefined'
                                || (float)$this->data['Rating']['ratings'][$i] > $this->Config->rating_scale)
                        ) {
                            $ratingErr++;
                        }
                    }
                }
                $this->Listing->validateInput('', "rating", "text", array('REVIEW_VALIDATE_CRITERIA',$ratingErr), $ratingErr);
            }

            // Review custom fields
            $this->Field->validateErrors = array(); // Clear any previous validation errors

            $review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);

			$this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);

			$this->Listing->validateInput($this->data['Review']['comments'], "comments", "text", 'REVIEW_VALIDATE_COMMENT',  ($this->Config->reviewform_comment == 'required' ? true : false));

        } // if ($revFormEnabled && $criteria['Criteria']['state'])

        # Validate Captcha security code
        if ($isNew && $this->Access->showCaptcha())
        {
            $captcha = Sanitize::getString($this->data['Captcha'],'code');

            if($captcha == '') {

                $this->Listing->validateSetError("code", 'VALID_CAPTCHA');
            }
            elseif (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) {

                $this->Listing->validateSetError("code", 'VALID_CAPTCHA_INVALID');
            }
         }

        # Get all validation messages
        $validation = $this->Listing->validateGetErrorArray();

        # Validation failed
        if(!empty($validation))
        {
            $response['str'] = $validation;

            // Transform textareas into wysiwyg editors
            if($this->Access->loadWysiwygEditor())
            {
                $response['editor'] = true;
            }

            // Replace captcha with new instance
            if ($this->Access->in_groups($this->Config->security_image))
            {
                $captcha = $this->Captcha->displayCode();

                $response['captcha'] = $captcha['src'];
            }

            return cmsFramework::jsonResponse($response);
        }

        # Validation passed, continue...
        if ($isNew)
        {
            $this->data['Listing']['created_by'] = $user_id;

            $this->data['Listing']['created'] = _CURRENT_SERVER_TIME;

            // PUBLISH UP

            $publish_up = _CURRENT_SERVER_TIME;

            $publish_up_value = Sanitize::getString($this->data['Listing'],'publish_up');

            if(Sanitize::getInt($this->Config,'listing_publication_date') && $publish_up_value != '') {

                $publish_up = $publish_up_value;
            }

            $this->data['Listing']['publish_up'] = $publish_up;

            // PUBLISH DOWN

            $publish_down = NULL_DATE;

            $publish_down_value = Sanitize::getString($this->data['Listing'],'publish_down');

            if(Sanitize::getInt($this->Config,'listing_expiration_date') && $publish_down_value != '') {

                $publish_down = $publish_down_value;
            }

            $this->data['Listing']['publish_down'] = $publish_down;

            // EMAIL AND IP ADDRESS

            $this->data['Field']['Listing']['email'] = $this->data['email'];

            $this->data['Field']['Listing']['ipaddress'] = ip2long($this->ipaddress == '::1' ? '127.0.0.1' : $this->ipaddress);

            // If visitor, assign name field to content Alias
            if (!$user_id) {

                $this->data['Listing']['created_by_alias'] = $this->data['name'];
            }

            // Check moderation settings
            $this->data['Listing']['state'] = (int) !$this->Access->moderateListing();

            // If listing moderation is enabled, then the review is also moderated
            if(!$this->data['Listing']['state']){

                $this->Config->moderation_reviews = $this->Config->moderation_editor_reviews = $this->Config->moderation_item;
            }

        }
        else {

            if($this->Config->moderation_item_edit) // If edit moderation enabled, then check listing moderation, otherwise leave state as is
            {
                $this->data['Listing']['state'] = (int) !$this->Access->moderateListing();
            }

            $this->data['Listing']['modified'] = _CURRENT_SERVER_TIME; //gmdate('Y-m-d H:i:s');

            $this->data['Listing']['modified_by'] = $this->_user->id;
        }

        # Save listing
        $savedListing = $this->Listing->store($this->data);

        $listing_id = $this->data['Listing']['id'];

        if (!$savedListing)
        {
            $response['str'][] = 'DB_ERROR';
        }

        # Set as approved Claim if claims are disabled
        if($isNew && !$this->Config->claims_enable && $user_id > 0) {

            $claimData = array('Claim'=>array(
                    'listing_id'=>$listing_id,
                    'user_id'=>$user_id,
                    'created'=>_CURRENT_SERVER_TIME,
                    'approved'=>1
                ));

            $this->Claim->store($claimData);
        }

        // Error on listing save
        if(!empty($response['str']))
        {
            return cmsFramework::jsonResponse($response);
        }

        # Save listing custom fields
        $this->data['Field']['Listing']['contentid'] = $this->data['Listing']['id'];

		$this->Field->save($this->data, 'listing', $isNew, $listing_valid_fields);

        # Begin insert review in table
        if ($revFormEnabled && $criteria['Criteria']['state'])
        {
            // Get reviewer type, for now editor reviews don't work in Everywhere components
            $this->data['Review']['author'] = (int) $this->Access->isJreviewsEditor($user_id);

            $this->data['Review']['mode'] = 'com_content';

            $this->data['Review']['pid'] = (int) $this->data['Listing']['id'];

            // Force plugin loading on Review model
            $this->_initPlugins('Review');

            $this->Review->isNew = true;

            $savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);
        }

         # Before render callback - PaidListings
        $facebook = false;

        if($isNew && isset($this->Listing->plgBeforeRenderListingSaveTrigger))
        {
            $plgBeforeRenderListingSave = $this->Listing->plgBeforeRenderListingSave();

            switch($plgBeforeRenderListingSave)
            {
                case '0': $this->data['Listing']['state'] = 1; break;

                case '1': $this->data['Listing']['state'] = 0; break;

                case '': break;

                default:

                    $facebook = true; // For paid plans we  publish to wall

                    $plgBeforeRenderListingSave['success'] = true;

                    $plgBeforeRenderListingSave['moderation'] = !isset($this->data['Listing']['state']) || $this->data['Listing']['state'];

                    return cmsFramework::jsonResponse($plgBeforeRenderListingSave);

                break;
            }
        }

        $listing = $this->Listing->findRow(array(
                'fields'=>array(
                        'Criteria.criteria AS `Criteria.criteria`',
                        'Criteria.tooltips AS `Criteria.tooltips`',
                    ),
                'conditions'=>array('Listing.id = ' . $listing_id)
            ),array('afterFind' /* Only need menu id */));

		$this->set(array(
			'upload_object'=>'listing',
			'listing_id'=>$listing_id,
            'listing'=>$listing,
			'review_id'=>0,
			'extension'=>'com_content',
			'formTokenKeys'=>array('listing_id','review_id','extension')
		));

		// Checks for media upload form as 2nd step
		if($isNew && ($allowed_media_types = $this->Access->canAddAnyListingMedia($user_id, array(), $listing_id))) {

			$tos_article = null;

			# Terms & Conditions for media uploads
			if(Sanitize::getBool($this->Config,'media_general_tos') && $tos_id = Sanitize::getInt($this->Config,'media_general_tos_articleid'))
			{
				$tos_article = $this->Article->findRow(array('conditions'=>array('Article.id = ' . $tos_id)));
			}

			$this->set(array(
				'tos_article'=>$tos_article,
				'allowed_types'=>$allowed_media_types,
				'upload_object'=>'listing',
				'session_id'=>$user_id,
                'User'=>$this->_user
			));

			$mediaForm = $this->render('media','create');
		}

        $response['success'] = true;

        $response['is_new'] = $isNew;

        # Moderation disabled
        if ($facebook || !isset($this->data['Listing']['state']) || $this->data['Listing']['state'])
        {
            # Facebook wall integration
            $fb_checkbox = Sanitize::getBool($this->data,'fb_publish');

            $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable')
                && Sanitize::getBool($this->Config,'facebook_listings')
                && $fb_checkbox;

            $url = cmsFramework::route($listing['Listing']['url']);

            $response['moderation'] = false;

            isset($mediaForm) and $response['mediaForm'] = $mediaForm;

            $response['listing_id'] = $listing_id;

            $response['url'] = $url;

            if($facebook_integration)  {

                $token = cmsFramework::getCustomToken($listing_id);

                $response['facebook'] = true;

                $response['token'] = $token;
            }

			return cmsFramework::jsonResponse($response);
        }

		if($isNew && $mediaForm != '') {

          $response['mediaForm'] = $mediaForm;

        }

        $response['moderation'] = true;

        return cmsFramework::jsonResponse($response);
    }
}
