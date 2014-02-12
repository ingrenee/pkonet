<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsCouponsController extends MyController
{
    var $uses = array('category','paid_coupon','paid_plan');

    var $helpers = array('admin/paginator','html','form','time','admin/admin_settings');

    var $components = array('config','access');

    var $autoRender = false;

    var $autoLayout = false;

    /**
    * Controller specific vars
    *
    */
    function beforeFilter()
    {
        $this->Access->init($this->Config);

        parent::beforeFilter();
    }

    function index()
    {
        $coupons = $this->PaidCoupon->findAll(array(
                'limit'=>$this->limit,
                'offset'=>$this->offset,
                'order'=>array('PaidCoupon.coupon_id')
            ),array() /* no callbacks */);

        $total = $this->PaidCoupon->findCount();

        $this->set(array(
            'coupons'=>$coupons,
            'pagination'=>array('total'=>$total)
        ));

        return $this->render('paidlistings_coupons','index');
    }

    function edit()
    {
        $coupon = array(
            'PaidCoupon'=>array('coupon_categories'=>array(),'coupon_plans'=>array())
            );

        $plans = array();

        $id = Sanitize::getInt($this->params,'id');

        if($id) {

            $coupon = $this->PaidCoupon->findRow(array('conditions'=>'PaidCoupon.coupon_id = ' . $id));
        }

        $this->PaidPlan->fields = array('PaidPlan.plan_id AS `PaidPlan.plan_id`','PaidPlan.plan_name AS `PaidPlan.plan_name`','PaidPlan.plan_type AS `PaidPlan.plan_type`');

        $plans  = $this->PaidPlan->findAll(array(
                    'conditions'=>array('PaidPlan.plan_state = 1') ,
                    'order'=>array('PaidPlan.plan_type ASC','PaidPlan.plan_name ASC')
                    ),array() /* no callbacks*/);

        $this->set(array(
            'coupon'=>$coupon,
            'plans'=>$plans
        ));

        return $this->render('paidlistings_coupons','create');
    }

    /**
    * Returns the category json object to build the category tree
    *
    */
    function jsonCategoryTree()
    {
        echo $this->Category->getCategoryList(array(
                'jstree'=>true,
                'indent'=>false,
                'conditions'=>array('ParentCategory.id IN (SELECT cat_id FROM #__jreviews_paid_plans_categories)')
            ));
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());

        $coupon_id = Sanitize::getInt($this->data['PaidCoupon'],'coupon_id');

        $isNew = !$coupon_id ? true : false;

        $cat_ids = Sanitize::getString($this->data,'cat_ids');

        $validation = array();

        if(Sanitize::getString($this->data['PaidCoupon'],'coupon_name') == '') {

            $response['str'][] = __a("You need fill in the coupon name.",true);
        }

        if(Sanitize::getInt($this->data['PaidCoupon'],'coupon_discount') == 0) {

            $response['str'][] = __a("You need to fill in a discount higher than zero.",true);
        }

        if(!empty($response['str'])) {

            return cmsFramework::jsonResponse($response);
        }

        $this->data['PaidCoupon']['coupon_users'] = implode(',',Sanitize::getVar($this->data['PaidCoupon'],'coupon_users',array()));

        $this->data['PaidCoupon']['coupon_plans'] = implode(',',Sanitize::getVar($this->data['PaidCoupon'],'coupon_plans',array()));

        # Ensure that some inputs have values
        $this->PaidCoupon->store($this->data);

        $response['success'] = true;

        if($isNew) {

            $response['isNew'] = $isNew;

            $response['html'] = $this->index();

            $response['id'] = $this->data['PaidCoupon']['coupon_id'];
        }

        return cmsFramework::jsonResponse($response);
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->PaidCoupon->findRow(array('conditions'=>array('PaidCoupon.coupon_id = ' . $id)));

        // Process columns that are not just plain text
        S2App::import('Helper','time','jreviews');

        $TimeHelper = ClassRegistry::getClass('TimeHelper');

        $coupon_ends = $row['PaidCoupon']['coupon_ends'];

        $row['PaidCoupon']['coupon_ends'] = $coupon_ends != NULL_DATE ? $TimeHelper->niceShort($coupon_ends) : '';

        return cmsFramework::jsonResponse($row);
    }

    /**
    * Returns the field group json object for the field tree
    *
    */
    function jsonFieldGroups()
    {
        $nodes = array();

        $plan_id = Sanitize::getInt($this->params,'plan_id');

        $group_id = Sanitize::getString($this->params,'group_id');

        $cat_ids = Sanitize::getString($this->params,'cat_id');

        $action = Sanitize::getString($this->params,'action');

        if($group_id && $action == 'checked') return $this->getFieldsbyGroupId($group_id); // Returns child nodes (fields) in the field tree when a group is checked

        if($plan_id || $cat_ids)
        {
            # Get cat ids in plan
            if($plan_id)
            {
                $plan = $this->PaidPlan->findRow(array('conditions'=>'PaidPlan.plan_id = ' . $plan_id));
                $cat_ids = implode(",",$plan['PaidPlanCategory']['cat_id']);
            }
            $group_ids = $this->getGroupIdsByCatId($cat_ids);
            if(!empty($group_ids))
            {
                $query = "
                    SELECT
                        groupid, title, name
                    FROM
                        #__jreviews_groups
                    WHERE
                        groupid IN (".$group_ids.")
                        AND
                        `type` = 'content'
                ";
                $this->_db->setQuery($query);
                $groups = $this->_db->loadAssocList();

                foreach($groups AS $group)
                {
                    $nodes[] = array(
                        "attributes"=>array("id"=>"g".$group['groupid']),
                        "data"=>$group['title'] . "(".$group['name'].")",
                        "state"=>"open"
//                        ,"children"=>$this->getFieldsbyGroupId($group['groupid'])
                    );
                }
            }
            return cmsFramework::jsonResponse($nodes);
        }
    }

    /**
    * Returns the field group ids corresponding to the passed node id which can be a catid
    *
    */
    function getGroupIdsByNode()
    {
        $nodes = array();
        $node_id = Sanitize::getVar($this->data,'node_id');
        $node_id = is_array($node_id) ? implode(",",$node_id) : $node_id;
        if(!empty($node_id))
        {
            $group_ids = explode(",",$this->getGroupIdsByCatId($node_id));

            if(!empty($group_ids))
            {
                foreach($group_ids AS $group_id)
                {
                    $nodes[] = "g".$group_id;
                }
            }
        }
        return cmsFramework::jsonResponse($nodes);
    }

    /**
    * Returns the field group ids corresponding to the passed cat ids
    *
    * @param array or comma separated string $cat_ids
    * @return comma separated list of group ids
    */
    function getGroupIdsByCatId($cat_ids = null)
    {
        $group_ids = null;
        $cat_ids = is_array($cat_ids) ? implode(",",$cat_ids) : $cat_ids;

        # Get field group ids used for selected categories
        if($cat_ids != '')
        {
            $query = "
                SELECT
                    ListingType.groupid
                FROM
                    #__jreviews_categories AS JreviewsCat
                LEFT JOIN
                    #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCat.criteriaid
                WHERE
                    JreviewsCat.`option` = 'com_content' AND JreviewsCat.id IN (".$cat_ids.")
            ";

            $group_ids = $this->PaidCoupon->query($query,'loadColumn');

            if(!empty($group_ids))
            {
                $group_ids = array_unique(explode(",",implode(",",array_filter($group_ids))));

                $group_ids = implode(",",$group_ids);
            }
        }
        return $group_ids;
    }

    /**
    * Returns the field json object corresponding to the passed group id
    *
    * @param int $group_id
    */
    function getFieldsbyGroupId($group_id)
    {
        $nodes = array();
        $group_id = (int)str_replace("g","",$group_id);
        if($group_id)
        {
            $query = "
                SELECT
                    name, title
                FROM
                    #__jreviews_fields
                WHERE
                    groupid = " . $group_id . "
                ORDER BY
                    title ASC
            ";
            $this->_db->setQuery($query);
            $fields = $this->_db->loadAssocList();
            foreach($fields AS $field)
            {
                $nodes[] = array(
                    "attributes"=>array("id"=>$field['name']),
                    "data"=>$field['title'] . "(".$field['name'].")"
                ) ;
            }
        }
        return cmsFramework::jsonResponse($nodes);
    }

    function _delete()
    {
        $response = array('success'=>false);

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $this->PaidCoupon->delete('coupon_id',$ids);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }



}
