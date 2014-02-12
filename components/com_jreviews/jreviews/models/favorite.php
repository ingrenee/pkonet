<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FavoriteModel extends MyModel  {

    var $name = 'Favorite';

	var $useTable = '#__jreviews_favorites';

	// Adds listing to user's favorite list
    function add($content_id, $user_id)
    {
		$query = "INSERT IGNORE INTO {$this->useTable} (content_id,user_id) VALUES ($content_id,$user_id)";

		$this->_db->setQuery($query);

		if($this->_db->query())
        {
            clearCache('com_content');

            $listing_id = $this->_db->insertid();

            // Trigger plugin
            $this->plgAfterSave();

            return $listing_id;
        }

        return false;
	}

	function remove($content_id, $user_id)
	{
		$query = "DELETE FROM {$this->useTable} WHERE content_id = $content_id AND user_id = $user_id";

        $this->_db->setQuery($query);

		if($this->_db->query())
        {
            clearCache('com_content');

            // Trigger plugin
            $this->plgAfterSave();

            return true;
        };

		return false;

		return $result;

	}

	function getCount($content_id, $user_id = null) {

		$query = "SELECT count(*) FROM {$this->useTable}"
		. "\n WHERE content_id = $content_id"
		. ($user_id ? "\n AND user_id = $user_id" : '')
		;

		$this->_db->setQuery($query);

		$count = $this->_db->loadResult();

		return $count;

	}

    // Completes listing information for current user
	function addFavorite($results)
    {
		$listing_ids = array_keys($results);

		if(!isset($this->Config)) {
			S2App::import('Component','config','jreviews');
			$this->Config = ClassRegistry::getClass('ConfigComponent');
		}

		if($this->Config->favorites_enable)
		{
			# Get favoured count
			$query = "
                SELECT
                    content_id AS listing_id, count(*) AS favored FROM #__jreviews_favorites AS Favorite
			    WHERE
                    Favorite.content_id IN (" . implode(',',$listing_ids) . ")
			    GROUP BY
                    listing_id
            ";

			$this->_db->setQuery($query);

			$favored = $this->_db->loadAssocList('listing_id');

			# Check if in user's favorites list
			$User = cmsFramework::getUser();

            if ($User->id)
            {
				$query = "
                    SELECT
                        Favorite.user_id, Favorite.content_id AS listing_id
				    FROM
                        #__jreviews_favorites AS Favorite
				    WHERE
                        Favorite.content_id IN (". implode(',',$listing_ids) . ")
				        AND Favorite.user_id = " . $User->id
                    ;

				$this->_db->setQuery($query);

				$my_favorite = $this->_db->loadAssocList('listing_id');
			}

			foreach($results AS $key=>$result) {

				if(isset($favored[$result['Listing']['listing_id']]['favored'])) {
					$results[$key]['Favorite']['favored'] = $favored[$result['Listing']['listing_id']]['favored'];
				} else {
					$results[$key]['Favorite']['favored'] = 0;
				}

				if(isset($my_favorite[$result['Listing']['listing_id']]['user_id'])) {
					$results[$key]['Favorite']['my_favorite'] = 1;
				} else {
					$results[$key]['Favorite']['my_favorite'] = 0;
				}

			}
		}

		return $results;
	}

}
