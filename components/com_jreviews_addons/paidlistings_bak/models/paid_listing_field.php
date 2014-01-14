<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidListingFieldModel extends MyModel  {

    var $name = 'PaidListingField';

    var $useTable = '#__jreviews_paid_listing_fields AS `PaidListingField`';

    var $primaryKey = 'PaidListingField.lising_id';

    var $realKey = 'lising_id';

    var $fields = array('PaidListingField.fields');

    var $order = array();

    function afterFind($results)
    {
         foreach($results AS $key=>$result)
         {
            $results[$key]['PaidListingField'] = array_merge($results[$key]['PaidListingField'],json_decode($results[$key]['PaidListingField']['fields'],true));
            unset($results[$key]['PaidListingField']['fields']);
         }
        return $results;
    }

    /**
    * Saves all fields to the paid listings field table and removes unpaid fields to be saved in jreviews_content table
    *
    * @param mixed $data
    */
    function save(&$data)
    {
        # Core fields
        $core_fields = array('email','ipaddress','listing_note');

        // Save all fields to paid_fields table
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $all_fields = $data['Field']['Listing'];

        $all_fields['element_id'] = $all_fields['contentid'];

        unset($all_fields['contentid']);

        $paidFieldRecordExists = $this->findCount(array('conditions'=>array('PaidListingField.listing_id = ' . $data['Listing']['id'])));

        $paidAction = $paidFieldRecordExists ? 'update' : 'insert';

        $data_all_fields = array('PaidListingField'=>array('listing_id'=>$data['Listing']['id'],'fields'=>json_encode($all_fields)));

        $this->$paidAction('#__jreviews_paid_listing_fields','PaidListingField',$data_all_fields,'listing_id');

        // Now remove unpaid fields from the jreviews_content table

        $paid_listing_array = $PaidOrder->completeOrderInfo($data['Listing']['id']);

        $paid_listing = array_shift($paid_listing_array); // Returns listing orders and paid fields info

        $data_paid_fields = !isset($paid_listing['Paid']) ? array() : array_flip($paid_listing['Paid']['fields']);

        // Makes unpaid fields empty
        foreach($data['Field']['Listing'] AS $field=>$value)
        {
            (!in_array($field,$core_fields) && !array_key_exists($field,$data_paid_fields)) and $field != 'contentid' and $data['Field']['Listing'][$field] = '';
        }
    }

    /**
    * Loads the paid_listing_field data instead of the jreviews_content one
    * For users editing a paid listing
    *
    * @param mixed $listing_id
    */
    function edit($listing_id)
    {
        $query = "
            SELECT
                PaidListingField.listing_id AS element_id, PaidListingField.fields
            FROM
                #__jreviews_paid_listing_fields AS PaidListingField
            WHERE
                PaidListingField.listing_id IN (" .cleanIntegerCommaList($listing_id). ")"
        ;

        $fieldValues = $this->query($query,'loadObjectList','element_id');

        if(empty($fieldValues)) return false;

        foreach($fieldValues AS $key=>$values) {

            $fields = json_decode($values->fields);

            $fieldValues[$key] = (object) array_merge((array)$values,(array)$fields);

            unset($fieldValues[$key]->fields,$fields);
        }

        return $fieldValues;
    }

    /**
    * Copies field values to jreviews_content
    *
    * @param mixed $listing_id
    * @param mixed $plan
    */
    function moveFieldsToListing($listing_id,$plan)
    {
        $field_values = $this->findRow(array('conditions'=>array('PaidListingField.listing_id = ' . (int)$listing_id)));

        if($field_values)
        {
            $field_values['PaidListingField'] = array_intersect_key($field_values['PaidListingField'],array_flip($plan['plan_array']['fields']));

            if(!empty($field_values['PaidListingField']))
            {

                $field_values['PaidListingField']['contentid'] = $listing_id;

                return $this->update('#__jreviews_content','PaidListingField',$field_values,'contentid');
            }
        }
        return true;
    }

    /**
    * Makes fields empty in jreviews_content
    *
    * @param mixed $listing_id
    * @param mixed $plan
    */
    function removeFieldsFromListing($listing_id,$plan)
    {
        $fields = array();
        $plan_fields = array_filter($plan['plan_array']['fields']);

        if(empty($plan_fields)) return true;

        $query = "
            SELECT
                name
            FROM
                #__jreviews_fields
            WHERE
                location = 'content'
                AND type NOT IN ('banner')
                AND name IN ( ". $this->Quote($plan_fields) . ")"
            ;

        $real_fields = $this->query($query,'loadColumn');

        $fields['PaidListingField'] = array_fill_keys(array_values($real_fields),'');

        $fields['PaidListingField']['contentid'] = $listing_id;

        return $this->update('#__jreviews_content','PaidListingField',$fields,'contentid');
    }
}