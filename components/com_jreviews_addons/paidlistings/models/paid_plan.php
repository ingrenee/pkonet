<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model','paid_plan_category','jreviews');

class PaidPlanModel extends MyModel  {

    var $name = 'PaidPlan';

    var $useTable = '#__jreviews_paid_plans AS `PaidPlan`';

    var $primaryKey = 'PaidPlan.plan_id';

    var $realKey = 'plan_id';

    var $fields = array(
        'PaidPlan.plan_id AS `PaidPlan.plan_id`',
        'PaidPlan.plan_name AS `PaidPlan.plan_name`',
        'PaidPlan.plan_price AS `PaidPlan.plan_price`',
        'PaidPlan.plan_type AS `PaidPlan.plan_type`',
        'PaidPlan.plan_upgrade_exclusive AS `PaidPlan.plan_upgrade_exclusive`',
        'PaidPlan.payment_type AS `PaidPlan.payment_type`',
        'PaidPlan.plan_featured AS `PaidPlan.plan_featured`',
        'PaidPlan.plan_array AS `PaidPlan.plan_array`',
        'PaidPlan.plan_default AS `PaidPlan.plan_default`',
        'PaidPlan.plan_state AS `PaidPlan.plan_state`',
        'PaidPlan.plan_updated AS `PaidPlan.plan_updated`',
        'PaidPlan.photo AS `PaidPlan.photo`',
        'PaidPlan.video AS `PaidPlan.video`',
        'PaidPlan.attachment AS `PaidPlan.attachment`',
        'PaidPlan.audio AS `PaidPlan.audio`'
    );

    var $planTypes = array();

    function beforeSave(&$data)
    {
        isset($data['cat_ids']) and isset($data['field_names']) and $data['PaidPlan']['plan_array']['fields'] = explode(",",$data['field_names']);

        if(isset($data['PaidPlan']['plan_array']['custom_vars']))
        {
            $custom_vars = array();

            $custom_vars_array = array_filter(explode("\n",trim($data['PaidPlan']['plan_array']['custom_vars'])));

            foreach($custom_vars_array AS $var_string)
            {
                $var = explode("|",$var_string);

                $custom_vars[Sanitize::getString($var,'0')] = Sanitize::getString($var,'1');
            }

            $data['PaidPlan']['plan_array']['custom_vars'] = $custom_vars;
        }

        if(isset($data['PaidPlan']['plan_array']))
        {
            $data['PaidPlan']['plan_array'] = json_encode($data['PaidPlan']['plan_array']);
        }
    }

    /**
    * Returns category plans - used in new listing submissions
    *
    * @param int $cat_id
    * @return mixed
    */
    function getCatPlans($cat_id)
    {
        $data = array();

        $data[]['Category']['cat_id'] = $cat_id;

        $plan_info = $this->completePlanInfo($data);

        $plans = array_shift($plan_info);

        return !empty($plans['PaidPlan']) ? $plans['PaidPlan'] : array();
    }

    /**
    * Returns array of plan_id and plan_name used to build a select list
    *
    */
    function getPlanList()
    {
        $query = "
            SELECT
                plan_id AS value, plan_name AS text
            FROM
                #__jreviews_paid_plans
            ORDER BY
                payment_type ASC,
                plan_price ASC
        ";

        $rows = $this->query($query,'loadAssocList');

        return $rows;
    }

    /**
    * Checks if an enabled default plan already exists in the passed categories
    *
    * @param mixed $cat_ids
    */
    function validateDefaultPlan($cat_ids, $plan_id = null, $plan_type = null)
    {
        $res = array();

        is_string($cat_ids) and $cat_ids = explode(",",$cat_ids);

        $query = "
            SELECT
                PaidPlanCategory.cat_id
            FROM
                #__jreviews_paid_plans AS PaidPlan
            LEFT JOIN
                #__jreviews_paid_plans_categories AS PaidPlanCategory ON PaidPlan.plan_id = PaidPlanCategory.plan_id
            WHERE
                PaidPlan.plan_default = 1"
            . ($plan_id ? " AND PaidPlan.plan_id != " . $plan_id : '')
            . ($plan_type ? " AND PaidPlan.plan_type != " . $plan_type : '')
        ;

        if($default_cat_ids = $this->query($query,'loadColumn'))
        {
            $res = array_intersect($cat_ids,$default_cat_ids);
        }

        return empty($res);
    }

    /**
    * Completes listing array with pricing plans available for each listing
    *
    * @param mixed $results
    */
    function completePlanInfo($results, $plan_type = null)
    {
        $user_id = UserAccountComponent::getUserId();

        $cat_ids = $base_plans_tmp = $upgrade_plans_tmp = array();

        foreach($results AS $result)
        {
            if(Sanitize::getInt($result['Category'],'cat_id') > 0)
            {
                $cat_ids[$result['Category']['cat_id']] = $result['Category']['cat_id'];
            }
        }

        if(empty($cat_ids)) return $results;

        $this->cat_ids = $cat_ids; // Make it available in the afterFind method

        $conditions =array(
            '`PaidPlanCategory`.cat_id IN (' . implode(',',$cat_ids).')',
            '`PaidPlan`.plan_state = 1'
        );

        !is_null($plan_type) and $conditions[] = '`PaidPlan`.plan_type = ' . (int)$plan_type;

        $plans = $this->findAll(array(
            'conditions'=>$conditions,
            'joins'=>array(
                'RIGHT JOIN
                    #__jreviews_paid_plans_categories AS PaidPlanCategory ON PaidPlanCategory.plan_id = PaidPlan.plan_id'
            ),
            'order'=>array(
                '`PaidPlan`.plan_price ASC'
            )
        ));

        S2App::import("Model","paid_order");

        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        foreach($results AS $key=>$result)
        {
            $trial_limit = false;

            foreach($plans AS $plan_id=>$plan)
            {
                $used_trials = 0;

                if(isset($plan['PaidPlanCategory']) && in_array($result['Category']['cat_id'],$plan['PaidPlanCategory']['cat_id']))
                {
                    // Check for free/trial plans if it's a logged in user
                    if($user_id > 0 && $plan['PaidPlan']['payment_type']==2)
                    {
                        $used_trials = $PaidOrder->findCount(array(
                            'conditions'=>array(
                                "PaidOrder.user_id = " . $user_id,
                                "PaidOrder.plan_id = " . $plan_id,
                                "PaidOrder.order_status = 'Complete'"
                            ),
							'session_cache'=>false
                        ));
                        $plan['PaidPlan']['used_trials'] = $used_trials;
                    }

                    // Trial plans that have exceed their trial limit are not shown as payment option
                    if($plan['PaidPlan']['plan_array']['trial_limit'] > 0
                        && $used_trials >= $plan['PaidPlan']['plan_array']['trial_limit']
                    ) {
                        // Don't add the plan
                        $trial_limit = true;
                    } else {
                        # Build a temporary array of base plans used further below to pre-set the default plan if only one plan available
                        if($plan['PaidPlan']['plan_type'] == 0) {
                            $base_plans_tmp[] =  array('results_key'=>$key,'plan_id'=>$plan_id,'payment_type'=>$plan['PaidPlan']['payment_type']);
                        }
                        if($plan['PaidPlan']['plan_type'] == 1) {
                            $upgrade_plans_tmp[] = array('results_key'=>$key,'plan_id'=>$plan_id,'payment_type'=>$plan['PaidPlan']['payment_type']);
                        }
                        $results[$key]['PaidPlan']['PlanType'.$plan['PaidPlan']['plan_type']]['PaymentType'.$plan['PaidPlan']['payment_type']][$plan_id] = $plan['PaidPlan'];
                    }
                }

                if($used_trials && $trial_limit && !isset($results[$key]['PaidPlan']['PlanType0'])) {
                    $results[$key]['PaidPlan']['used_trials'] = $used_trials;
                }
            }
        }

        if(count($base_plans_tmp) == 1) {

            $results_key = $base_plans_tmp[0]['results_key'];

            $plan_id = $base_plans_tmp[0]['plan_id'];

            $payment_type = $base_plans_tmp[0]['payment_type'];

            $results[$results_key]['PaidPlan']['PlanType0']['PaymentType'.$payment_type][$plan_id]['plan_default'] = 1;
        }

        if(count($upgrade_plans_tmp) == 1) {

            $results_key = $upgrade_plans_tmp[0]['results_key'];

            $plan_id = $upgrade_plans_tmp[0]['plan_id'];

            $payment_type = $upgrade_plans_tmp[0]['payment_type'];

            $results[$results_key]['PaidPlan']['PlanType1']['PaymentType'.$payment_type][$plan_id]['plan_default'] = 1;
        }

        return $results;
    }

    /**
    * Returns list of valid plans for a particular listing and plan_type
    */
    function getValidPlans($listing_id,$plan_type,$renewal=false)
    {
        $PaidOrder = ClassRegistry::getClass('PaidOrderModel');

        $query = "
            SELECT
                Listing.catid
            FROM
                #__content AS Listing
            WHERE
                Listing.id = " . (int) $listing_id
            ;

        $cat_id = $this->query($query,'loadResult');

        $plans = $this->completePlanInfo(array(0=>array('Category'=>array('cat_id'=>$cat_id))),$plan_type);

        $plans = array_shift($plans);

        # For Upgrade Plans check base plan first to remove expiring upgrades for expiring base plans
        if($plan_type == 1)
        {
            $active_plan_ids = $PaidOrder->getActiveOrderPlanIdsByListing($listing_id);

            $base_plan_never_expires = $PaidOrder->findRow(array('conditions'=>array(
                "PaidOrder.plan_type = 0",  // New listing plan
                "PaidOrder.order_active = 1",
                "PaidOrder.listing_id = " . $listing_id,
                "PaidOrder.order_never_expires = 1" // Never expires
            )));

            # Unset upgrade plans with expiration for non-expiring base plans
            # Unset upgrade plans that are currently active
            unset($plans['PaidPlan']['Category']);

            if(isset($plans['PaidPlan']) && !empty($plans['PaidPlan']))
            {
                foreach($plans['PaidPlan'] AS $plan_type=>$payment_types)
                {
                    if(!is_array($payment_types) || empty($payment_types)) continue;

                    foreach($payment_types AS $payment_type=>$plan)
                    {
                        if(empty($plan)) continue;

                        foreach($plan AS $plan_id=>$row)
                        {
                            if((!$base_plan_never_expires && $row['plan_array']['duration_period'] != 'never')
                                 ||
                                (in_array($plan_id,$active_plan_ids) && !$renewal)
                            ) {
                                unset($plans['PaidPlan'][$plan_type][$payment_type][$plan_id]);
                            }
                        }
                    }
                }
            }
            else {
                $plans['PaidPlan'] = array();
            }

            if(empty($plans['PaidPlan']['PlanType0']['PaymentType0'])) unset($plans['PaidPlan']['PlanType0']['PaymentType0']);
            if(empty($plans['PaidPlan']['PlanType0']['PaymentType1'])) unset($plans['PaidPlan']['PlanType0']['PaymentType1']);
            if(empty($plans['PaidPlan']['PlanType0']['PaymentType2'])) unset($plans['PaidPlan']['PlanType0']['PaymentType2']);
            if(empty($plans['PaidPlan']['PlanType1']['PaymentType0'])) unset($plans['PaidPlan']['PlanType1']['PaymentType0']);
            if(empty($plans['PaidPlan']['PlanType1']['PaymentType1'])) unset($plans['PaidPlan']['PlanType1']['PaymentType1']);
            if(empty($plans['PaidPlan']['PlanType1']['PaymentType2'])) unset($plans['PaidPlan']['PlanType1']['PaymentType2']);
        }
        return $plans;
    }

    /**
    * Prevent fraud by validating whether the plan submitted for a listing is a valid plan for that listing
    *
    * @param mixed $listing
    * @param mixed $curr_plan
    * @return bool
    */
    function validatePlan($listing,$curr_plan,$renewal = false)
    {
        $plan_ids = array();

        $plans = $this->getValidPlans($listing['Listing']['listing_id'],$curr_plan['PaidPlan']['plan_type'],$renewal);

        unset($plans['Category']);

        if(empty($plans)) return false;

        foreach($plans['PaidPlan'] AS $plan_types){

            if(!is_array($plan_types)) continue;

            foreach($plan_types AS $payment_types){

                foreach($payment_types AS $plan_id=>$plan)
                {
                    $plan_ids[] = $plan_id;
                }
            }
        }
        return (in_array($curr_plan['PaidPlan']['plan_id'],$plan_ids));
    }

    function afterSave($created)
    {
        if($created)
        {
            $PaidPlanCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

            $plan_id = Sanitize::getInt($this->data['PaidPlan'],'plan_id');

            $plan_id and $PaidPlanCategory->delete('plan_id',$plan_id);

            if(isset($this->data['cat_ids']) and $this->data['cat_ids'] = explode(",",$this->data['cat_ids']))
            {
                foreach($this->data['cat_ids'] AS $cat_id)
                {

                    $data['PaidPlanCategory']['plan_id'] = $plan_id;

                    $data['PaidPlanCategory']['cat_id'] = $cat_id;

                    $PaidPlanCategory->insert( '#__jreviews_paid_plans_categories', 'PaidPlanCategory', $data);
                }
            }
        }
    }

    function afterFind($results)
    {
        if(empty($results)) return $results;

        S2App::import('Helper','paid','jreviews');

        # Complete the plans with the categories to which they were assigned
        $PaidPlanCategory = ClassRegistry::getClass('PaidPlanCategoryModel');

        if(isset($results['plan_id']))
        {
            $results['PaidPlan']['plan_array'] = json_decode($result['PaidPlan']['plan_array'],true);

            $results = array_merge($results, $PaidPlanCategory->findAll(array('condition'=>array('PaidPlanCategory.plan_id = ' . (int) $results['PaidPlan']['plan_id']))));

            $results['PaidPlan']['plan_array']['custom_vars'] = $this->customVarsToText(Sanitize::getVar($results['PaidPlan']['plan_array'],'custom_vars'));

            $results['PaidPlan']['PlanType'] = PaidHelper::getPlanTypes($results['PaidPlan']['plan_type']);

            $results['PaidPlan']['PaymentType'] = PaidHelper::getPaymentTypes($results['PaidPlan']['payment_type']);
        }
        else {

            foreach($results AS $key=>$result)
            {
                if(isset($result['PaidPlan']['plan_array']))
                {
                    $results[$key]['PaidPlan']['plan_array'] = json_decode($result['PaidPlan']['plan_array'],true);
                }

                if(isset($this->cat_ids) && count($this->cat_ids) == 1) {
                    $results[$key]['PaidPlanCategory']['cat_id'][] = current($this->cat_ids);
                }

                $results[$key]['PaidPlan']['PlanType'] = PaidHelper::getPlanTypes($result['PaidPlan']['plan_type']);

                $results[$key]['PaidPlan']['PaymentType'] = PaidHelper::getPaymentTypes($result['PaidPlan']['payment_type']);
            }

            if(!isset($this->cat_ids) || count($this->cat_ids) > 1)
            {
                $plan_ids = array_keys($results);

                $PlanCats = $PaidPlanCategory->findAll(array('conditions'=>array('PaidPlanCategory.plan_id IN (' . $this->Quote($plan_ids) . ')')));

                foreach($results AS $key=>$result) {

                    $results[$key]['PaidPlanCategory']['cat_id'] = $PlanCats['PaidPlanIdCats'][$result['PaidPlan']['plan_id']];
                }
            }
        }

        return $results;
    }
}
