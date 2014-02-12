<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CronFunctionsComponent extends S2Component {

    var $plugin_order = 1;

    var $name = 'cron_functions';

    var $published = true;

    var $c;

    function startup(&$controller)
    {
        if(!isset($controller->Config)
			|| (!$controller->Config->cron_site_visits && $controller->name != 'cron')
			|| $controller->ajaxRequest
			|| Sanitize::getString($controller->params,'action') == 'xml'
			|| Sanitize::getString($controller,'action' == 'com_content_blog'))

				return;

		if(Configure::read('JreviewsSystem.cron')) {
			return;
		}

		Configure::write('JreviewsSystem.cron',1);

        $this->c = &$controller;

        if(!cmsFramework::isAdmin()) {

        	$this->cacheCleaner();

        	$this->rebuildRankTable();
        }

		$this->rebuildMediaLikesTable();
    }

    /**
    * Cleans the JReviews cache
    *
    */
    function cacheCleaner()
    {
		$cron_period = Sanitize::getVar($this->c->Config,'cache_cleanup') * 3600;

		$last_run = Sanitize::getInt($this->c->Config,'last_cache_clean');

		$now = time();

		if($last_run + $cron_period <= $now)
		{
			$this->c->Config->store(array('last_cache_clean'=>$now));

			clearCache('', 'views');

			clearCache('', 'menu');

			clearCache('', '__data');

			// Removes session related rows for guest submissions that used the account create feature
			S2App::import('Model','registration','jreviews');

			$Registration = ClassRegistry::getClass('RegistrationModel');

			$Registration->clearTable(3600);
		}
    }

    /**
    * Rebuilds the reviewer rank table
    *
    */
    function rebuildRankTable()
    {
		$cron_period = Sanitize::getVar($this->c->Config,'ranks_rebuild_interval') * 3600;

		$last_run = Sanitize::getInt($this->c->Config,'ranks_rebuild_last');

		$now = time();

		if($last_run + $cron_period <= $now)
		{
			$this->c->Config->store(array('ranks_rebuild_last' => $now));

			S2App::import('Model','review','jreviews');

			$Review = ClassRegistry::getClass('ReviewModel');

			$Review->rebuildRanksTable();
		}
    }

	function rebuildMediaLikesTable()
	{
		$cron_period = Sanitize::getVar($this->c->Config,'media_likes_rebuild_interval') * 3600;

		$last_run = Sanitize::getInt($this->c->Config,'media_likes_rebuild_last');

		$now = time();

		if($last_run + $cron_period <= $now)
		{
			$this->c->Config->store(array('media_likes_rebuild_last' => $now));

			S2App::import('Model','media_like','jreviews');

			$MediaLike = ClassRegistry::getClass('MediaLikeModel');

			$MediaLike->updateCount();
		}
	}
}