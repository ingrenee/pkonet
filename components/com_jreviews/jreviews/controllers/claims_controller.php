<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ClaimsController extends MyController {

    var $uses = array('user','menu','claim','criteria'/*for config overrides*/);

    var $helpers = array('html','form','routes');

    var $components = array('config','everywhere','notifications');

    function beforeFilter() {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->Claim;
    }

    function create()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        if($listing_id) {

            $this->set('listing_id',$listing_id);

            return $this->render('claims','create');
        }

        return s2Messages::accessDenied();
    }

    function _save()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $this->components = array('security');

        $this->__initComponents();

        $listing_id = Sanitize::getInt($this->data['Claim'],'listing_id');

        $response = array('success'=>false,'str'=>array());

        # Validate form token
        if($this->invalidToken) {

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

		$overrides = $this->Criteria->getListingTypeOverridesByListingId($listing_id);

		if(!$listing_id || !$this->Config->getOverride('claims_enable',$overrides) || !$this->_user->id) {

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
 		}

		$this->data['Claim']['claim_text'] = Sanitize::getString($this->data['Claim'],'claim_text');

		if ($this->data['Claim']['claim_text'] != '') {

			// Check if this user already has a claim for this listing to update it
			$claim_id = $this->Claim->findOne(array(
				'fields'=>array('Claim.claim_id AS `Claim.claim_id`'),
				'conditions'=>array(
					'Claim.user_id = ' . (int) $this->_user->id,
					'Claim.listing_id = ' . $listing_id,
					'Claim.approved <= 0'
					)
			));

			if($claim_id > 0) {
				$this->data['Claim']['claim_id'] = $claim_id;
			}

			$this->data['Claim']['user_id'] = $this->_user->id;

            $this->data['Claim']['created'] = date('Y-m-d H:i:s');

            $this->data['Claim']['approved'] = 0;

			if($this->Claim->store($this->data)) {

                $response['success'] = true;

                return cmsFramework::jsonResponse($response);
			}

		}
        else {

			# Validation failed
			return cmsFramework::jsonResponse($response);
		}

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }
}
