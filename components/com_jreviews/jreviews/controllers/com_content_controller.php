<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ComContentController extends MyController {

    var $uses = array('user','menu','criteria','directory','field','media','favorite','review','category','vote');

    var $helpers = array('assets','routes','libraries','html','form','text','time','jreviews','media','custom_fields','rating','community','widgets');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false; //Output is returned

    var $autoLayout = true;

    var $listingResults;

    var $formTokenKeys = array('id'=>'review_id','pid'=>'listing_id','mode'=>'extension','criteria_id'=>'criteria_id');

    function beforeFilter()
    {
         # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        # Make configuration available in models
        $this->Listing->Config = &$this->Config;
    }

    function afterFilter()
    {
        if(isset($this->review_fields))
        {
            $Assets = ClassRegistry::getClass('AssetsHelper');
            $Assets->assetParams['review_fields'] = $this->review_fields;
            $Assets->assetParams['owner_id'] = $this->owner_id;
            unset($this->review_fields);
        }
        parent::afterFilter();
    }

    // Need to return object by reference for PHP4
    function &getPluginModel()
    {
        return $this->Listing;
    }

    // Need to return object by reference for PHP4
    function &getObserverModel()
    {
        return $this->Listing;
    }

    function com_content_view($passedArgs)
    {
        $this->layout = 'detail';

        $content_row = Sanitize::getVar($passedArgs,'row');

        $content_params = Sanitize::getVar($passedArgs,'params');

        $preview = Sanitize::getBool($passedArgs,'preview');

        $editor_review = array();

        $editor_ratings_summary = array();

        $editor_review_count = null;

        $reviews  = array();

        $ratings_summary = array();

        $review_count = null;

        $crumbs = array();

        $listing_id = Sanitize::getInt($content_row,'id',Sanitize::getInt($this->params,'id'));

        // Escape quotes in meta tags
        if($content_row) {

            $content_row->metadesc = htmlspecialchars($content_row->metadesc,ENT_QUOTES,'UTF-8');

            $content_row->metakey = htmlspecialchars($content_row->metakey,ENT_QUOTES,'UTF-8');
        }

        # Check if item category is configured for jreviews
        if(!$preview && !$this->Category->isJreviewsCategory($content_row->catid))
        {
            return array('row'=>$content_row,'params'=>$content_params);
        }

        # Override content page parameter settings
        if($content_params) {

			$content_row->params->set('access-edit',0);
			$content_row->params->set('show_title',0);
            $content_row->params->set('show_category',0);
            $content_row->params->set('show_author',0);
            is_object($content_params) and $content_params->set('show_page_heading',0); /* For some reason setting it on $content_row does not work*/
            $content_row->params->set('show_page_heading',0); /* Place holder in case it ever gets fixed */
            $content_row->params->set('show_create_date',0);
            $content_row->params->set('show_publish_date',0);
            $content_row->params->set('show_modify_date',0);
            $content_row->params->set('show_page_title',0); // J1.5.4+
            $content_row->params->set('show_hits',0);

			$content_row->params->set('show_parent_category',0);
        }

        # Get listing and review summary data
        $fields = array(
            'Criteria.criteria AS `Criteria.criteria`',
            'Criteria.tooltips AS `Criteria.tooltips`',
        );

		$this->Listing->controller = $this->name;

		$this->Listing->action = $this->action;

        // Need to query the listing even if view cache enabled because otherwise there's no way to set breadcrumbs and meta data in the content plugin
        $listing = $this->Listing->findRow(array('cache'=>!((bool) $this->_user->id),'fields'=>$fields,'conditions'=>array('Listing.id = '. $listing_id)));

        if(!$listing) {

            // The listing doesn't exit;
            echo cmsFramework::noAccess();

            $this->autoRender = false;

            return;
        }
        // Access check for preview mode - display only for unpublished listings when the registered user is the owner and for editors and above
        if($preview && $listing['Listing']['state'] > 0
            &&
            (NULL_DATE == $listing['Listing']['publish_down'] || strtotime($listing['Listing']['publish_down']) > time())
            &&
            (NULL_DATE == $listing['Listing']['publish_up'] || strtotime($listing['Listing']['publish_up']) < time())
        ) {

            // Listing is published so we redirect to the published url

            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

            cmsFramework::redirect($url);

            exit;
        }
        elseif ($preview &&
                ($listing['User']['user_id'] == 0 || $this->_user->id != $listing['User']['user_id']) &&
                !$this->Access->isEditor()
        ) {

            // Preview mode, but listing owner is guest, or registered user is not the listing owner and it's also not a Joomla editor or above
            echo cmsFramework::noAccess();

            $this->autoRender = false;

            return;
        }

        $cat_id = Sanitize::getInt($content_row,'catid',$listing['Category']['cat_id']);

        $text = Sanitize::getString($content_row,'text',$listing['Listing']['summary'].$listing['Listing']['description']);

        if($preview) {

            $text = JHTML::_('content.prepare', $text);
        }

        # Set the theme suffix  - $parent_categories also used for J16 breadcrumb
        $parent_categories = $this->Category->findParents($cat_id);

        $this->Theming->setSuffix(array('categories'=>$parent_categories));

		# These should be moved to the s2framework model to unset automatically after every query so that there's no confusion when the model is used
		# in modules, plugins, etc.
        unset($this->Listing->controller);

        unset($this->Listing->action);

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        // Override CMS breadcrumbs
        S2App::import('Helper','routes');

        $Routes = ClassRegistry::getClass('RoutesHelper');

        $Routes->Config = $this->Config;

        $this->Config->breadcrumb_detail_directory and $crumbs[] = array('name'=>$listing['Directory']['title'],'link'=>$Routes->directory($listing,array('return_url'=>true)));

        # Generate crumbs
        while($cat = array_shift($parent_categories))
        {
            $crumbs[] = array('name'=>$cat['Category']['title'],'link'=>$Routes->category($cat,array('return_url'=>true)));
        }

        $crumbs[] = array('name'=>$listing['Listing']['title'],'link'=>'');

        $this->set(array('listing'=>$listing,'crumbs'=>$crumbs));

        if(!$this->Config->breadcrumb_detail_override) $crumbs = array();

        # Get cached vesion
        if(!$preview && $this->_user->id === 0)
        {
            $page = $this->cached($this->here . '.detail');

            if($page) {

                $content_row->text = $page;

                return array('row'=>$content_row,'params'=>$content_params,'listing'=>$listing,'crumbs'=>$crumbs);
            }
        }

        $this->owner_id = $listing['Listing']['user_id']; // Used in AssetsHelper

        // Check if the listing has any html tags, and if it does, then strip the double /r/r added by J1.5, otherwise it is
        // required for proper spacing of summary and description fields
        if(preg_match('/(<\w+)(\s*[^>]*)(>)/',$text)) {

            $listing['Listing']['text'] = str_replace("\r",'',$text); // Eliminates double break between summary and description
        }
        else {

            $listing['Listing']['text'] = $text;
        }

        $regex = '/{mosimage\s*.*?}/i';

        $listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );

        # Get editor review data
        if ($this->Config->author_review)
        {
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'
            );

//            $joins = $this->Listing->joinsReviews;

            $conditions = array(
                'Review.pid = '. $listing['Listing']['listing_id'],
                'Review.author = 1',
                'Review.published = 1'
            );

            $queryData = array(
                'fields'=>$fields,
                'conditions'=>$conditions,
//                'joins'=>$joins,
                'offset'=>0,
                'limit'=>$this->Config->editor_limit,
                'order'=>array($this->Review->processSorting())
            );

            $editor_review = $this->Review->findAll($queryData);

            $editor_review_count = $this->Review->findCount($queryData);

            if ( $editor_review_count <= 1 && $this->Config->author_review == 1 )
            {
                // used for the separate display routine when we are in single-editor-review mode, and also for backwards compat with older templates
                $editor_review = array_shift($editor_review);
            }

            $editor_ratings_summary = array(
                'Rating' => array(
                    'average_rating' => $listing['Review']['editor_rating'],
                    'ratings' => explode(',', $listing['Review']['editor_criteria_rating']),
                    'criteria_rating_count' => explode(',', $listing['Review']['editor_criteria_rating_count'])
                ),
                'Criteria' => $listing['Criteria'],
                'summary' => 1
            );
        }

        # Ger user review data
        if ($this->Config->user_reviews)
        {
            $fields = array(
                'Review.owner_reply_approved As `Review.owner_reply_approved`',
                'Review.owner_reply_text As `Review.owner_reply_text`',
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'
            );

//            $joins = $this->Listing->joinsReviews;

            $conditions = array(
                'Review.pid= '. $listing['Listing']['listing_id'],
                'Review.author = 0',
                'Review.published = 1',
                'Review.mode = \'com_content\'',
                'JreviewsCategory.`option` = \'com_content\''
            );

            $queryData = array
            (
                'fields'=>$fields,
                'conditions'=>$conditions,
//                'joins'=>$joins,
                'offset'=>0,
                'limit'=>$this->Config->user_limit,
                'order'=>array($this->Review->processSorting($this->Config->user_review_order))
            );

            $reviews = $this->Review->findAll($queryData);

            $review_count = $this->Review->findCount($queryData);

            $ratings_summary = array(
                'Rating' => array(
                    'average_rating' => $listing['Review']['user_rating'],
                    'ratings' => explode(',', $listing['Review']['user_criteria_rating']),
                    'criteria_rating_count' => explode(',', $listing['Review']['user_criteria_rating_count'])
                ),
                'Criteria' => $listing['Criteria'],
                'summary' => 1
            );
        }

        # Get custom fields for review form if form is shown on page
        $review_fields = $this->review_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'review');

        # Initialize review array and set Criteria and extension keys
        $review = $this->Review->init();
        $review['Criteria'] = $listing['Criteria'];
        $review['Review']['extension'] = $listing['Listing']['extension'];

        # check for duplicate reviews
        $is_jr_editor = $this->Access->isJreviewsEditor($this->_user->id);

        $this->_user->duplicate_review = $listing['duplicate_review'] = false;

        // It's a guest so we only care about checking the IP address if this feature is not disabled and
        // server is not localhost
        if(!$this->_user->id)
        {
            if(!$this->Config->review_ipcheck_disable && $this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1')
            {
                // Do the ip address check everywhere except in localhost
               $this->_user->duplicate_review = (bool) $this->Review->findCount(array(
				   'conditions'=>array(
						'Review.pid = '.$listing_id,
						"Review.ipaddress = '{$this->ipaddress}'",
						"Review.mode = 'com_content'",
						"Review.author = 0",
						"Review.published >= 0"
					),
				    'session_cache'=>false
			   ));

               $listing['duplicate_review'] = $this->_user->duplicate_review;

            }
        }

        elseif(
            (!$is_jr_editor && !$this->Config->user_multiple_reviews)  // registered user and one review per user allowed when multiple reviews is disabled
            ||
            ($is_jr_editor && $this->Config->author_review == 2) // editor and one review per editor allowed when multiple editor reviews is enabled
        )
        {
            $this->_user->duplicate_review = (bool) $this->Review->findCount(array(
				'conditions'=>array(
					'Review.pid = '.$listing_id,
					"(Review.userid = {$this->_user->id}" .
						(
							$this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1' && !$this->Config->review_ipcheck_disable && !$is_jr_editor //&& (!$is_jr_editor || !$this->Config->review_ipcheck_disable)
						?
							" OR Review.ipaddress = '{$this->ipaddress}') "
						:
							')'
						),
					"Review.mode = 'com_content'",
					"Review.published >= 0",
					($this->Config->author_review == 0 ? "Review.author = 0" : "Review.author >= 0")
				),
				'session_cache'=>false
			));

            $listing['duplicate_review'] = $this->_user->duplicate_review;
        }

          $this->set(array(
                'extension'=>'com_content',
                'User'=>$this->_user,
                'listing'=>$listing,
                'editor_review'=>$editor_review,
                'reviews'=>$reviews,
                'ratings_summary'=>$ratings_summary,
                'editor_ratings_summary'=>$editor_ratings_summary,
                'review_count'=>$review_count,
                'editor_review_count'=>$editor_review_count,
                'review_fields'=>$review_fields,
                'review'=>$review,
                'formTokenKeys'=>$this->formTokenKeys
            )
        );

        $page = $this->render('listings','detail');

        # Save cached version
        if(!$preview && $this->_user->id ===0) {

            $this->cacheView('listings','detail',$this->here . '.detail', $page);
        }

        if(!$preview) {

            $content_row->text = $page;

            return array('row'=>$content_row,'params'=>$content_params,'listing'=>$listing,'crumbs'=>$crumbs);
        }
        else {

            return $page;
        }
    }

    function com_content_blog($passedArgs)
    {
        $this->autoLayout = true;
        $this->layout = 'cmsblog';

        $content_row = $passedArgs['row'];
        $content_params = $passedArgs['params'];

//        return array('row'=>$content_row,'params'=>$content_params);

        // Check if item category is configured for jreviews
        if(!$this->Category->isJreviewsCategory($content_row->catid)) {
            return array('row'=>$content_row,'params'=>$content_params);
        }

        # Set the theme suffix  - $parent_categories also used for J16 breadcrumb
        $parent_categories = $this->Category->findParents($content_row->catid);

        $this->Theming->setSuffix(array('categories'=>$parent_categories));

        # Override content page parameter settings
//            $content_row->params->set('show_title',0);
//            $content_row->params->set('show_category',0);
        $content_row->params->set('show_author',0);
        is_object($content_params) and $content_params->set('show_page_heading',0); /* For some reason setting it on $content_row does not work*/
        $content_row->params->set('show_page_heading',0); /* Place holder in case it ever gets fixed */
        $content_row->params->set('show_create_date',0);
        $content_row->params->set('show_modify_date',0);
        $content_row->params->set('show_publish_date',0);
        $content_row->params->set('show_vote',0);
        $content_row->params->set('show_hits',0);

        # Get listing and review summary data
        $fields = array(
            'Criteria.criteria AS `Criteria.criteria`',
            'Criteria.tooltips AS `Criteria.tooltips`',
            'Criteria.weights AS `Criteria.weights`'
        );
        $listing = $this->Listing->findRow(array('fields'=>$fields,'conditions'=>array('Listing.id = '. $content_row->id)));

        $listing['Listing']['text'] = $content_row->text;

        $regex = '/{mosimage\s*.*?}/i';
        $listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );

        $this->set(array(
                'User'=>$this->_user,
                'listing'=>$listing
        ));

        $content_row->text = $this->render('listings','cmsblog');

        return array('row'=>$content_row,'params'=>$content_params);
    }
}
