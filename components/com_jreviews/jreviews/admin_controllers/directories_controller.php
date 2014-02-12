<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DirectoriesController extends MyController {

	var $uses = array('directory','jreviews_category');

    var $helpers = array('form','html');

    var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
		$this->name = 'directories';

		$rows = $this->Directory->findAll(array(),array());

		$this->set(array(
			'isNew'=>true,
			'dir'=>$this->Directory->emptyModel(),
			'rows'=>$rows
		));

		return $this->render('directories','index');
	}

	function edit()
    {
		$this->name = 'directories';

		$this->autoRender = true;

		$dirid =  Sanitize::getInt( $this->params, 'id');

		if ($dirid) {

			$directory = $this->Directory->findRow(array('conditions'=>array('id = ' . $dirid)),array());
		}

		$this->set(array(
			'isNew'=>false,
			'dir'=>$directory
		));
	}

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Directory->findRow(array('conditions'=>array('Directory.id = ' . $id)));

		return cmsFramework::jsonResponse($row);
	}

	function _save($params)
    {
    	$response = array('success'=>false,'str'=>array());

        $this->action = 'index';

		// Begin validation
		if (isset($this->data['Directory']['title']) && $this->data['Directory']['title']=='') {

			$response['str'][] = 'DIRECTORY_VALIDATE_NAME';
		}

		if (isset($this->data['Directory']['desc']) && $this->data['Directory']['desc']=='') {

			$response['str'][] = 'DIRECTORY_VALIDATE_TITLE';
		}

		if (count($response['str']) > 0) {

			return cmsFramework::jsonResponse($response);
		}

		$isNew = Sanitize::getInt($this->data['Directory'],'id') ? false : true;

		$this->Directory->store($this->data);

		$response['success'] = true;

		if($isNew) {

			$response['html'] = $this->index();

	        $response['id'] = $this->data['Directory']['id'];
		}

		return cmsFramework::jsonResponse($response);
	}

	function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid');

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

		$queryData = array(
			'conditions'=>array('dirid IN (' . cleanIntegerCommaList($ids) . ')')
		);

		$catCount = $this->JreviewsCategory->findCount($queryData);

		// First check if the directory is currently in use by categories
		if($catCount > 0)
        {
			$response['str'][] = 'DIRECTORY_DELETE_NOT_EMPTY';

 			return cmsFramework::jsonResponse($response);

		}

		$this->Directory->delete('id',$ids);

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

 		$response['success'] = true;

 		return cmsFramework::jsonResponse($response);
	}
}