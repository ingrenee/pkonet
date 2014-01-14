<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThemingComponent extends S2Component
{
    var $c;
    var $ignored_controllers = array(
        'categories'=>array(),
        'com_content'=>array(),
        'listings'=>array(
            'edit',
            '_loadForm'
        )
    );

	function startup(&$controller)
    {
        $this->c = & $controller;

        # Set Theme
        $controller->viewTheme = $controller->Config->template;

        $this->mobileDetect();

        Configure::write('System.isIE',s2isIE());

        $controller->viewImages = S2Paths::get('jreviews', 'S2_THEMES_URL') . Sanitize::getString($controller->Config,'fallback_theme') . _DS . 'theme_images' . _DS;

        # Dynamic theme setup
        if(
            (isset($this->ignored_controllers[$controller->name]) && empty($this->ignored_controllers[$controller->name]))
            ||
            (isset($this->ignored_controllers[$controller->name]) && in_array($controller->action,$this->ignored_controllers[$controller->name]))
        ) {
             return;
        }
        $this->setSuffix();
    }

    function mobileDetect()
    {
        $mobile_theme = Sanitize::getString($this->c->Config,'mobile_theme');

        if($mobile_theme == '') return;

        if(!Configure::read('System.mobileDetect'))
        {
			if(!class_exists('S2MobileDetect')) {

				S2App::import('Vendor','mobile_detect' . DS . 'Mobile_Detect');
			}

            Configure::write('System.mobileDetect',true);

			$detect = new S2MobileDetect();

            $isMobile = $detect->isMobile();

            $isTablet = $detect->isTablet();

            Configure::write('System.isiOS',$detect->isiOS());

            Configure::write('System.isAndroidOS',$detect->isAndroidOS());

			if ($isMobile && !$isTablet) { // Mobile, excluding tablets

                // Add data param to be able to generate different cache filenames for desktop and mobile
                // Same code run in dispatcher to pickup the correct cache file
                $this->c->params['isMobile'] = true;

				Configure::write('System.isMobile',true);

                Configure::write('System.isMobileOS',true);

            	$this->c->viewTheme = $mobile_theme;
            }
            elseif ($isMobile && $isTablet) // Mobile, including tablets
            {
                Configure::write('System.isMobile',false);

                Configure::write('System.isMobileOS',true);
            }
			else { // Not mobile

            	Configure::write('System.isMobile',false);
			}

        }
        elseif(Configure::read('System.isMobile')) {

            $this->c->viewTheme = $mobile_theme;
        }
    }

    /**
    * Sets the correct view suffix
    *
    * @param mixed $categories
    */
    public function setSuffix($options = array())
    {
        switch($this->c->action)
        {
            case 'search':
                $this->c->viewSuffix = Sanitize::getString($this->c->params,'tmpl_suffix',$this->c->Config->search_tmpl_suffix);
                // return;
            break;
            case 'detail': // View all reviews page
                $options['listing_id'] = Sanitize::getInt($this->c->params,'id');
            break;
        }

        # Find cat id
        if($listing_id = Sanitize::getInt($options,'listing_id'))
        {
            $query = "SELECT catid FROM #__content WHERE id = " . $listing_id;

            $this->c->_db->setQuery($query);

            $options['cat_id'] = $this->c->_db->loadResult();
        }

        # Get cat and parent cat info
        if($cat_id = Sanitize::getInt($options,'cat_id'))
        {
            S2App::import('Model','category','jreviews');

            $CategoryModel = ClassRegistry::getClass('CategoryModel');

            $options['categories'] = $this->c->cmsVersion == $CategoryModel->findParents($cat_id);
        }

        if(Sanitize::getVar($options,'categories'))
        {
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {
                $category['Category']['tmpl_suffix'] != '' and $this->c->viewSuffix = $category['Category']['tmpl_suffix'];
            }
        }

        # Module params, menu params and posted data override previous values
        if(Sanitize::getVar($this->c->params,'module')) {
            $this->c->viewSuffix = Sanitize::getString($this->c->params['module'],'tmpl_suffix');
        }

        if($suffix = Sanitize::getString($this->c->data,'tmpl_suffix',Sanitize::getString($this->c->params,'tmpl_suffix')))
        {
            $suffix != '' and $this->c->viewSuffix = $suffix;
        }

        if(isset($this->c->Menu))
        {
            # Nothing yet, so we load the menu params
            $menu_params = $this->c->Menu->getMenuParams(Sanitize::getInt($this->c->params,'Itemid'));
            Sanitize::getVar($menu_params,'tmpl_suffix') != '' and $this->c->viewSuffix = Sanitize::getVar($menu_params,'tmpl_suffix');
        }
    }

    /**
    * Sets the correct view layout
    *
    * @param mixed $categories
    */
    public function setLayout($options = array())
    {
        $default_listview = Sanitize::getInt($this->c->Config,'list_display_type',1);

        $override_listview = null;

        $search_listview = $this->c->action == 'search' ? $this->listTypeConversion($this->c->Config->search_display_type) : null;

        if(Sanitize::getVar($options,'categories'))
        {
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {
                $category['Category']['tmpl'] != '' and $default_listview = $category['Category']['tmpl'];
            }
        }

        # Overrides default view baed on menu and url parameter (listview)

        $menu_listview = Sanitize::getString($this->c->data,'listview');

        $url_listview = Sanitize::getString($this->c->params,'listview');

        if($url_listview != '') {

            $listview = $url_listview;
        }
        elseif($menu_listview != '') {

            $listview = $menu_listview;
        }
        elseif($search_listview != '') {

            $listview = $search_listview;
        }
        else {

            $listview = $default_listview;
        }

        # Global layout
        $this->c->listview = $this->c->data['listview'] = is_numeric($listview) ? $this->listTypeConversion($listview) : $listview;

        # Layout can be overriden for certain controller::actions
        if(method_exists($this,$this->c->action)) $this->{$this->c->action}();
    }

    /**
    * Uses the listings_favorite theme file if present
    */
    function favorites()
    {
        $Configure = S2App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_favorites.thtml'])
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_favorites.thtml'])
        ){
            $this->c->listview = 'favorites';
        }
    }

    /**
    * Uses the listings_favorite theme file if present
    */
    function mylistings()
    {
        $Configure = S2App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_mylistings.thtml'])
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_mylistings.thtml'])
        ){
            $this->c->listview = 'mylistings';
        }
    }

    function listTypeConversion($type)
    {
        switch($type) {

            case 0:
                return 'tableview';
                break;
            case 1:
                return 'blogview';
                break;
            case 2:
                return 'thumbview';
                break;
            case 3:
                return 'masonry';
                break;
            default:
                return 'blogview';
                break;
        }

    }
}