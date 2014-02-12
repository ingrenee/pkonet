<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsPlansController extends MyController
{
    var $uses = array('category','paid_plan','paid_plan_category');

    var $helpers = array('html','form','paid','admin/admin_settings');

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
        $this->set(array(
            'plans'=>$this->PaidPlan->findAll(array(
                'order'=>array('PaidPlan.plan_type','PaidPlan.payment_type','PaidPlan.plan_price'))
            )
        ));
        return $this->render('paidlistings_plans','index');
    }

    function edit()
    {
        $plan = array(
            'PaidPlan'=>array('plan_array'=>array('fields'=>array())),
            'PaidPlanCategory'=>array('cat_id'=>array())
        );

        $id = Sanitize::getInt($this->params,'id');

        $group_ids = array();

        if($id)
        {
            $plan = $this->PaidPlan->findRow(array('conditions'=>'PaidPlan.plan_id = ' . $id));

            $fields = Sanitize::getVar($plan['PaidPlan']['plan_array'],'fields',array());

            if(!empty($fields))
            {
                !is_array($fields) and $fields = explode(',',$fields);
                $fields = implode(",",array_map(array($this,'quote'),$fields));

                if($fields)
                {
                    $query = "
                        SELECT DISTINCTROW
                            groupid
                        FROM
                            #__jreviews_fields
                        WHERE
                            name IN (".$fields.")
                    ";

                    $group_ids = $this->PaidPlan->query($query,'loadColumn');
                }
            }

        }

        $this->set(array(
            'plan'=>$plan,
            'group_ids'=>$group_ids
        ));

        return $this->render('paidlistings_plans','create');
    }

    /**
    * Returns the category json object to build the category tree
    *
    */
    function jsonCategoryTree()
    {
        return $this->Category->getCategoryList(array('jstree'=>true,'indent'=>false));
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

        if($group_id && $action == 'checked') return $this->getFieldsbyGroupId($group_id, true); // Returns child nodes (fields) in the field tree when a group is checked

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
                        "attr"=>array("id"=>"g".$group['groupid']),
                        "data"=>$group['title'] . "(".$group['name'].")"
                        ,"children"=>$this->getFieldsbyGroupId($group['groupid'],false)
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

        $node_id = Sanitize::getVar($this->params,'node_id');

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

            $group_ids = $this->PaidPlan->query($query,'loadColumn');

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
    function getFieldsbyGroupId($group_id, $json = true)
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
                    "attr"=>array("id"=>$field['name']),
                    "data"=>$field['title'] . "(".$field['name'].")"
                ) ;
            }
        }
        return $json ? json_encode($nodes) : $nodes;
    }

    function _delete()
    {
        $response = array('success'=>false);

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        foreach($ids AS $id)
        {

            $this->PaidPlan->delete('plan_id',$id);

            $this->PaidPlanCategory->delete('plan_id',$id);
        }

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());

        $plan_id = Sanitize::getInt($this->data['PaidPlan'],'plan_id');

        $isNew = !$plan_id ? true : false;

        $cat_ids = Sanitize::getString($this->data,'cat_ids');

        $validation = array();

        if(Sanitize::getString($this->data['PaidPlan'],'plan_name') == '') {

            $response['str'][] = __a("You must fill out the plan name.",true);
        }

        if(in_array(Sanitize::getInt($this->data['PaidPlan'],'plan_type'),array(0,1))
            && Sanitize::getFloat($this->data['PaidPlan'],'payment_type') != 2
            && Sanitize::getFloat($this->data['PaidPlan'],'plan_price') == 0) {

            $response['str'][] = __a("You must fill out a valid price for paid plans.",true);
        }

        if(Sanitize::getString($this->data['PaidPlan']['plan_array'],'duration_number') == ''
            && Sanitize::getString($this->data['PaidPlan']['plan_array'],'duration_period') != 'never') {

            $response['str'][] = __a("You must fill out the numeric value for the plan's duration.",true);
        }

        if(!$cat_ids) {

            $response['str'][] = __a("You must select at least one category for this plan.",true);
        }

        // if plan marked as default plan, then check if there's already another default plan in selected categories
        If($cat_ids
            &&
            Sanitize::getInt($this->data['PaidPlan'],'plan_default')
            &&
            !$this->PaidPlan->validateDefaultPlan($cat_ids, Sanitize::getInt($this->data['PaidPlan'],'plan_id', Sanitize::getInt($this->data['PaidPlan'],'plan_type'))))
        {
            $response['str'][] = __a("A default plan of the same type already exists for one of the selected categories.",true);
        }

        if(!empty($response['str'])) {

            return cmsFramework::jsonResponse($response);
        }

        $this->data['PaidPlan']['plan_array']['description'] = $this->data['__raw']['PaidPlan']['plan_array']['description'];

        # Ensure that some inputs have values
        $this->data['PaidPlan']['plan_type'] == 0 and $this->data['PaidPlan']['plan_upgrade_exclusive'] = 0; // New listing plans are always exclusive, so we don't care about this value

        $this->data['PaidPlan']['plan_price'] = Sanitize::getFloat($this->data['PaidPlan'],'plan_price',0);

        !isset($this->data['PaidPlan']['plan_array']['duration_number']) and $this->data['PaidPlan']['plan_array']['duration_number'] = 0;

        $this->data['PaidPlan']['plan_updated'] = _CURRENT_SERVER_TIME;

        $this->data['PaidPlan']['photo'] = $this->data['PaidPlan']['photo'] == '' ? null : $this->data['PaidPlan']['photo'];

        $this->data['PaidPlan']['video'] = $this->data['PaidPlan']['video'] == '' ? null : $this->data['PaidPlan']['video'];

        $this->data['PaidPlan']['attachment'] = $this->data['PaidPlan']['attachment'] == '' ? null : $this->data['PaidPlan']['attachment'];

        $this->data['PaidPlan']['audio'] = $this->data['PaidPlan']['audio'] == '' ? null : $this->data['PaidPlan']['audio'];

        $this->PaidPlan->store($this->data,true);

        $response['success'] = true;

        if($isNew) {

            $response['isNew'] = $isNew;

            $response['html'] = $this->index();

            $response['id'] = $this->data['PaidPlan']['plan_id'];
        }

        clearCache('', 'core');

        return cmsFramework::jsonResponse($response);
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->PaidPlan->findRow(array('conditions'=>array('PaidPlan.plan_id = ' . $id)));

        return cmsFramework::jsonResponse($row);
    }
}
