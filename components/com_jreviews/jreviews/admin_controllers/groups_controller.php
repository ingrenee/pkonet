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

class GroupsController extends MyController {

	var $uses = array('group','field');

	var $helpers = array('html','form','admin/paginator');

    var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
    {
		$this->name = 'groups'; // required for admin controllers

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
        $this->action = 'index'; // For pagination after saving

        $type = Sanitize::getString($this->params, 'type', 'content');

	 	$total = 0;

	 	$rows = $this->Group->getList($type, $this->offset, $this->limit, $total);

		$this->set(
			array(
				'isNew'=>true,
				'rows'=>$rows,
				'type'=>$type,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);

		return $this->render('groups','index');
	}

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Group->findRow(array('conditions'=>array('Group.groupid = ' . $id)));

		return cmsFramework::jsonResponse($row);
	}

	function _save()
    {
    	$response = array('success'=>false,'str'=>array());

		$isNew = false;

		# Validate
		if (!isset($this->data['Group']['type'])) {

			$response['str'][] = 'GROUP_VALIDATE_TYPE';
		}

		if ($this->data['Group']['name']=='') {

			$response['str'][] = 'GROUP_VALIDATE_NAME';
		}

		if ($this->data['Group']['title']=='') {

			$response['str'][] = 'GROUP_VALIDATE_TITLE';
		}

		if (count($response['str']) > 0) {

            return cmsFramework::jsonResponse($response);
		}

		# New
		if(!Sanitize::getInt($this->data['Group'],'groupid')) {

			$isNew = true;

			$query = "SELECT max(ordering) FROM #__jreviews_groups WHERE type='".$this->data['Group']['type']."'";

			$max = $this->Group->query($query, 'loadResult');

			$this->data['Group']['ordering'] = $max + 1;
		}

        $control_value = Sanitize::getVar($this->data['Group'],'control_value');

        $control_field = Sanitize::getVar($this->data['Group'],'control_field');

        if(empty($control_value) || empty($control_field)) {

            $this->data['Group']['control_field'] = $this->data['Group']['control_value'] = '';
        }

        $this->data['Group']['name'] = mb_strtolower(trim(str_replace(' ','-',$this->data['Group']['name'])));

		$this->Group->store($this->data);

		if($isNew) {

			$query = "SELECT count(*) FROM #__jreviews_groups WHERE type='".$this->data['Group']['type']."'";

			$total = $this->Group->query($query,'loadResult');

			$this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;

			$this->offset = ($this->page-1) * $this->limit;

		}

		$response['success'] = true;

		if($isNew) {

			$this->params['type'] = Sanitize::getString($this->data['Group'],'type','content');

			$response['html'] = $this->index();

			$response['id'] = $this->data['Group']['groupid'];
		}

		return cmsFramework::jsonResponse($response);
	}

	function edit() {

		$this->autoRender = true;

		$groupid =  Sanitize::getInt( $this->params, 'id');

		$type = Sanitize::getString( $this->params, 'type');

		$row = $this->Group->findRow(array('conditions'=>array('groupid = ' . $groupid)));

		$this->set(array(
			'isNew'=>false,
			'row'=>$row
		));
	}

	function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid');

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

		$queryData = array(
			'conditions'=>array('groupid IN (' . cleanIntegerCommaList($ids) . ')')
		);

		$fieldCount = $this->Field->findCount($queryData);

		// First check if the group has any fields and force the user to delete the fields first
		if($fieldCount > 0)
        {
			$response['str'][] = array('GROUP_DELETE_NOT_EMPTY',$fieldCount);

 			return cmsFramework::jsonResponse($response);

		}

		$this->Group->delete('groupid',$ids);

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

 		$response['success'] = true;

 		return cmsFramework::jsonResponse($response);
	}

	function reorder() {

		$ordering = Sanitize::getVar($this->data,'order');

		$reorder = $this->Group->reorder($ordering);

		return $reorder;
	}

	function _publish()
    {
    	$response = array('success'=>false,'str'=>array());

        $group_id = Sanitize::getInt($this->params,'id');

        $query = "
        	SELECT
        		showtitle
        	FROM
        		#__jreviews_groups
        	WHERE groupid = " . $group_id;

        $state = !$this->Group->query($query,'loadResult');

        $query = "
        	UPDATE
        		#__jreviews_groups
        	SET
        		showtitle = " . (int) $state . "
        	WHERE
        		groupid = " . $group_id;

        if ($this->Group->query($query)){

        	$response['success'] = true;

            $response['state'] = (int) $state;

            clearCache('', 'views');

            clearCache('', '__data');
        }

        return cmsFramework::jsonResponse($response);
	}
}
