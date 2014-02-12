<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidHelper extends MyHelper
{
    var $submit_plan_id = null;

    function getDurationPeriods($key = null)
    {
        $array = array(
            'days'=>__t("Day(s)",true),
            'weeks'=>__t("Week(s)",true),
            'months'=>__t("Month(s)",true),
            'years'=>__t("Year(s)",true),
            'never'=>__t("Never Expires",true),
        );
        return !is_null($key) ? $array[$key] : $array;
    }

    static function getOrderStatus($status)
    {
        $array = array(
            'Incomplete'=>'<span class="jrStatusLabel jrOrange">'.__t("Incomplete",true).'</span>',
            'Pending'=>'<span class="jrStatusLabel jrOrange">'.__t("Pending",true).'</span>',
            'Processing'=>'<span class="jrStatusLabel jrBlue">'.__t("Processing",true).'</span>',
            'Complete'=>'<span class="jrStatusLabel jrGreen">'.__t("Complete",true).'</span>',
            'Cancelled'=>'<span class="jrStatusLabel jrBrown">'.__t("Cancelled",true).'</span>',
            'Fraud'=>'<span class="jrStatusLabel jrRed">'.__t("Fraud",true).'</span>',
            'Failed'=>'<span class="jrStatusLabel jrPurple">'.__t("Failed",true).'</span>'
            );

        return $array[$status];
    }

    static function getPaymentTypes($key = null)
    {
        $array = array(
            '0'=>__t("One Time Payment",true),
            '1'=>__t("Subscription",true),
            '2'=>__t("Free or Trial",true)
        );
        return !is_null($key) ? $array[$key] : $array;
    }

    static function getPlanTypes($key = null)
    {
        $array = array(
            '0'=>__t("Base",true),
            '1'=>__t("Upgrade",true)
        );
        return !is_null($key) ? $array[$key] : $array;
    }

    function getVar($name,$listing)
    {
        if(isset($listing['Paid']))
        {
            return Sanitize::getString($listing['Paid']['custom_vars'],$name);
        }
        return false;
    }
    function setSubmitPlanId($plan_id)
    {
        $this->submit_plan_id = $plan_id;
    }
    function planChecked($plan_id,$default)
    {
        if((!$this->submit_plan_id && $default))
        {
            return true;
        }
        elseif($this->submit_plan_id && $this->submit_plan_id == $plan_id)
        {
            return true;
        }
        return false;
    }

    function canOrder(&$listing)
    {
        $User = cmsFramework::getUser();
        return $listing['Listing']['user_id'] == $User->id && isset($listing['PaidPlanCategory']) && !empty($listing['PaidPlanCategory']['cat_id']);
    }

    function displayAddMediaLink($order, $listing) {

        $owner_id = Sanitize::getInt($listing['Listing'],'created_by');

        $overrides = Sanitize::getVar($listing['ListingType'],'config');

        $listing_id = Sanitize::getInt($listing['Listing'],'listing_id');

        $canAddListingMedia = $this->Access->canAddAnyListingMedia($owner_id, $overrides, $listing_id);

        // Base plan
        if($order['PaidOrder']['plan_type'] == 0 && $canAddListingMedia) {

            extract($listing['Paid']);

            if(
                ($photo == '' || $photo > 0)
                ||
                ($video == '' || $video > 0)
                ||
                ($attachment == '' || $attachment > 0)
                ||
                ($audio == '' || $audio > 0)
                )
            {
                return true;
            }
        }

        // Upgrade plan
        if($order['PaidOrder']['plan_type'] == 1 && $canAddListingMedia) {

            extract($listing['Listing']);

            if(
                ($photo_count == '' || ($photo_count > 0 && $photo_count_owner < $photo_count))
                ||
                ($video_count == '' || ($video_count > 0 && $video_count_owner < $video_count))
                ||
                ($attachment_count == '' || ($attachment_count > 0 && $attachment_count_owner < $attachment_count))
                ||
                ($audio_count == '' || ($audio_count > 0 && $audio_count_owner < $audio_count))
                )
            {

                return true;
            }
        }

        return false;
    }
}