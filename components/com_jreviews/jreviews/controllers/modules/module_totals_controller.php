<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ModuleTotalsController extends MyController {

    var $uses = array('menu','review');

    var $components = array('config');

    var $autoRender = false;

    var $autoLayout = false;

    var $layout = 'module';

    function beforeFilter() {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index()
    {
        $module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        $cache_file = S2CacheKey('modules_totals_'.$module_id,serialize($this->params['module']));

        $page = $this->cached($cache_file);

        if($page) {
            return $page;
        }

        // Initialize variables
        $extension = Sanitize::getString($this->params['module'],'extension');

        // Automagically load and initialize Everywhere Model
        S2App::import('Model','everywhere_'.$extension,'jreviews');
        $class_name = inflector::camelize('everywhere_'.$extension).'Model';

        $conditions_reviews = array('Review.published = 1');

        $extension == 'com_content' and $conditions_listings = array('Listing.state = 1');

        $extension!='' and $conditions_reviews[] = "Review.mode = " . $this->Quote($extension);

        if(class_exists($class_name)) {
            $this->Listing = new $class_name();
            $this->Listing->_user = $this->_user;
            $listings = $this->Listing->findCount(array('conditions'=>$conditions_listings),'DISTINCT Listing.'.$this->Listing->realKey);
            $reviews = $this->Review->findCount(array('conditions'=>$conditions_reviews),'DISTINCT Review.id');
        }

        # Send variables to view template
        $this->set(array(
                'listing_count'=>isset($listings) ? $listings : 0,
                'review_count'=>isset($reviews) ? $reviews : 0
        ));

        $page = $this->render('modules','totals');

        # Save cached version
        $this->cacheView('modules','totals',$cache_file, $page);

        return $page;
    }
}