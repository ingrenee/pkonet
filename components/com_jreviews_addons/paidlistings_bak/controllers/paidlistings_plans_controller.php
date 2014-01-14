<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsPlansController extends MyController
{
    var $uses = array('menu','paid_plan','section','category','jreviews_category','paid_plan_category');

    var $components = array('config','access');

    var $helpers = array('form','assets','html','paid','paid_routes');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index()
    {
        $plans = array();

        $category = $categories = $sections = array();

        $dir_id = '';

        $cat_id = Sanitize::getInt($this->params,'cat_id',Sanitize::getInt($this->data,'catid'));

        $menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));

        $this->viewSuffix = Sanitize::getString($this->data,'tmpl_suffix',$this->viewSuffix);

        if($cat_id)
        {
            $plans = $this->PaidPlan->getCatPlans($cat_id);

            $category = $this->Category->findRow(array('conditions'=>array('Category.id = ' . $cat_id)));
        }
        else {

            if($this->cmsVersion == CMS_JOOMLA15)
            {
                // Get paid cat id array
                $paid_section_ids = $this->PaidPlanCategory->getPaidSectionIdsArray();

                // Gets list of jReviews Sections
                $sections = $this->Section->getList('',implode(',',$paid_section_ids),$dir_id);

                // Get list of jReviews Categories
                !empty($section_id) and $categories = $this->Category->getList($section_id);
            }
            else {

                // Get paid cat id array
                $paid_cat_ids = $this->PaidPlanCategory->getPaidCatIdsArray(true);

                $categories = $this->Category->getCategoryList(array(
                    'level'=>1,
                    'disabled'=>false,
                    'dir_id'=>$dir_id,
                    'listing_type'=>true,
                    'cat_id'=>implode(',', $paid_cat_ids)
                ));
           }

            $this->set(
                array(
                    'Access'=>$this->Access,
                    'User'=>$this->_user,
                    'sections'=>$sections,
                    'categories'=>$categories,
                )
            );
        }

        $page = $this->createPageArray($menu_id);

        $this->set(array(
            'cat_id'=>$cat_id,
            'plans'=>$plans,
            'page'=>$page,
            'category'=>$category,
            'categories'=>$categories));

        echo $this->render('paid_plans', 'index');
    }

    function getPlans($cat_id) {

        $plans = $this->PaidPlan->getCatPlans($cat_id);

        return $plans;
    }

    /*
    * J1.5 - Loads the categories for the selected section in new item submission
    */
    function _loadCategories()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array();

        $listing_id = Sanitize::getInt($this->data['Listing'],'id');

        $section_id = Sanitize::getInt($this->data['Listing'],'sectionid');

        $cat_id = Sanitize::getInt($this->data['Listing'],'catid');

        $cat_ids = '';

        if($section_id)
        {
            // If in edit mode limit the categories to the ones with the same criteriaid and custom fields
            if ($listing_id)
            {
                $query = "
                    SELECT
                        Category.id
                    FROM
                        #_jreviews_categories AS Category
                    INNER JOIN
                        #_jreviews_categories AS Criteria ON Criteria.criteriaid = Category.criteriaid AND Criteria.id = (
                            SELECT catid FROM #__content WHERE id = " . $listing_id . "
                        )
                    WHERE
                         Category.`option` = 'com_content'
                ";

                $cat_ids = implode(",",$this->Listing->query($query,'loadColumn'));
            }

            $categories = $this->Category->getList($section_id, $cat_ids);

            $this->set(array(
                'section_id'=>$section_id,
                'categories'=>$categories,
                'listing_id'=>0,
                'cat_id'=>0
            ));

            $categoryList = $this->render('elements','category_list');

            return $categoryList;
        }
    }

    /*
    * Loads the new item form with the review form and approriate custom fields
    */
    function _loadForm()
    {
        if($this->cmsVersion == CMS_JOOMLA15) return $this->_loadForm_j15();

        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array();

        $isLeaf = false;

        $level = Sanitize::getInt($this->data,'level');

        $cat_id = Sanitize::getInt($this->data,'catid');

        $cat_id_array =  Sanitize::getVar($this->data['Listing'],'catid');

        // Get paid cat id array
        $paid_cat_ids = $this->PaidPlanCategory->getPaidCatIdsArray();

        $paid_cat_ids_ancestors = $this->PaidPlanCategory->getPaidCatIdsArray(true);

        # No category selected
        if(!$cat_id)
        {
            // Check if there's a new cat id we can use
            $catArray = Sanitize::getVar($this->data['Listing'],'catid',array());

            $catArray = array_slice($catArray, 0, array_search(0, $catArray));

            if(!empty($catArray)) {

                $level = count($catArray);

                $cat_id = array_pop($catArray);
            }
        }

        # Category selected is not leaf. Need to show new category list with children, but clear every list to the right first!
        if(!$this->Category->isLeaf($cat_id))
        {
            $categories = $this->Category->getCategoryList(array(
                'parent_id'=>$cat_id,
                'indent'=>false,
                'disabled'=>false,
                'listing_type'=>true
                ));

            $categories = array_intersect_key($categories, array_flip($paid_cat_ids_ancestors));

            if(!empty($categories))
            {
                if(!empty($categories))
                {
                    $cat = reset($categories);

                    S2App::import('Helper','form','jreviews');

                    $Form = ClassRegistry::getClass('FormHelper');

                    $attributes = array('id'=>'cat_id'.$cat->level,'class'=>'jr-cat-select jrSelect','size'=>'1');

                    $select_list = $Form->select(
                        'data[Listing][catid][]',
                        array_merge(array(array('value'=>null,'text'=>JreviewsLocale::getPHP('LISTING_SELECT_CAT'))),$categories),
                        null,
                        $attributes
                    );

                    if($level >= 1 && count($cat_id_array) > 1) {

                        $response['level'] = $level - 1;
                    }

                    $response['select'] = $select_list;
                }
                else {

                    $response['action'] = 'no_access';

                    return cmsFramework::jsonResponse($response);
                }
            }

            # Checks if this category is setup with a listing type. Otherwise hides the form.
            if(!$this->Category->isJReviewsCategory($cat_id))
            {
                $response['action'] = 'hide_form';

                return cmsFramework::jsonResponse($response);
            }
        }
        else {

            $isLeaf = true;
        }

        # Category selected is leaf or set up with listing type, so show form
        if($cat_id)
        {
            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));

            $category  = $this->Category->findRow(array(
                'conditions'=>array('Category.id = ' . $cat_id)
            ));

            $this->set(array(
                'category'=>$category,
                'plans'=>$this->getPlans($cat_id),
                'User'=>$this->_user,
                'Access'=>$this->Access
            ));

           // Remove cat select lists to the right of current select list if current selection is a leaf
            if($level && $isLeaf)
            {
                $response['level'] = $level - 1;
            }

            $response['action'] = 'show_form';

            $response['html'] = $this->render('paid_plans','plans');

            return cmsFramework::jsonResponse($response);
        }

        # No category selected
        $response['level'] = 0;

        $response['action'] = 'hide_form';

        return cmsFramework::jsonResponse($response);
    }

    /**
    * Loads the new item form with the review form and approriate custom fields
    **/
    function _loadForm_j15()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array();

        $section_id = Sanitize::getInt($this->data['Listing'],'sectionid');

        $cat_id = Sanitize::getInt($this->data['Listing'],'catid');

        if ($section_id  && $cat_id) {

            # Get criteria info for selected category
            $category  = $this->Category->findRow(array(
                'conditions'=>array('Category.id = ' . $cat_id)
            ));

            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));

            $this->set(array(
                'category'=>$category,
                'plans'=>$this->getPlans($cat_id),
                'User'=>$this->_user,
                'Access'=>$this->Access
            ));

            $response['action'] = 'show_form';

            $response['html'] = $this->render('paid_plans','plans');

            return cmsFramework::jsonResponse($response);
        }
    }

}