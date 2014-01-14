<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidPlanCategoryModel extends MyModel  {

    var $name = 'PaidPlanCategory';

    var $useTable = '#__jreviews_paid_plans_categories AS `PaidPlanCategory`';

    var $primaryKey = 'PaidPlanCategory.plan_id';

    var $realKey = 'plan_id';

    var $fields = array('PaidPlanCategory.*');

    function isInPaidCategoryByListingId($listing_id) {
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_paid_plans_categories AS Category
            LEFT JOIN
                #__jreviews_paid_plans AS Plan ON Category.plan_id = Plan.plan_id
            WHERE
                cat_id = (
                    SELECT catid FROM #__content WHERE id = " . $listing_id . "
                )
                AND
                plan_state = 1
            ";

        $plan_cats = $this->query($query,'loadResult');

        return !empty($plan_cats);
    }

    function getPaidCatIdsArray($ancestors = false)
    {
        # Check for cached version
        $cache_file = s2CacheKey('jreviews_paid_cats',(int)$ancestors);

        $paid_cat_ids = S2Cache::read($cache_file,'_s2framework_core_');

        if(!$paid_cat_ids) {

            $query = "
                SELECT
                    DISTINCT PaidPlanCategory.cat_id
                FROM
                    #__jreviews_paid_plans_categories AS PaidPlanCategory
                INNER JOIN
                    #__jreviews_paid_plans AS PaidPlan ON PaidPlan.plan_id = PaidPlanCategory.plan_id
                WHERE
                    PaidPlan.plan_state = 1
            ";

            $paid_cat_ids = $this->query($query,'loadColumn');

            $paid_cat_ids_comma = implode(',',$paid_cat_ids);

            if($ancestors) {

                $query = "
                    SELECT
                        ParentCategory.*
                    FROM
                        #__categories AS Category, #__categories AS ParentCategory
                    WHERE
                        Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                        AND Category.id IN (".$paid_cat_ids_comma.")
                        AND ParentCategory.id NOT IN (".$paid_cat_ids_comma.")
                        AND ParentCategory.parent_id > 0
                    ORDER BY
                        Category.lft";

                $paid_cat_ids_parents = $this->query($query,'loadColumn');

                $paid_cat_ids = array_unique(array_merge($paid_cat_ids,$paid_cat_ids_parents));
            }

            S2Cache::write($cache_file, array('cat_ids'=>$paid_cat_ids),'_s2framework_core_');
        }
        else {

            $paid_cat_ids = $paid_cat_ids['cat_ids'];
        }

        return $paid_cat_ids;
    }

    function isInPaidCategory($cat_id)
    {
        $paid_cat_ids = $this->getPaidCatIdsArray();

        return in_array($cat_id, $paid_cat_ids);
    }

    function afterFind($results)
    {
        if(isset($results['PaidPlanCategory']['plan_id']))
        {
            $results = array(
                'PaidPlanCategory'=>array('cat_id'=>$results['PaidPlanCategory']['cat_id']),
                'PaidPlanIdCats'=>array($results['PaidPlanCategory']['plan_id']=>array($results['PaidPlanCategory']['cat_id']))
                );
        } else {
            $cat_ids = array();
            foreach($results AS $key=>$result)
            {
                if(isset($result['PaidPlanCategory']['plan_id']))
                {
                    $cat_ids[] = $result['PaidPlanCategory']['cat_id'];
                    $planid_cats[$result['PaidPlanCategory']['plan_id']][] = $result['PaidPlanCategory']['cat_id'];
                }
            }
            $results = array(
                'PaidPlanCategory'=>array('cat_id'=>$cat_ids),
                'PaidPlanIdCats'=>$planid_cats
                );
        }

        return $results;
    }
}
