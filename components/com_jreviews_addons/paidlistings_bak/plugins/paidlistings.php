<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsComponent extends S2Component
{
    var $plugin_order = 1;

    var $name = 'paid_listings';

    /**
    * Changed dynamically in startup method to restrict the plugin's callbacks to certain controller actions
    */
    var $published = false;

    var $order = 0;

//    var $validObserverModels = array('Listing');
    /**
    * Define where plugin should run
    */
    var $controllerActions = array(
        'about'=>'index',
        'com_content'=>'com_content_view',
        'module_listings'=>'index',
        'module_directories'=>'index', // for plans page
        'module_geomaps'=>'listings',
//        'module_reviews'=>'index', Processed directly in the Everywhere integration file for com_content
        'listings'=>array('_loadForm','_save','edit','create','_delete'),
        'media_upload'=>array('create','_save','_uploadUrl'),
        'media'=>'_saveEdit',
        'categories'=>array(
            'mylistings',
            'alphaindex',
            'section',
            'category',
            'favorites',
            'featured',
            'latest',
            'mostreviews',
            'toprated',
            'topratededitor',
            'popular',
            'featuredrandom',
            'random',
            'search'
        ),
        'fields'=>'_loadFieldData',
        'paidlistings'=>'myaccount',
        'paidlistings_plans'=>'index',
        'paidlistings_orders'=>array('_submit','complete'),
        'paidlistings_listings'=>'index',
        'paidlistings_invoices'=>array('view','unpaid'),
        'admin_paidlistings'=>'index',
        'admin_paidlistings_orders'=>'_save',
        'admin_listings'=>array('index','edit','browse','moderation','_save','_saveModeration')
    );

    var $media_types = array('photo','video','attachment','audio');

    function runPlugin(&$controller)
    {
        $this->c = &$controller;

        // Check if running in valid controller/actions
        if(!isset($this->controllerActions[$controller->name])){
            return false;
        }

        S2App::import('Model',array('paid_plan','paid_plan_category','paid_order','paid_txn_log','paid_listing_field'),'jreviews');

        S2App::import('Helper','paid_routes','jreviews');

        $actions = !is_array($this->controllerActions[$controller->name]) ? array($this->controllerActions[$controller->name]) : $this->controllerActions[$controller->name];

        if(!in_array('all',$actions) && !in_array($controller->action,$actions)) {
            return false;
        }

        return true;
    }

    function startup(&$controller)
    {
        if(!$this->runPlugin($controller))
        {
            return false;
        }
        elseif(
            defined('MVC_FRAMEWORK_ADMIN')
            ||
            (isset($controller->Config) && Sanitize::getBool($controller->Config,'paid.stealth',false) && $controller->Access->isAdmin())
            ||
            isset($controller->Config) && !Sanitize::getBool($controller->Config,'paid.stealth',false)
        )
        {
            Configure::write('PaidListings.enabled',true);

            $controller->helpers[] = 'paid';

            $controller->helpers[] = 'paid_routes';

            $this->published = true; // Enable the callbacks

            if(!defined('MVC_FRAMEWORK_ADMIN'))
            {
                switch($controller->name)
                {
                    case 'listings':

                        $controller->action != 'delete' and $this->loadAssets();

                        if($controller->action == '_save')
                        {
                            if(isset($controller->data['Paid']))
                            {
                               $controller->Config->media_photo_max_uploads_listing = Sanitize::getInt($controller->data['Paid'],'photo');

                                $controller->Config->media_video_max_uploads_listing = Sanitize::getInt($controller->data['Paid'],'video');

                                $controller->Config->media_attachment_max_uploads_listing = Sanitize::getInt($controller->data['Paid'],'attachment');

                                $controller->Config->media_audio_max_uploads_listing = Sanitize::getInt($controller->data['Paid'],'audio');

                                $this->banOverrideMediaLimits();
                            }
                        }
                    break;

                    case 'paidlistings':
                    case 'paidlistings_orders':
                    case 'paidlistings_plans':
                    case 'paidlistings_invoices':
                    case 'com_content':
                    case 'categories':
                    case 'module_geomaps':

                        $this->loadAssets();

                    break;

                    case 'module_listings':

                        isset($controller->params['module']) and $extension = Sanitize::getString($controller->params['module'],'extension') or $extension = Sanitize::getString($controller->data,'extension','com_content');

                    break;

                    case 'module_directories':

                        strstr($controller->viewSuffix,'_plans') and $controller->Directory->conditions[] = "JreviewsCategory.id IN (SELECT cat_id FROM #__jreviews_paid_plans_categories)";

                    break;

                    case 'media_upload':

                        if(in_array($controller->action,array('_save','_uploadUrl'))) {

                            $listing_id = Sanitize::getInt($controller->data['Media'],'listing_id');

                            $review_id = Sanitize::getInt($controller->data['Media'],'review_id');

                            $extension = Sanitize::getInt($controller->data['Media'],'extension');

                            $this->overrideMediaLimits($listing_id, $review_id, $extension);

                            $this->banOverrideMediaLimits();
                        }

                    break;
                }
            }
            else {

                if(!$controller->ajaxRequest) {

                    $controller->assets['js'] = array_merge(isset($controller->assets['js']) ? $controller->assets['js'] : array(),array(
                        'admin/addon_paidlistings',
                        'jstree/jquery.jstree'
                        ));

                    // Load Google API for Charts
                    cmsFramework::addScript('<script type="text/javascript" src="//www.google.com/jsapi"></script>');

                    $controller->assets['css'] = array_merge(isset($controller->assets['css']) ? $controller->assets['css'] : array(),array(
                        'admin/paidlistings',
                        'admin/jstree-checkbox/style'
                        ));
                }
            }
        }
    }

/************************************************************************
* CALLBACK METHODS
************************************************************************/

/*    function plgAfterFind(&$model, $results)
    {
    }*/

    function plgAfterAfterFind(&$model, $results)
    {
        if(empty($results))
        {
            return $results;
        }

        switch($this->c->name)
        {
            case (defined('MVC_FRAMEWORK_ADMIN') && ($this->c->name=='admin_listings' || $this->c->name=='listings')):

                if(in_array($this->c->action,array('index','moderation','browse','create')))
                {
                    $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

                    $PaidCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

                    $paid_cat_ids = $PaidCategory->getPaidCatIdsArray();

                    foreach($results AS $listing_id=>$listing) {

                        if(in_array($listing['Category']['cat_id'],$paid_cat_ids)) {

                            $results[$listing_id]['PaidPlanCategory']['cat_id'] = $listing['Category']['cat_id'];
                        }
                    }

                    $results = $PaidOrder->completeOrderInfo($results, array() /* retrieves all orders for listing */);
                }

            break;

            case 'listings':

                $this->c->action == 'edit' and $this->processPaidData($results);

            break;

            case 'admin_paidlistings_orders':

                $this->c->action == '_save' and $this->processPaidData($results);

            break;

            case 'paidlistings_orders':

                $this->c->action == 'complete' and $this->processPaidData($results);

            break;

            case 'paidlistings_listings':
            case 'module_listings':
            case 'module_geomaps':
            case 'com_content':

                $this->processPaidData($results);

            break;

            case 'categories':
                switch($this->c->action)
                {
                    case 'alphaindex':
                    case 'section':
                    case 'category':
                    case 'custom':
                    case 'favorites':
                    case 'featured':
                    case 'latest':
                    case 'mostreviews':
                    case 'toprated':
                    case 'topratededitor':
                    case 'popular':
                    case 'featuredrandom':
                    case 'random':
                    case 'mylistings':
                    case 'search':
                        $this->processPaidData($results);
                    break;
                    default:
                    break;
                }
                break;
            break;
        }

        return $results;
    }

    function plgAfterDelete(&$model,$data)
    {
        $PaidListingField = ClassRegistry::getClass('PaidListingFieldModel');

        $data = array_shift($data);

        $listing_id = Sanitize::getInt($data['Listing'],'id');

        $PaidListingField->delete('listing_id',$listing_id);
    }

    function plgBeforeSave(&$model,$data)
    {
        $PaidCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

        switch($this->c->name)
        {
            case 'admin_listings':

                if($this->c->action == '_save')
                {
                    if($PaidCategory->isInPaidCategory($data['Listing']['catid']))
                    {
                        $data['paid_category'] = 1;  // read in fields model save method
                    }
                }
            break;

            case 'listings':

                if($this->c->action == '_save')
                {
                    if($PaidCategory->isInPaidCategory($data['Listing']['catid']))
                    {
                        $data['paid_category'] = 1;  // read in fields model save method

                        // Make sure it's a new listing
                        if(!isset($data['Listing']['id']) or ($data['Listing']['id']==''))
                        {
                            if(isset($data['PaidOrder']) && $plan_id = Sanitize::getInt($data['PaidOrder'],'plan_id'))
                            {
                                $PaidPlan = ClassRegistry::getClass('PaidPlanModel');

                                $plan = $PaidPlan->findRow(array('conditions'=>'PaidPlan.plan_id = ' . $plan_id),array('afterFind'));

                                // Apply plan moderation setting only if it's a free plan, otherwise we wait for payment
                                if($plan['PaidPlan']['payment_type'] == 2)
                                {
                                    $data['Listing']['state'] = !$plan['PaidPlan']['plan_array']['moderation'];
                                }
                                else
                                {
                                    $data['Listing']['state'] = 0;
                                }
                            }
                            else
                            {
                                $data['Listing']['state'] = 0;
                            }
                        }
                    }
                }
            break;
        }
        return $data;
    }
    /**
    * Executed before rendering the theme file.
    * All variables sent to theme are available in the $this->c->viewVars array and can be modified on the fly
    *
    */
    function plgBeforeRender()
    {
        # EDIT paid listing

        if(($this->c->name == 'listings' && $this->c->action == 'edit')
            || ($this->c->name == 'listings' && defined('MVC_FRAMEWORK_ADMIN') && $this->c->action == 'create') )
        {
            $plans = array();

            $listing = & $this->c->viewVars['listing'];

            if(isset($listing['Paid']))
            {
                $plans['plan0'] = array(
                    'fields'=>$listing['Paid']['fields'],
                    'photo'=>$listing['Paid']['photo'],
                    'video'=>$listing['Paid']['video'],
                    'attachment'=>$listing['Paid']['attachment'],
                    'audio'=>$listing['Paid']['audio']
                );

                // Overrides media count with the count for the paid plan
                $this->c->Config->media_photo_max_uploads_listing = Sanitize::getInt($listing['Paid'],'photo');

                $this->c->Config->media_video_max_uploads_listing = Sanitize::getInt($listing['Paid'],'video');

                $this->c->Config->media_attachment_max_uploads_listing = Sanitize::getInt($listing['Paid'],'attachment');

                $this->c->Config->media_audio_max_uploads_listing = Sanitize::getInt($listing['Paid'],'audio');

                if(Sanitize::getInt($this->c->Config,'libraries_scripts_loader',0)) {
                    echo $this->c->makeJS("
                        head.ready(function() {
                            jreviews.paid.plan.planList = ".json_encode($plans).";
                            jreviews.paid.plan.plan_selected = 0;
                        })
                    ");
                }
                else {
                    echo $this->c->makeJS("
                        jreviews.paid.plan.planList = ".json_encode($plans).";
                        jreviews.paid.plan.plan_selected = 0;
                    ");
                }
            }
        }

        # SUBMIT NEW paid listing with plan id in url

        if($this->c->name == 'listings' && $this->c->action == 'create')
        {
            if($plan_id = Sanitize::getInt($this->c->params,'plan_id'))
            {
                echo $this->c->makeJS("jreviews.paid.plan.plan_selected = " . $plan_id . ";");
            }
        }

        # SUBMIT NEW paidlisting, after category select. Paid plan info is loaded for chosen category

        if($this->c->name == 'listings' && $this->c->action == '_loadForm')
        {
            $selected = 0;

            $PlanModel = ClassRegistry::getClass('PaidPlanModel');

            $PaidOrderModel = ClassRegistry::getClass('PaidOrderModel');

            $cat_id = Sanitize::getVar($this->c->data['Listing'],'catid');

            $level = (int) str_replace('cat_id','',Sanitize::getVar($this->c->data,'level'));

            if(is_array($cat_id))
            {
                $cat_id = array_filter($cat_id);

                switch($level) {
                    case 1:
                        $cat_id = (int) $cat_id[0];
                    break;
                    default:
                        $cat_id = (int) array_pop($cat_id);
                    break;
                }
            }
            else {
                $cat_id = (int) $cat_id;
            }

            $plans = $PlanModel->getCatPlans($cat_id);

            $plansArray = array();

            if(empty($plans['PlanType0'])) {

                if(isset($plans['used_trials']) && $plans['used_trials'] > 0) {

                    $this->c->viewVars['paid_plans'] = array('used_trials'=>$plans['used_trials']);
                }

                return;
            }

            // Remove plans hidden in settings
            foreach($plans['PlanType0'] AS $payment_type=>$plan_rows)
            {
                foreach($plan_rows AS $plan_id=>$plan)
                {
                    # Free plan limit validation
                    if($payment_type == 'PaymentType2' && Sanitize::getInt($plan['plan_array'],'trial_limit') > 0)
                    {
                        $trial_limit = $plan['plan_array']['trial_limit'];

                        // Find the number of existing Complete orders with the same plan
                        $used_trials = $PaidOrderModel->findCount(array(
                            'conditions'=>array(
                                "PaidOrder.user_id = " . $this->c->_user->id,
                                "PaidOrder.plan_id = " . $plan_id,
                                "PaidOrder.order_status = 'Complete'"
                            ),
                            'session_cache'=>false
                        ));


                       if($used_trials >= $trial_limit)
                       {
                        unset($plans['PlanType0'][$payment_type][$plan_id]);
                        continue;
                       }
                    }

                    // Remove hidden plans from array

                    if(!Sanitize::getInt($plan['plan_array'],'submit_form',1))
                    {
                        unset($plans['PlanType0'][$payment_type][$plan_id]);

                        if(empty($plans['PlanType0'][$payment_type])) unset($plans['PlanType0'][$payment_type]);
                    }

                    if($plan['plan_default']) {

                        $selected = $plan_id;
                    }

                    $plansArray['plan'.$plan_id] = array(
                        'fields'=>Sanitize::getVar($plan['plan_array'],'fields',array()),
                        'photos'=>Sanitize::getString($plan,'photo',''),
                        'videos'=>Sanitize::getString($plan,'video',''),
                        'attachments'=>Sanitize::getString($plan,'attachment',''),
                        'audio'=>Sanitize::getString($plan,'audio',''),
                        'free'=>$plan['payment_type'] == 2 ? 1 : 0
                    );
                }
            }

            // For field relations and filtering
            if(!empty($plansArray))
            {
                $this->c->plgResponse['planList'] = $plansArray;

                if($selected) {

                    $this->c->plgResponse['plan_selected'] = $selected;
                }
            }

            // For checkout flow, displays plans list in listing form
            if(!empty($plans['PlanType0']) || !empty($plans['PlanDefault']))
            {
                $this->c->viewVars['paid_plans'] = $plans;
            }
        }

        # UPLOAD new media

        if($this->c->name == 'media_upload' && $this->c->action == 'create') {

            $review_id = $listing_id = 0;

            $id = explode(':',base64_decode(urldecode(Sanitize::getString($this->c->params,'id'))));

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

            // If it's a review media upload or an upload for an everywhere extension don't do anything
            // Later could add ability to enable/disable review uploads in paid plans

            $this->overrideMediaLimits($listing_id, $review_id, $extension);
        }
    }

    /**
    * POST SUBMIT paid listing redirect to ORDER FORM
    * Intercepts the response after all validation checks have completed
    * and custom fields and review have been saved.
    * Redirects the user to the orders page for payment options
    */
    function plgBeforeRenderListingSave(&$model)
    {
        $response = array();

        $PaidPlan = ClassRegistry::getClass('PaidPlanModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $PaidCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

        $cat_id = $model->data['Listing']['catid'];

        $plan_id = isset($model->data['PaidOrder']) ? Sanitize::getInt($model->data['PaidOrder'],'plan_id') : 0;

        $listing_id = $model->data['Listing']['id'];

        $response['plgBeforeRenderListingSave'] = true;

        $response['listing_title'] = $model->data['Listing']['title'];

        $response['listing_id'] = $listing_id;

        $response['referrer'] = 'create';

        if($plan_id > 0 && $plan = $PaidPlan->findRow(array(
            'conditions'=>array(
                'PaidCategory.cat_id = ' . $cat_id,
//                'PaidPlan.plan_default = 1',
                'PaidPlan.plan_state = 1',
                'PaidPlan.plan_id = ' . $plan_id
            ),
            'joins'=>array(
                'LEFT JOIN #__jreviews_paid_plans_categories AS PaidCategory ON PaidPlan.plan_id = PaidCategory.plan_id'
            )
        ))){
            // Override media counts with those for the selected plan
            foreach($this->media_types AS $media_type) {

                $paid_limit = Sanitize::getVar($plan['PaidPlan'],$media_type);

                $this->c->Config->{'media_'.$media_type.'_max_uploads_listing'} = $paid_limit;
            }

            /**
             * Tracking code
             */
            $price = $plan['PaidPlan']['plan_price'];

            if($track = Sanitize::stripWhiteSpace(Sanitize::getVar($this->c->Config,'paid.track_listing_submit','')))
            {
                $track = self::trackingReplacements($track,array(
                    'PaidOrder'=>array(
                        'order_amount'=>$plan['PaidPlan']['plan_price'],
                        'plan_info'=>array('plan_name'=>addslashes($plan['PaidPlan']['plan_name']))
                    ),
                ));

                $response['track'] = html_entity_decode($track,ENT_QUOTES,cmsFramework::getCharset());
            }

            if($price == 0)
            {
                $listing = $model->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)),array());

                $order = $PaidOrder->makeOrder($plan,$listing);

                $PaidOrder->store($order);

                $this->processFreeOrder($order);

                return (string) $plan['PaidPlan']['plan_array']['moderation'];
            }

            $response['plan_id'] = $plan_id;

            $response['plan_type'] = $plan['PaidPlan']['plan_type'];

            $response['price'] = $price;

            $response['page'] = 2;

            $response['referrer'] = 'create';

            return $response;
        }
        elseif($PaidCategory->isInPaidCategory($cat_id)) {

            // A plan was not selected
            return $response;
        }
        else {
            // Continue with normal processing of listings, nothing to do with plans
            return '';
        }
    }

    /**
     * Prevents the ConfigComponnet override method from overriding the media limits since
     * we've already set the correct values for these
     */
    function banOverrideMediaLimits() {

        $override_ban = Configure::read('JreviewsSystem.OverrideBan');

        if(!is_array($override_ban))
        {
            $override_ban = array(
                'media_photo_max_uploads_listing',
                'media_video_max_uploads_listing',
                'media_attachment_max_uploads_listing',
                'media_audio_max_uploads_listing'
                );
        }
        else {

            $override_ban[] = 'media_photo_max_uploads_listing';

            $override_ban[] = 'media_video_max_uploads_listing';

            $override_ban[] = 'media_attachment_max_uploads_listing';

            $override_ban[] = 'media_audio_max_uploads_listing';
        }

        Configure::write('JreviewsSystem.OverrideBan',$override_ban);
    }

    function isWithinMediaLimits($listing_id, $review_id, $extension, $media_type) {

        if($review_id > 0 || $extension != 'com_content') return true;

        // It's a listing upload so we continue

        // First get the media count allowance based on existing orders to override global/listing type settings
        $PaidOrderModel = ClassRegistry::getClass('PaidOrderModel');

        $MediaModel = ClassRegistry::getClass('MediaModel');

        $orders = $PaidOrderModel->completeOrderInfo($listing_id);

        if($orders) {

            $orders = array_shift($orders);

            if(isset($orders['Paid'])) {

                $published_count = $MediaModel->getListingPublishedUploads($listing_id, $review_id, $extension, $media_type);

                $paid_limit = Sanitize::getVar($orders['Paid'],$media_type);

                if($paid_limit != '' && $published_count >= $paid_limit) {

                    return false;
                }
            }
        }

        return true;
    }

    function overrideMediaLimitsByPlanId($plan_id) {

        $PaidPlan = ClassRegistry::getClass('PaidPlanModel');

        $plan = $PaidPlan->findRow(array('conditions'=>array('PaidPlan.plan_id = ' . $plan_id)));

        if($plan) {

            foreach($this->media_types AS $media_type) {

                $paid_limit = Sanitize::getVar($plan['PaidPlan'],$media_type);

                // Override only if not empty
                $paid_limit != '' and $this->c->Config->{'media_'.$media_type.'_max_uploads_listing'} = $paid_limit;
            }
        }
    }

    function overrideMediaLimits($listing_id, $review_id, $extension) {

        if($review_id > 0 || $extension != 'com_content') return;

        // It's a listing upload so we continue

        // Continue only if listing is in paid category
        $PaidPlanCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

        if(!$PaidPlanCategory->isInPaidCategoryByListingId($listing_id)) return;

        // First get the media count allowance based on existing orders to override global/listing type settings
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $orders = $PaidOrder->completeOrderInfo($listing_id);

        if($orders) {

            $orders = array_shift($orders);

            // For listings without any paid/complete orders, we default to the limits of incomplete orders
            if(!isset($orders['Paid'])) {

                $orders = $PaidOrder->completeOrderInfo($listing_id,array('order_active'=>0, 'order_status'=>'Incomplete','paid_data'=>true));

                $orders = array_shift($orders);
            }

            if(isset($orders['Paid'])) {

                foreach($this->media_types AS $media_type) {

                    $paid_limit = Sanitize::getVar($orders['Paid'],$media_type);

                    // Override only if not empty
                    $paid_limit != '' and $this->c->Config->{'media_'.$media_type.'_max_uploads_listing'} = $paid_limit;
                }
            }
            else {

                // For listings without orders (paid or otherwise we remove the ability to upload anything)
                foreach($this->media_types AS $media_type) {

                    $this->c->Config->{'media_'.$media_type.'_max_uploads_listing'} = 0;
                }
            }
        }
    }

    /**
     * Ensures the number of published media for a listing matches the purchased number
     * By unpublishing the ones that exceed the paid limit or publishing the ones below it
     * @param  mixed  $listing      listing id or listing array
     * @param  boolean $auto_publish automatically publishes media below the paid limit
     */
    function syncMediaLimits($listing, $auto_publish = false) {

        $update_counts = false;

        if(is_numeric($listing)) {

            $this->c->Listing->addStopAfterFindModel(array('Favorite','Media','Field'));

            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing)));
        }

        $listing_id = $listing['Listing']['listing_id'];

        $extension = $listing['Listing']['extension'];

        // Set the correct ordering
        $sort = Sanitize::getString($listing['ListingType']['config'],'media_general_default_order_listing',-1);

        $sort == -1 and $sort = $this->c->Config->media_general_default_order_listing;

        $MediaModel = ClassRegistry::getClass('MediaModel');

        switch($sort) {

            case 'ordering':
                $orderby = 'Media.ordering';
                break;

            case 'oldest':
                $orderby = 'Media.created ASC';
                break;

            case 'popular':
                $orderby = 'Media.views DESC';
                break;

            case 'liked':
                $orderby = 'Media.likes_rank DESC';
                break;

            case 'newest':
            case 'recent':
            default:
                $orderby = 'Media.created DESC';
                break;
        }

        foreach($this->media_types AS $media_type) {

            // Set the correct limit based on media type
            $global_limit = $this->c->Config->getOverride('media_'.$media_type.'_max_uploads_listing',$listing['ListingType']['config']);

            $paid_limit = !isset($listing['Paid']) ? 0 : Sanitize::getVar($listing['Paid'],$media_type);

            $main_media = 0;

            // If unlimited uploads, then don't do anything
            if((string) $paid_limit != '' && $listing['Listing'][$media_type.'_count_owner'] > $paid_limit) {

                // If uploads allowed with plan, check if the listing has a main media set so we don't unpublish that one
                if($paid_limit > 0) {

                    $query = "
                        SELECT
                            media_id
                        FROM
                            #__jreviews_media AS Media
                        WHERE
                            Media.listing_id = " . $listing_id . "
                            AND Media.review_id = 0
                            AND Media.extension = " . $this->c->Quote($extension) . "
                            AND Media.published = 1
                            AND Media.media_type = " . $this->c->Quote($media_type) . "
                            AND Media.main_media = 1"
                        ;

                    $main_media = $this->c->Media->query($query,'loadResult');

                    $main_media and $paid_limit--;
                }

                if($paid_limit > 0) {

                    // Unpublish only media that exceeds the paid limit
                    // Get the rest of the media ids using the pre-defined or

                    $query = "
                        SELECT
                            Media.media_id
                        FROM
                            #__jreviews_media AS Media
                        WHERE
                            Media.listing_id = " . $listing_id . "
                            AND Media.review_id = 0
                            AND Media.extension = " . $this->c->Quote($extension) . "
                            AND Media.published = 1
                            AND Media.media_type = " . $this->c->Quote($media_type) . "
                            " . ($main_media ? " AND Media.main_media = 0" : '') . "
                        ORDER BY " . $orderby . "
                        LIMIT " . $paid_limit
                        ;

                    $media_ids = $this->c->Media->query($query,'loadColumn');

                    $main_media and $media_ids[] = $main_media;

                    if($media_ids) {

                        $update_counts = true;

                         $query = "
                            UPDATE
                                #__jreviews_media
                            SET
                                published = 0
                            WHERE
                                media_id NOT IN (".cleanIntegerCommaList($media_ids) . ")
                                AND media_type = " . $this->c->Quote($media_type) . "
                                AND review_id = 0
                                AND listing_id = " . $listing_id
                        ;

                        $this->c->Media->query($query,'query');
                    }
                }
                else {

                    // Unpublish all media for the listing

                    $update_counts = true;

                     $query = "
                        UPDATE
                            #__jreviews_media
                        SET
                            published = 0
                        WHERE
                            media_type = " . $this->c->Quote($media_type) . "
                            AND review_id = 0
                            AND listing_id = " . $listing_id
                    ;

                    $this->c->Media->query($query,'query');
                }

            }
            elseif ($auto_publish == true && ((string) $paid_limit == '' || ($paid_limit != '' && $listing['Listing'][$media_type.'_count_owner'] < $paid_limit))) {

                $update_counts = true;

                // Re-publish media up the the allowed limit
                $query = "SET @i:=0, @max:= " . ($paid_limit == '' ? "''" : $paid_limit );

                $this->c->Media->query($query,'query');

                $query = "
                    UPDATE
                        #__jreviews_media AS Media
                    SET published = (
                        CASE
                            WHEN @max = '' THEN 1
                            WHEN @max = 0 THEN 0
                            WHEN (@i := @i + 1) <= @max THEN 1 ELSE 0
                        END )
                    WHERE
                        listing_id = " . $listing_id . "
                        AND review_id = 0
                        AND media_type = " . $this->c->Quote($media_type) . "
                    ORDER BY Media.main_media DESC, " . $orderby
                ;

                $this->c->Media->query($query,'query');
            }

        }

        $update_counts == true and $MediaModel->updateListingCounts($listing_id, $extension);

        return $update_counts;
    }

    /**
    * Gets rid of everything that shouldn't be shown on the page
    * like images and fields. Adds paid variables from the selected plan if any
    *
    * @param array $results
    */

    function processPaidData(&$results)
    {
        if(!isset($this->c->Config))
        {
            $Config = Configure::read('JreviewsSystem.Config');
        }
        else {
            $Config = $this->c->Config;
        }

        if(!isset($this->c->_user))
        {
            $User = cmsFramework::getUser();
        }
        else {
            $User = $this->c->_user;
        }

        // We need to retrieve payment plan info for each listing and remove fields not applicable to the payment plan
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $PaidPlan = ClassRegistry::getClass('PaidPlanModel');

        $PaidCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

        // Geomaps integration
        $jr_lat = Sanitize::getString($Config,'geomaps.latitude');

        $results = $PaidOrder->completeOrderInfo($results);

        $User->id > 0 and $results = $PaidPlan->completePlanInfo($results);

        $paid_cat_ids = $PaidCategory->getPaidCatIdsArray();

        // Now remove un-paid stuff
        foreach($results AS $listing_id=>$listing)
        {
            if(
                in_array($listing['Category']['cat_id'],$paid_cat_ids)
                &&
                (
                !isset($listing['Listing']['extension']) || $listing['Listing']['extension']=='com_content'
                )
            ){
                $results[$listing_id]['PaidPlanCategory']['cat_id'] = $listing['Category']['cat_id'];

                /**
                *  Geomaps integration
                */
                // Add check for GeoMaps coordinate fields and remove them if not in paid fields
                if(!isset($listing['Paid'])  || !in_array($jr_lat,Sanitize::getVar($listing['Paid'],'fields'))) {

                    unset($results[$listing_id]['Geomaps']);
                }

                // Process Fields
                if(!empty($listing['Field']['groups'])) {

                    foreach($listing['Field']['groups'] AS $group_name=>$group_fields)
                    {
                        if(isset($listing['Paid'])) // The listing has a plan attached to it
                        {
                            // Remove fields not included with the plan
                            $fields_flip = array_flip($listing['Paid']['fields']);

                            $results[$listing_id]['Field']['groups'][$group_name]['Fields'] = array_intersect_key($listing['Field']['groups'][$group_name]['Fields'],$fields_flip);

                            $results[$listing_id]['Field']['pairs'] = array_intersect_key($listing['Field']['pairs'],$fields_flip);
                        }
                        else {

                            // If listing in paid category without a plan, remove all fields
                            $results[$listing_id]['Field']['groups'][$group_name]['Fields'] = $results[$listing_id]['Field']['pairs'] = array();
                        }
                    }
                }

                // Process Images
                if($listing['Listing']['media_count_owner'] > 0)
                {
                    // Limit media counts to paid plan's allowance
                    $sync_run = $this->syncMediaLimits($listing);

                    if(!$sync_run) continue;

                    // Only continue if there was something to sync before, otherwise the counts are already limited
                    $paid_limits = array();

                    if($results[$listing_id]['Listing']['media_count_owner'] == 0) continue;

                    foreach($this->media_types AS $media_type) {

                        $paid_limits[$media_type] = isset($listing['Paid']) ? Sanitize::getVar($listing['Paid'],$media_type) : 0;

                        $results[$listing_id]['ListingType']['config']['media_'.$media_type.'_max_uploads_listing'] = $paid_limits[$media_type];

                        $results[$listing_id]['Listing'][$media_type.'_count_owner'] = $paid_limits[$media_type] == ''

                                    ? $results[$listing_id]['Listing'][$media_type.'_count_owner']

                                    :

                                    min($results[$listing_id]['Listing'][$media_type.'_count_owner'],$paid_limits[$media_type]);
                    }

                    $results[$listing_id]['Listing']['media_count_owner'] = $results[$listing_id]['Listing']['photo_count_owner']
                        + $results[$listing_id]['Listing']['video_count_owner']
                        + $results[$listing_id]['Listing']['attachment_count_owner']
                        + $results[$listing_id]['Listing']['audio_count_owner'];

                    $results[$listing_id]['Listing']['media_count'] = $results[$listing_id]['Listing']['media_count_owner']
                        + $results[$listing_id]['Listing']['media_count_user'];

                    foreach($this->media_types AS $media_type) {

                        $results[$listing_id]['Listing'][$media_type.'_count'] = $results[$listing_id]['Listing'][$media_type.'_count_owner']
                            + $results[$listing_id]['Listing'][$media_type.'_count_user'];
                    }

                    $main_media_type = null;

                    if(isset($results[$listing_id]['MainMedia'])) {

                        $main_media_user_id = Sanitize::getInt($results[$listing_id]['MainMedia'],'user_id');

                        $main_media_type = $results[$listing_id]['MainMedia']['media_type'];

                        $media_type_owner_count = $results[$listing_id]['Listing'][$main_media_type.'_count_owner'];

                        if($media_type_owner_count > 1 && ($paid_limits[$main_media_type] != '' && $paid_limits[$main_media_type] <= 1) && $main_media_user_id == $listing['User']['user_id']) {

                            unset($results[$listing_id]['MainMedia']['media_info']);
                        }
                    }

                    foreach($this->media_types AS $media_type) {

                        if(isset($results[$listing_id]['Media'][$media_type]) && $paid_limits[$media_type] != '') {

                             $results[$listing_id]['Media'][$media_type] = array_splice($results[$listing_id]['Media'][$media_type],0, $results[$listing_id]['Listing'][$media_type.'_count_owner'] - (int) ($main_media_type == $media_type));
                        }
                    }
                }
            }
        }
    }

    /**
    * Called from the payment handler to finalize the processing of the order
    * Makes changes to the listing and adds notes to the txn record for the order which is saved in the handler itself.
    *
    * @param array $order
    */
    function processSuccessfulOrder($order,$listing_state = null)
    {
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        if(!is_array($order['PaidOrder']['plan_info'])) // Make sure we are dealing with an array
        {
            $order['PaidOrder']['plan_info'] = json_decode($order['PaidOrder']['plan_info'],true);
        }

        # For renewals don't process the order. It will be done via cron on renewal date
        $renewal_date = Sanitize::getVar($order['PaidOrder'],'order_renewal',null);

        if(!is_null($renewal_date) && $renewal_date != _NULL_DATE)
        {
            $PaidTxnLog->addNote(__t("Order renewal set.",true));

            !defined('MVC_FRAMEWORK_ADMIN') and $PaidOrder->updateOrder($order,array('order_status'=>'Complete','order_active'=>0)); // Do not run when editing an order via the administration

            return true;
        }

        # Process Order by Plan Type
        switch($order['PaidOrder']['plan_type'])
        {
            case 0: $process = $this->processNewListing($order,$listing_state); break; // New Listing Plan

            case 1: $process = $this->processUpgradeListing($order); break; // Upgrade Listing Plan
        }

         if(!$process){

            $PaidTxnLog->addNote(__t("Order processing error.",true));

            return false;
         }

        # Transfer fields from paid_listing_fields to jreviews_content
        $PaidListingField = ClassRegistry::getClass('PaidListingFieldModel');

        $transferred = $PaidListingField->moveFieldsToListing($order['PaidOrder']['listing_id'],$order['PaidOrder']['plan_info']);

        if(!$transferred)
        {
            $PaidTxnLog->addNote("Error updating field values");
        }

        # Run only for new orders on the front-end and auto-generated orders via admin

        if(!defined('MVC_FRAMEWORK_ADMIN') || (defined('MVC_FRAMEWORK_ADMIN') && $this->c->name == 'admin_paidlistings_orders' && $this->c->action != '_save'))
        {
            $PaidTxnLog->addNote("Payment status: " . $order['PaidOrder']['order_status']);

            $PaidTxnLog->addNote("Order active: 1");

            $PaidOrder->updateOrder($order,array('order_renewal'=>_NULL_DATE,'order_active'=>1));
        }

        # Sync media limits
        $this->c->Listing->addStopAfterFindModel(array('Favorite','Media','Field'));

        $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = ' . $order['PaidOrder']['listing_id'])));

        if(empty($listing)) return false;

        // New listing
        if($order['PaidOrder']['plan_type'] == 0) {

            foreach($this->media_types AS $media_type) {

                $global_limit = $this->c->Config->getOverride('media_'.$media_type.'_max_uploads_listing',$listing['ListingType']['config']);

                $paid_limit = $order['PaidOrder']['plan_info'][$media_type];

                $listing['Paid'][$media_type] = $paid_limit == '' ? $global_limit : $paid_limit;
            }
        }
        else {
        // Upgrade

            foreach($this->media_types AS $media_type) {

                if($order['PaidOrder']['plan_info'][$media_type] != '') {

                    $listing['Paid'][$media_type] = $order['PaidOrder']['plan_info'][$media_type];
                }
            }
        }

        $this->syncMediaLimits($listing, true);

        return true;
    }

    function processFreeOrder(&$order)
    {
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $this->c->action = '_process'; // To make the notifications component send the correct emails

        $order['PaidOrder']['order_status'] = 'Complete';

        if($this->processSuccessfulOrder($order))
        {

            $PaidTxnLog->save($order, '', 'FREE-'.$order['PaidOrder']['order_id'], true);

            return true;
        }

        return false;
    }

    function processNewListing($order,$listing_state)
    {
        $txn_duplicate = false;

        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        if(isset($order['txn_id']))
        {
            $txn_duplicate = $PaidTxnLog->findDuplicate($order['txn_id']);
        }

        $listing_state = !is_null($listing_state) ? $listing_state : !$order['PaidOrder']['plan_info']['plan_array']['moderation'];

        $listing = array(
                'Listing'=>array(
                    'id'=>$order['PaidOrder']['listing_id'],
                    'state'=>$listing_state,
                    'publish_down'=>$order['PaidOrder']['order_expires'],
                )
            );

        $this->c->Listing->isNew = true; // Used by other plugins

        $this->c->_user->id = $order['PaidOrder']['user_id']; // Function called by payment handler.  We need to force the user id since there isn' a logged in user

        /**
        *  If run from the listings controller then we want to avoid running the callbacks a second time
        *  The listing state should have been updated in the plgBeforeSave method in this plugin before other plugins are run
        *
        *  If payment handler post request don't run plugins because they are run when returned to the site
        */
        if($this->c->name == 'listings' || $txn_duplicate)
        {
            $store = $this->c->Listing->store($listing,false,array() /* Disable callbacks*/);
        }
        else
        {
            $store = $this->c->Listing->store($listing);
        }

        if($store)
        {
            $PaidTxnLog->addNote(sprintf(__t("Listing expiration: %s",true),$order['PaidOrder']['order_expires']));

            $PaidTxnLog->addNote(sprintf(__t("Listing state: %s",true),$listing_state));

            if($PaidOrder->changeFeaturedState($order['PaidOrder']['listing_id'],$order['PaidOrder']['plan_info']['plan_featured']))
            {
                $PaidTxnLog->addNote("Listing featured: " . $order['PaidOrder']['plan_info']['plan_featured']);
            }

            return true;
        }

        return false;
    }

    function processUpgradeListing($order)
    {
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        // Upgrades consist on making the listing featured, adding more images or new fields. Only the featured addition requires updates to the listing
        if($order['PaidOrder']['plan_info']['plan_featured']
            &&
            $PaidOrder->changeFeaturedState($order['PaidOrder']['listing_id'],$order['PaidOrder']['plan_info']['plan_featured'])
        )
        {

            $PaidTxnLog->addNote("Listing featured: " . $order['PaidOrder']['plan_info']['plan_featured']);
        }

        return true;
    }

    /**
    * Called from the payment handler to process a failed payment attempt and unpublish the listing
    * @param array $order
    * @param int $listing_state
    */
    function processFailedOrder($order,$listing_state = 0)
    {
        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $listing = array(
            'Listing'=>array(
                'id'=>$order['PaidOrder']['listing_id'],
                'state'=>$listing_state
            )
        );

        if($this->c->Listing->store($listing))
        {
            $PaidTxnLog->addNote(sprintf(__t("Listing state: %s",true),$listing_state));
        }
    }

/************************************************************************
* AUXILIARY METHODS
************************************************************************/

    /**
    * Adds js and css assets to the $this->c->assets array to be processed later on by the assets helper
    * Need to be set here instead of theme files for pages that can be cached
    *
    */
    function loadAssets()
    {
        switch($this->c->name){

            case 'paidlistings_plans':

                $this->c->assets['js'][] = 'paidlistings';

                $this->c->assets['css'][] = 'paidlistings';

                $this->c->assets['css'][] = 'form';

                break;

            case 'paidlistings_invoices':

                $this->c->assets['css'][] = 'paidlistings';

                break;

            case 'paidlistings_orders':

                $this->c->assets['css'][] = 'paidlistings';

                $this->c->assets['css'][] = 'theme';

                break;

            case 'paidlistings': // my account page

                $this->c->assets['js'][] = 'paidlistings';

                $this->c->assets['css'][] = 'paidlistings';

                $this->c->assets['css'][] = 'theme';

                $this->c->assets['css'][] = 'theme.list';

                $this->c->assets['css'][] = 'theme.form';

            break;

            case 'com_content':

            case 'listings':

                $this->c->assets['js'][] = 'paidlistings';

                $this->c->assets['css'][] = 'paidlistings';

                break;
        }
    }

    /**
    * Replacers order related tags in tracking code added by admins for payment steps
    *
    * @param mixed $code
    * @param mixed $order
    * @return mixed
    */
    function trackingReplacements(&$code, $order)
    {
        !is_array($order['PaidOrder']['plan_info']) and $order['PaidOrder']['plan_info'] = json_decode($order['PaidOrder']['plan_info'],true);
        $code = str_replace(
            array(
                '{order_amount}',
                '{order_id}',
                '{plan_name}'
            ),
            array(
                Sanitize::getFloat($order['PaidOrder'],'order_amount'),
                Sanitize::getInt($order['PaidOrder'],'order_id'),
                addslashes(Sanitize::getString($order['PaidOrder']['plan_info'],'plan_name')),
            )
            ,$code
        );
        return $code;
    }
}
