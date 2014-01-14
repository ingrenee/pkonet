<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsHandlersController extends MyController
{
    var $uses = array('paid_handler');

    var $helpers = array('html','form','admin/admin_settings');

    var $components = array('config');

    var $autoRender = false;

    var $autoLayout = false;

    /**
    * Controller specific vars
    */
    function beforeFilter()
    {
        parent::beforeFilter();
    }

    function index()
    {
        $handlers = $this->PaidHandler->findAll(array('order'=>array('PaidHandler.ordering')));

        $this->set(array(
            'handlers'=>$handlers
        ));

        return $this->render('paidlistings_handlers','index');
    }

    function edit()
    {
        $handler_id = Sanitize::getInt($this->params,'id');

        $handler_plugins = array();

        if($handler_id)
        {
            $handler = $this->PaidHandler->findRow(array('conditions'=>'PaidHandler.handler_id = ' . $handler_id));

            $this->set(array(
                'handler'=>$handler,
                'handler_theme'=>rtrim($handler['PaidHandler']['theme_file']),
            ));

            return $this->render('paidlistings_handlers','handler_theme');
        }

        // Get list of handler plugins
        $Folder = new S2Folder();

        $Folder->path = PATH_ROOT . DS . 'components' . DS . 'com_jreviews_addons' . DS . 'paidlistings' . DS . 'controllers' . DS . 'components' . DS;

        $handler_files = next($Folder->read(true,true,true));

        foreach($handler_files AS $handler)
        {
            $parts = pathinfo($handler);
            if(!strstr($parts['basename'],'index.html') && strstr($parts['basename'],'handler_'))
            {
                $handler_plugins[$parts['basename']] = $parts['basename'];
            }
        }

        $handlers = $this->PaidHandler->findAll(array('order'=>array('PaidHandler.ordering')));

        foreach($handlers AS $handler)
        {
            unset($handler_plugins[$handler['PaidHandler']['plugin_file'].'.php']);
        }

        $this->set(array(
            'handler_plugins'=>$handler_plugins
        ));

        return $this->render('paidlistings_handlers','create');

    }

    function reorder() {

        $ordering = Sanitize::getVar($this->data,'order');

        $reorder = $this->PaidHandler->reorder($ordering);

        return $reorder;
    }

    function _delete()
    {
        $id = (int) $this->data['entry_id'];

        $this->response[] = "jreviews_admin.dialog.close();";

        $this->response[] = "jreviews_admin.tools.removeRow('handler{$id}');";

        $this->PaidHandler->delete('handler_id',$id);

        return $this->ajaxResponse($this->response);
    }

    function _save()
    {
        $response = array('success'=>false);

        $isNew = false;

        if(isset($this->data['__raw']['PaidHandler']['settings']['offline']))
        {
            $this->data['PaidHandler']['settings']['offline'] = $this->data['__raw']['PaidHandler']['settings']['offline'];
        }

        if(isset($this->data['PaidHandler']['settings'])) {

            $this->data['PaidHandler']['settings'] = json_encode($this->data['PaidHandler']['settings']);
        }

        $name = Sanitize::getString($this->data['PaidHandler'],'name');

        if($name == '') {

            $response['str'][] = __a("You need to fill out the payment handler name",true);

        }

        # New
        if(!Sanitize::getInt($this->data['PaidHandler'],'handler_id')) {

            $isNew = true;

            $plugin_file = Sanitize::getString($this->data['PaidHandler'],'plugin_file');

            if($plugin_file == '') {

                $response['str'][] = __a("You need to select a payment handler from the list",true);

            }

            $this->data['PaidHandler']['plugin_file'] = $this->data['PaidHandler']['theme_file'] =  str_replace('.php','',$plugin_file);

            $this->_db->setQuery("SELECT max(ordering) FROM #__jreviews_paid_handlers");

            $max = $this->_db->loadResult();

            $this->data['PaidHandler']['ordering'] = $max + 1;
        }

        if(!empty($response['str'])) {

            return cmsFramework::jsonResponse($response);
        }

        $this->PaidHandler->store($this->data);

        $response['success'] = true;

        if($isNew) {

            $this->params['type'] = Sanitize::getString($this->data['Group'],'type','content');

            $response['html'] = $this->index();

            $response['id'] = $this->data['PaidHandler']['handler_id'];
        }

        return cmsFramework::jsonResponse($response);
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->PaidHandler->findRow(array('conditions'=>array('PaidHandler.handler_id = ' . $id)));

        return cmsFramework::jsonResponse($row);
    }}
