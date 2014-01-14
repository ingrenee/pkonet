<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/**
 * Automatically creates user accounts for guest submissions using the Joomla settings.
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UserAccountComponent extends S2Component {

    var $plugin_order = 1;

    var $name = 'register';

    var $published = false;

    var $ranBeforeSave = false;

    var $ranAfterSave = false;

    /**
    * Define where plugin should run
    */
    var $controllerActions = array(
        'com_content'=>array('com_content_view'),
        'everywhere'=>array('index'),
        'media_upload'=>array('create','_save','_uploadUrl','_embedVideo'),
        'listings'=>array('create','_loadForm','_save'),
        'discussions'=>array('create','_save','review','reply'),
        'reviews'=>array('_save'),
        'paidlistings_orders'=>array('_getOrderForm','_submit','_process','validateCoupon')
    );

    function runPlugin(&$controller)
    {
        // Check if running in valid controller/actions
        if(!isset($this->controllerActions[$controller->name])){
            return false;
        }

        $actions = !is_array($this->controllerActions[$controller->name]) ? array($this->controllerActions[$controller->name]) : $this->controllerActions[$controller->name];

        if(!in_array('all',$actions) && !in_array($controller->action,$actions)) {
            return false;
        }

        return true;
    }

    function startup(&$controller)
    {
        if(!$this->runPlugin($controller) || !$controller->Access->isGuest() || defined('MVC_FRAMEWORK_ADMIN'))
        {
            return false;
        }

        $this->c = & $controller;

        // Trash the session info if it's a logged in user or if it has expired
        $user_session = self::getUser();

        $User = cmsFramework::getUser();

        if($User->id > 0

            || (Sanitize::getInt($user_session,'user_id') == 0 && (Sanitize::getInt($user_session,'name') == '' || Sanitize::getInt($user_session,'email') == ''))

            || Sanitize::getInt($user_session,'session_time') <  time() - 3600) {

            $user_session = array();

            cmsFramework::clearSessionVar('user','jreviews');

            cmsFramework::clearSessionVar('gid','jreviews');
        }

        /**
         * For paid listing submissions by guests the user account creation feature must be enabled
         */
        if($controller->Access->isGuest()
            && $controller->name == 'listings'
            && in_array($controller->action,array('_loadForm','_save'))) {

            if(S2App::import('Model','paid_plan_category')) {

                $PaidCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

                $selected_catid = Sanitize::getVar($controller->data['Listing'],'catid');

                if(is_array($selected_catid)) {

                    $curr_cat_id = array_filter($selected_catid);

                    $curr_cat_id = (int) array_pop($curr_cat_id);
                }
                else {

                    $curr_cat_id = (int) $selected_catid;
                }

                $paid_cat_ids = $PaidCategory->getPaidCatIdsArray();

                // If it's a paid listing force account creation ON
                if(in_array($curr_cat_id,$paid_cat_ids)) {

                    $this->c->Config->user_registration_guest = 1;

                    $this->c->Config->content_name = 'required';

                    $this->c->Config->content_email = 'required';

                    $this->c->Config->content_username = 1;
                }
            }
        }

        if(!$User->id)
        {
            $this->published = true;

            $new_user = $user_session;

            $register_guests = (!empty($user_session) && Sanitize::getInt($user_session,'user_id') > 0) /* Required for subsequent form displays after a paidlisting is submitted when account creation is disabled */
                                || Sanitize::getBool($this->c->Config,'user_registration_guest');

            $this->c->set(array(
                'register_guests'=>$register_guests,
                'user_session'=>$user_session
                ));

            // User account creation is enabled
            // Pre-fill user related inputs in forms with session data
            if(/*$register_guests && */!empty($user_session) && method_exists($this->c,'getPluginModel'))
            {
                $model = $this->c->getPluginModel();

                extract($user_session);

                $new_user['id'] = $user_id;

                $new_user['aid'] = 0;

                $new_user['gid'] = array(1);

                if(isset($this->c->Access)) {

                    $this->c->Access->gid = $new_user['gid'];

                    // cmsFramework::setSessionVar('gid',$new_user['gid'],'jreviews');

                    $this->c->_user = (object) $new_user;
                }

                switch($model->name)
                {
                    case 'Discussion':
                    case 'Media':

                        $user_id > 0 and $this->c->data[$model->name]['user_id'] = $user_id;

                        $name != '' and $this->c->data[$model->name]['name'] = $name;

                        $email != '' and $this->c->data[$model->name]['email'] = $email;

                        break;

                    case 'Listing':

                        $user_id > 0 and $this->c->data['Listing']['created_by'] = $user_id;

                        $name != '' and $this->c->data['name'] = $name;

                        $email != '' and $this->c->data['email'] = $email;

                        break;

                    case 'Review':

                        $user_id > 0 and $this->c->data[$model->name]['userid'] = $user_id;

                        $name != '' and $this->c->data[$model->name]['name'] = $name;

                        $email != '' and $this->c->data[$model->name]['email'] = $email;

                        break;
                }
            }
        }
    }

    /**
     * If guest user registration is enabled we need to generate the new account before the form is saved
     * to grab the user id. Otherwise we still save the name and email to the session
     * @param  object $model the model that is being saved
     * @param  array $data  the form data
     */
    function plgBeforeSave(&$model, $data)
    {
        $user_id_key = false;

        $user_registration_guest = Sanitize::getBool($this->c->Config,'user_registration_guest');

        if(!$this->c->Access->isGuest() || $this->ranBeforeSave || !$user_registration_guest) return $data;

        $this->ranBeforeSave = true; // Run once per request

        // Generate a new account if one not already created; or override the user info
        // user id, name, username and email for each model

        switch($model->name)
        {
            case 'Discussion':

                if(!Sanitize::getInt($data['Discussion'],'discussion_id'))  {

                    $user_id_key = 'user_id';
                }

            break;

            case 'Listing':

                if(!Sanitize::getInt($data['Listing'],'id')) {

                    $user_id_key = 'created_by';
                }

            break;

            case 'Media':

                if(!Sanitize::getInt($data['Media'],'media_id')) {

                    $user_id_key = 'user_id';
                }

            break;

            case 'Review':

                if(!Sanitize::getInt($data['Review'],'id')) {

                    $user_id_key = 'userid';
                }
            break;
        }

        if($user_id_key) {

            $data = $this->coreRegistration($model, $data, $user_id_key);
        }

        return $data;
    }

    // Record submission and associated session id and user id if available
    function plgAfterSave(&$model)
    {
        if(!$this->c->Access->isGuest() || $this->ranAfterSave) return;

        $this->ranAfterSave = true; // Run once per request

        $data = &$model->data;

        $name = $email = '';

        $username = Sanitize::getString($data,'username');

        $new_user = self::getUser();

        if($username == '') {

            $username = Sanitize::getString($new_user,'username');
        }

        // For guests, keep track of ids of newly submitted forms.
        // Used in media form to allow media submissions and assign them to the same user
        self::setGuestSubmissionsInSession($model);

        // Processing for disabled account creation or account creation enabled, but a username was not filled out
        if($username == '')
        {
            switch($model->name) {

                case 'Discussion':
                case 'Media':
                case 'Review':

                    $name = Sanitize::getString($data[$model->name],'name');

                    $email = Sanitize::getString($data[$model->name],'email');
                break;

                case 'Listing':

                    $name = Sanitize::getString($data,'name');

                    $email = Sanitize::getString($data,'email');
                break;
            }

            self::setUser(0, '', $name, $email);
        }
        else {

            $listing_id = $review_id = $discussion_id = $media_id = 0;

            $user_id = Sanitize::getInt($data,'user_id');

            if($user_id) {

                $name = Sanitize::getString($data,'name');

                $email = Sanitize::getString($data,'email');
            }
            else {

                $user_id = Sanitize::getString($new_user,'user_id');

                $name = Sanitize::getString($new_user,'name');

                $email = Sanitize::getString($new_user,'email');
            }

            switch($model->name)
            {
                case 'Discussion':
                    $discussion_id = Sanitize::getInt($data['Discussion'],'discussion_id');
                break;

                case 'Listing':
                    $listing_id = Sanitize::getInt($data['Listing'],'id');
                break;

                case 'Media':
                    $media_id = Sanitize::getInt($data['Media'],'media_id');
                break;

                case 'Review':
                    $review_id = Sanitize::getInt($data['Review'],'id');
                break;
            }

            S2App::import('Model','registration','jreviews');

            $Registration = ClassRegistry::getClass('RegistrationModel');

            $register_data = array(
                'model'=>$model->name,
                'Registration'=>array(
                    'session_id'=>session_id(),
                    'user_id'=>$user_id,
                    'name'=>$name,
                    'email'=>$email,
                    'listing_id'=>$listing_id,
                    'review_id'=>$review_id,
                    'discussion_id'=>$discussion_id,
                    'media_id'=>$media_id,
                    'session_time'=>time()
                ));

            $Registration->store($register_data);

            $user_id == 0 and self::setUser(0, '', $name, $email);
        }
    }

    /**
     * Stores listing/review submission data that can be used in the media form
     * to determine via access settings if the guest is allowed to submit media for his
     * own submission
     * @param Model class $model
     */
    function setGuestSubmissionsInSession(&$model) {

        $User = cmsFramework::getUser();

        // For guest users store newly submitted review ids for this user's session so they can be used for media submissions
        if(Sanitize::getBool($model->data,'isNew') && $User->id == 0) {

            switch($model->name) {

                case 'Review':

                    $id = Sanitize::getInt($model->data[$model->name],'id');

                    $session_ids = cmsFramework::getSessionVar('reviews','jreviews');

                    $session_ids[$id] = cmsFramework::getCustomToken($id);

                    cmsFramework::setSessionVar('reviews',$session_ids,'jreviews');

                    break;

                case 'Listing':

                    $id = Sanitize::getInt($model->data[$model->name],'id');

                    $session_ids = cmsFramework::getSessionVar('listings','jreviews');

                    $session_ids[$id] = cmsFramework::getCustomToken($id);

                    cmsFramework::setSessionVar('listings',$session_ids,'jreviews');

                    break;
            }
        }

    }

    /**
     * Creates a new user account if one hasn't already been created in this session
     * @param  [type] $model       [description]
     * @param  [type] $data        [description]
     * @param  [type] $user_id_key [description]
     * @return [type]              [description]
     */
    function coreRegistration(&$model, $data, $user_id_key)
    {
        // Check if user id already stored in session
        $session_key = 'user_id';

        $user_session = self::getUser(false);

        $name = $email = '';

        if(!empty($user_session) && Sanitize::getInt($user_session,'user_id') == 0) {

            $user_session = array();
        }

        switch($model->name)
        {
            case 'Discussion':
            case 'Media':
            case 'Review':

                $name = Sanitize::getString($data[$model->name],'name');

                $email = Sanitize::getString($data[$model->name],'email');

            break;

            case 'Listing':

                $name = Sanitize::getString($data,'name');

                $email = Sanitize::getString($data,'email');

            break;
        }

        # Create the new user account

        if(empty($user_session)) {

            $username = Sanitize::getString($data,'username');

            $valid_username = Sanitize::getBool($data,'valid_username');

            if($username && $valid_username && Sanitize::getInt($data[$model->name],$user_id_key) == 0)
            {
                $user = $this->c->User->findRow(array('conditions'=>array(
                    'username = ' .$this->c->Quote($username) . ' OR email = ' . $this->c->Quote($email)
                )));

                // If a user with this email or username exists, then we don't do anything
                // Adding submission under found user raises some concerns as there is no way to authenticate it's
                // not an impersonator

                if($user) {

                    return $data;
                }

                # Create the new user

                $user_data = array(
                    'username'=>$username,
                    'name'=>$name,
                    'email'=>$email,
                    'password'=>null
                );

                if($user_id = cmsFramework::registerUser($user_data))
                {
                    $data[$model->name][$user_id_key] = $data['user_id'] = $user_id;

                    $data['name'] = $name;

                    $data['email'] = $email;

                    // Store the new user id in the session to be able to re-use it in subsequent submissions
                    // No need to ask the user again for his name, email on other forms
                    $user_session = self::setUser($user_id, $username, $name, $email);

                    if($model->name == 'Listing') {

                        unset($data[$model->name]['created_by_alias']);

                    }
                }
            }
            else {

                return $data;
            }
        }

        # User account already created, so we override the user info in the posted data

        if($model->name == 'Listing') {

            unset($data[$model->name]['created_by_alias']);
        }

        // Override user id so the guest submissions use the new account id

        $data[$model->name][$user_id_key] = $data['user_id'] = $user_session['user_id'];

        // Add name and email from session to corresponding model name data array

        $data['name'] = $name = $user_session['name'];

        $data['email'] = $email = $user_session['email'];

        switch($model->name)
        {
            case 'Discussion':
            case 'Media':
            case 'Review':

                $data[$model->name]['name'] = $name;

                $data[$model->name]['email'] = $email;

            break;
        }

        return $data;
    }

    static function getUserId()
    {
        $user_id = 0;

        $User = cmsFramework::getUser();

        $Config = Configure::read('JreviewsSystem.Config');

        $register_guests = Sanitize::getBool($Config,'user_registration_guest');

        if(!$User->id && $register_guests) {

            $User = self::getUser(true);

            if($User) {

                $user_id = $User['user_id'];
            }
        }
        else {

            $user_id = $User->id;
        }

        return $user_id;
    }

    static function getUser($session_only = true)
    {
        // Check if user id already stored in session
        $user_session =  cmsFramework::getSessionVar('user','jreviews');

        if(!empty($user_session))
        {
            return $user_session;
        }
        elseif($session_only) {

            return false;
        }

        $session_id = session_id();

        S2App::import('Model','registration','jreviews');

        $Registration = ClassRegistry::getClass('RegistrationModel');

        $user = $Registration->findRow(array(
                'fields'=>array('Registration.user_id','Registration.name','Registration.email','User.username AS `Registration.username`'),
                'joins'=>array('LEFT JOIN #__users AS User ON User.id = Registration.user_id'),
                'conditions'=>array(
                    'Registration.session_id = ' . $Registration->Quote($session_id),
                    'Registration.user_id > 0'
                ),
                'limit'=>1
            ));

        if(!empty($user))
        {
            extract($user['Registration']);

            self::setUser($user_id, $username, $name, $email);

            return array(
                'user_id'=>$user_id,
                'username'=>$username,
                'name'=>$name,
                'email'=>$email);
        }

        return false;
    }

    static function setUser($user_id, $username, $name, $email)
    {
        $user = array(
            'user_id'=>$user_id,
            'username'=>$username,
            'name'=>$name,
            'email'=>$email,
            'session_time'=>time()
            );

        cmsFramework::setSessionVar('user',$user,'jreviews');

        return $user;
    }
}
