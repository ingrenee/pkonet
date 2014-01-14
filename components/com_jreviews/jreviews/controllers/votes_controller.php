<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-12 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class VotesController extends MyController {

	var $uses = array('menu','review','vote','media');

	var $components = array('config','access','everywhere','media_storage');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter() {
        parent::beforeFilter();
	}

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }

    function &getPluginModel() {
        return $this->Vote;
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());

        $duplicate = 0;

        $this->data['Vote']['user_id'] = $this->_user->id;

        $this->data['Vote']['review_id'] = Sanitize::getInt($this->data['Vote'],'review_id');

        $type = Sanitize::getString($this->data,'type');

	   # Exact vote check to prevent form tampering. User can cheat the js and enter any interger, thus increasing the count
        $this->data['Vote']['vote_yes'] = $type == 'yes' ? 1 : 0;

        $this->data['Vote']['vote_no'] = $type == 'no' ? 1 : 0;

        $this->data['Vote']['created'] = gmdate('Y-m-d H:i:s');

        $this->data['Vote']['ipaddress'] = $this->ipaddress;

        if(!$this->data['Vote']['review_id']){

            $response['str'][] = 'PROCESS_REQUEST_ERROR';

            return cmsFramework::jsonResponse($response);
        }

        // Find duplicates
        // It's a guest so we only care about checking the IP address if this feature is not disabled and
        // server is not localhost
        if(!$this->_user->id)
        {
            if(!$this->Config->vote_ipcheck_disable && $this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1')
            {
                // Do the ip address check everywhere except in localhost
               $duplicate = $this->Vote->findCount(array(
				   'conditions'=>array(
						'review_id = ' . $this->data['Vote']['review_id'],
						'ipaddress = ' . $this->Vote->Quote($this->ipaddress)
					),
					'session_cache'=>false
				));
            }
        }
        else
        // It's a registered user
        {
            $duplicate = $this->Vote->findCount(array(
				'conditions'=>array(
					'review_id = ' . $this->data['Vote']['review_id'],
					"(user_id = {$this->_user->id}" .
						(
							$this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1' && !$this->Config->vote_ipcheck_disable
						?
							" OR ipaddress = ". $this->Vote->Quote($this->ipaddress) .") "
						:
							')'
						)
					),
				'session_cache'=>false
			));
        }

        if($duplicate > 0){

            $response['str'][] = 'REVIEW_VOTE_DUPLICATE';

            return cmsFramework::jsonResponse($response);
        }

        if($this->Vote->store($this->data))
        {
            $response['success'] = true;

            # Facebook wall integration only for positive votes
            $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') && Sanitize::getBool($this->Config,'facebook_votes');

            if($facebook_integration) {

                $response['facebook'] = true;

                $response['token'] = cmsFramework::getCustomToken($this->data['Vote']['review_id']);
            }

            return cmsFramework::jsonResponse($response);
        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }
}
