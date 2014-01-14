<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012  ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/* This is used for review feeds */
class FeedsController extends MyController {

	var $uses = array('user','menu','media','category','review','field','criteria');

	var $helpers = array('html','media','text','custom_fields');

	var $components = array('config','access','feeds','everywhere','media_storage');

	var $autoRender = false; //Output is returned

	var $autoLayout = false;

	var $encoding = 'utf-8';

	function beforeFilter()
    {
        $this->params['action'] = 'xml';

		# Call beforeFilter of MyController parent class
		parent::beforeFilter();

		# Make configuration available in models
		$this->Listing->Config = &$this->Config;
	}

	// Need to return object by reference for PHP4
	function &getEverywhereModel() {
		return $this->Review;
	}

	function reviews()
	{
        $access =  $this->Access->getAccessLevels();
        $feed_filename = S2_CACHE . 'views' . DS . 'jreviewsfeed_'.md5($access.$this->here).'.xml';
        $this->Feeds->useCached($feed_filename,'reviews');

		$extension = Sanitize::getString($this->params,'extension','com_content');
		$cat_id = Sanitize::getInt($this->params,'cat');
        $dir_id = Sanitize::getInt($this->params,'dir');
		$listing_id = Sanitize::getInt($this->params,'id');
		$this->encoding = cmsFramework::getCharset();
		$feedPage = null;

		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

		$this->limit = $this->Config->rss_limit;

		$rss = array(
			'title'=>$this->Config->rss_title,
			'link'=>WWW_ROOT,
			'description'=>$this->Config->rss_description,
			'image_url'=>WWW_ROOT . "images/stories/" . $this->Config->rss_image,
			'image_link'=>WWW_ROOT
		);

		$queryData = array(
			'conditions'=>array(
				'Review.published = 1',
				"Review.mode = '$extension'", //Everywhere
			),
			'fields'=>array(
				'Review.mode AS `Review.extension`'
			),
			'limit'=>$this->limit,
			'order'=>array('Review.created DESC')
		);

        if($extension == 'com_content')
        {
            $queryData['conditions'][]  = 'Listing.state = 1';
            $queryData['conditions'][]  = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )';
            $queryData['conditions'][]  = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )';

            # Shows only links users can access
            $cat_id > 0 and $cat_id = array_keys($this->Category->getChildren($cat_id));
            $access_id = $this->Access->getAccessLevels();
            $queryData['conditions'][]  = 'Listing.access IN ( ' . $access_id . ')';
            $queryData['conditions'][]  = 'Category.access IN ( ' . $access_id . ')';
        }

		if(!empty($cat_id) && $extension == 'com_content')
        { // Category feeds only supported for core content
            $queryData['conditions'][] = 'JreviewsCategory.id IN (' . $this->Quote($cat_id) . ')';
            $feedPage = 'category';
        }
        elseif($dir_id >0 && $extension == 'com_content'){
            $queryData['conditions'][] = 'JreviewsCategory.dirid= ' . $dir_id;
            $feedPage = 'directory';
        }
        elseif ($extension!='com_content')
        {
			unset($this->Review->joins['listings'],$this->Review->joins['jreviews_categories'],$this->Review->joins['listings']);
		    $feedPage = 'everywhere';
        }

		if($listing_id>0) {
			$queryData['conditions'][] = 'Review.pid = ' . $listing_id;
            $feedPage = 'listing';
		}
		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		$reviews = $this->Review->findAll($queryData);

		$this->set(array(
            'feedPage'=>$feedPage,
			'encoding'=>$this->encoding,
			'rss'=>$rss,
			'reviews'=>$reviews
		));

		return $this->Feeds->saveFeed($feed_filename,'reviews');
	}
}
