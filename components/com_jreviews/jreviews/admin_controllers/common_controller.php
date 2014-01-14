<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommonController extends MyController {

	var $uses = array('review','field','media');

    var $helpers = array('text');

    var $components = array('config');

    var $autoLayout = false;

    var $autoRender = false;

    function feed()
    {
        if(function_exists('curl_init'))
        {
            if(!class_exists('SimplePie')) {
                S2App::import('Vendor','simplepie/simplepie.inc');
            }

            $feedUrl = "http://www.reviewsforjoomla.com/smf/index.php?board=7.0&type=rss2&action=.xml";

            $feed = new SimplePie();

            $feed->set_feed_url($feedUrl);

            $feed->enable_cache(true);

            $feed->set_cache_location(PATH_ROOT . 'cache');

            $feed->set_cache_duration(3600);

            $feed->init();

            $feed->handle_content_type();

            $items = $feed->get_items();

            $this->set('items',$items);

            $page = $this->render('about','feed');

        } else {

            $page = 'News feed requires curl';
        }

        echo $page;
    }

    function getStats() {

        # BEGIN STATS COLLECTION
        $stats = array();

        // Usage and setup stats
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories
            WHERE
                `option` = 'com_content'
        ";

        $this->_db->setQuery($query);

        $stats['categories'] = $this->_db->loadResult();

        $query = "
            SELECT
                count(*)
            FROM
                #__content
            WHERE
                state = 1
                AND catid IN (
                    SELECT id FROM #__jreviews_categories WHERE `option` = 'com_content'
                )
        ";

        $this->_db->setQuery($query);

        $stats['listings'] = $this->_db->loadResult();

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_comments AS review
            WHERE
                review.pid > 0 AND review.author = '0' AND review.published = 1"
            . (@!$this->EverywhereAddon ? "\n AND review.`mode`='com_content'" : '');

        $this->_db->setQuery($query);

        $stats['user-reviews'] = $this->_db->loadResult();

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_comments AS review
            WHERE
                review.pid > 0 AND review.author = '1' AND review.published = 1"
            . (@!$this->EverywhereAddon ? "\n AND review.`mode`='com_content'" : '');

        $this->_db->setQuery($query);

        $stats['editor-reviews'] = $this->_db->loadResult();

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_groups";

        $this->_db->setQuery($query);

        $stats['groups'] = $this->_db->loadResult();

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_fields";

        $this->_db->setQuery($query);

        $stats['fields'] = $this->_db->loadResult();

        // Media stats
        $query = "
            SELECT
                COUNT(*) AS count, media_type
             FROM
                #__jreviews_media
             WHERE
                approved = 1 AND published = 1
            GROUP BY
                media_type
        ";

        $this->_db->setQuery($query);

        $media_counts = $this->_db->loadAssocList();

        foreach($media_counts AS $media) {

            $stats[$media['media_type']] = (int)$media['count'];

        }

        return cmsFramework::jsonResponse($stats);
    }

    function getVersion()
    {
        $response = array('isNew'=>false, 'version'=>'');

        $session_var = cmsFramework::getSessionVar('new_version','jreviews');

        if(empty($session_var))
        {
            // Version checker
            $curl_handle = curl_init('http://www.reviewsforjoomla.com/updates_server/'.$this->majorVersion.'/files.php');

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1); // return instead of echo

            @curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);

            curl_setopt($curl_handle, CURLOPT_HEADER, 0);

            $data = curl_exec($curl_handle);

            curl_close($curl_handle);

            $current_versions = json_decode($data,true);

            $this->Config->updater_betas and isset($current_versions['jreviews']['beta']) and $current_versions['jreviews'] = array_merge($current_versions['jreviews'],$current_versions['jreviews']['beta']);

            $remoteVersion = $current_versions['components']['jreviews']['version'];

			$localVersion= strip_tags($this->Config->version);

			// Need to pad 3rd and 4th numbers to 3 digits
			$current_array = explode('.',$localVersion);

			$current_array[2] = str_pad($current_array[2],3,0,STR_PAD_LEFT);

			$current_array[3] = str_pad($current_array[3],3,0,STR_PAD_LEFT);

			$new_array = explode('.',$remoteVersion);

			$new_array[2] = str_pad($new_array[2],3,0,STR_PAD_LEFT);

			$new_array[3] = str_pad($new_array[3],3,0,STR_PAD_LEFT);

			if(self::paddedVersion($localVersion) < self::paddedVersion($remoteVersion)) {

                $response['isNew'] = true;
            }

            $response['version'] = $remoteVersion;

            cmsFramework::setSessionVar('new_version',$response,'jreviews');
        }
        else {

            $response = $session_var;
        }

        return cmsFramework::jsonResponse($response);
    }

	function toggleState()
    {
        $response = array('success'=>false,'str'=>array());

		$id = Sanitize::getInt($this->params,'id');

        $key = Sanitize::getString($this->params,'key');

        $field = Sanitize::getString($this->params,'state');

        $object_type = Sanitize::getString($this->params,'object_type');

        switch($object_type) {

            case 'field':
                $table = '#__jreviews_fields';
            break;
            case 'media':
                $table = '#__jreviews_media';
            break;
            default:
                $table = null;
            break;
        }

		if(!$id || !$table) return cmsFramework::jsonResponse($response);

		$this->_db->setQuery( "SELECT $field FROM `$table` WHERE $key = '$id'"	);

		$state = $this->_db->loadResult();

		$state = $state ? 0 : 1;

		$this->_db->setQuery( "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$id'" );

		if (!$this->_db->query()){

		    cmsFramework::jsonResponse($response);
        }

        // Clear cache
        clearCache('', 'views');
        clearCache('', '__data');

        $response['success'] = true;

        $response['state'] = $state;

        return cmsFramework::jsonResponse($response);
	}

    function loadView() {

        $folder = Sanitize::getString($this->params,'folder');

        $view = Sanitize::getString($this->params,'view');

        return $this->render($folder,$view);
    }

	function _rebuildReviewerRanks()
    {
        return $this->Review->rebuildRanksTable() ?
            JreviewsLocale::getPHP('REVIEWER_RANKS_REBUILT') :
			JreviewsLocale::getPHP('PROCESS_REQUEST_ERROR');
    }

    function _rebuildMediaCounts()
    {
        $listings = $this->Media->updateListingCounts();

        $reviews = $this->Media->updateReviewCounts();

        echo $listings && $reviews ?
            JreviewsLocale::getPHP('MEDIA_COUNTS_UPDATE') :
            JreviewsLocale::getPHP('PROCESS_REQUEST_ERROR');
    }

	function clearCacheRegistry()
    {
		clearCache('', 'views');
		clearCache('', '__data');
        clearCache('', 'menu');
        clearCache('', 'core');

        return JreviewsLocale::getPHP('CACHE_REGISTRY_CLEARED');
	}
}