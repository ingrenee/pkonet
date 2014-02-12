<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ClaimModel extends MyModel {

	var $name = 'Claim';

	var $useTable = '#__jreviews_claims AS Claim';

	var $primaryKey = 'Claim.claim_id';

	var $realKey = 'claim_id';

    var $fields = array('*');

    function afterSave($status)
    {
        if($status && $this->data['Claim']['approved'] == 1)
        {
            // Change listing owner if claim is approved

            S2App::import('Model',array('everywhere_com_content',/*'jreviews_content'*/),'jreviews');

            $Listing = new EverywhereComContentModel();

            // $JreviewsContent = new JreviewsContentModel();

            $listing_id = Sanitize::getInt($this->data['Claim'],'listing_id');

            $user_id = Sanitize::getInt($this->data['Claim'],'user_id');

            if(!isset($this->data['Listing'])) {

                $this->data['Listing']['id'] = $listing_id;

                $this->data['Listing']['created_by'] = $user_id;
            }

            isset($this->data['Listing']) and $Listing->store($this->data);

            // Ensure only one claim per listing is approved at the same time

            $claim_id = Sanitize::getInt($this->data['Claim'],'claim_id');

            $query = "
                UPDATE
                    #__jreviews_claims
                SET
                    approved = 0
                WHERE
                    listing_id = " . $listing_id . "
                    AND
                    claim_id <> " . $claim_id
            ;

            $this->query($query);
        }
    }
}
