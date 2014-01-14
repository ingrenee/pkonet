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

class FieldsController extends MyController {

	var $uses = array('field','group','acl','criteria');

    var $components = array('config');

	var $helpers = array('html','form','admin/paginator','admin/admin_fields');

	var $autoRender = false;

	var $autoLayout = false;

	function index()
    {
    	$group_id = Sanitize::getInt($this->params,'groupid');

    	$location = Sanitize::getString($this->params,'location','content');

    	$type = Sanitize::getString($this->params,'type',null);

        $title = Sanitize::getString($this->params,'filter_title');

        $groupchange = Sanitize::getInt($this->data,'groupchange');

        $this->action = 'index'; // Required for paginator helper

		// First check if there are any field groups created
		$query = "SELECT count(*) FROM #__jreviews_groups";

		$count = $this->Field->query($query,'loadResult');

		if(!$count) {

			return JreviewsLocale::getPHP('FIELD_GROUPS_NOT_CREATED');
		}

		$lists = array();

		$total = 0;

		$rows = $this->Field->getList(compact('location','type','group_id','title'),$this->offset, $this->limit, $total);

		$this->set(
			array(
				'location'=>$location,
				'groups'=>$this->Group->getSelectList($location),
				'rows'=>$rows,
				'groupid'=>$group_id,
                'type'=>$type,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);

		return $this->render('fields','index');
	}

	function edit()
    {
		$this->name = 'fields';

		$this->action = 'edit';

		$this->autoRender = false;

		$fieldid = Sanitize::getInt($this->params,'id');

		$fieldParams = array();

		$groupList = array();

		$data_maxlength = '';

		$disabled = "'DISABLED'";

		if($fieldid)
        {
			$field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));

			$fieldParams = $field['Field']['_params'];

			if($field['Field']['type'] != 'banner') {

				$data_maxlength = $this->Field->getMaxDataLength($field['Field']['name'],$field['Field']['location']);
			}

			$location = Sanitize::getString($field['Field'],'location','content');

		}
        else {
			$field = $this->Field->emptyModel();

			$location = Sanitize::getString($this->params,'location','content');
		}

		$this->_db->setQuery("
            SELECT
                groupid AS value,
                CONCAT(title,' (',name,')') AS text
            FROM
                #__jreviews_groups
            WHERE
                type= " . $this->Quote($location) . "
            ORDER BY
            	ordering"
        );

		if (!$fieldGroups = $this->_db->loadObjectList())
        {
            return sprintf(JreviewsLocale::getPHP('FIELD_GROUP_TYPE_NOT_CREATED'),$location);
        }

		$this->set(array(
			'db_version'=>explode('.',$this->_db->getVersion()),
			'field'=>$field,
			'location'=>$location,
			'fieldParams'=>$fieldParams,
			'accessGroups'=>$this->Acl->getAccessGroupList(),
			'fieldGroups'=>$fieldGroups,
            'listingTypes'=>$this->Criteria->getSelectList(),
            'data_maxlength'=>$data_maxlength,
			'demo'=>(int)defined('_JREVIEWS_DEMO')
		));

		return $this->render();
    }

    function getAdvancedOptions($type,$fieldid,$location)
    {
        $this->name = 'fields';
        $this->action = 'advanced_options';
        $fieldParams = array();

        $script = '';

        if($fieldid)
        {
            $field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));
            $fieldParams = stringToArray($field['Field']['options'] );
        }

        # Preselect list/radio values based on current settings
        switch($type)
        {
            case 'integer': case 'decimal':
                $script = "jQuery('#curr_format').val(".Sanitize::getVar($fieldParams,'curr_format',1).");";
            break;
            case 'select': case 'selectmultiple': case 'radiobuttons': case 'checkboxes':
                $script = "jQuery('#options_ordering').val(".Sanitize::getVar($fieldParams,'option_ordering',0).");";
            break;
            case 'textarea': case 'text':
                $script = "jQuery('#allow_html').val(".Sanitize::getVar($fieldParams,'allow_html',0).");";
            break;
        }

        if (Sanitize::getVar($fieldParams,'output_format')=='' && !in_array($type,array('website','relatedlisting')))
        {
            $fieldParams['output_format'] = "{FIELDTEXT}";
        }
        else
        {
            $fieldParams['output_format'] = Sanitize::getVar($fieldParams,'output_format');
        }

        $fieldParams['valid_regex'] = !Sanitize::getVar($fieldParams,'valid_regex',0) ? '' : Sanitize::getVar($fieldParams,'valid_regex');

        $fieldParams['date_setup'] = trim(br2nl(stripslashes(Sanitize::getVar($fieldParams,'date_setup'))));

        $paramArray = array(
            'valid_regex',
            'allow_html',
            'click2searchlink',
            'output_format',
            'click2search',
            'click2add',
            'date_format',
            'option_images',
            'listing_type'
        );

        $params = new stdClass();

        foreach($paramArray AS $paramKey)
        {
            $params->$paramKey = null;
        }

        foreach($fieldParams AS $paramKey=>$paramValue)
        {
            $params->$paramKey = $paramValue;
        }

        if($fieldid && in_array($type,array('email','website'))) {

        	$params->valid_regex = null;
        }

        $this->set(
            array(
                'type'=>$type,
                'location'=>$location,
                'params'=>$params,
                'field_params'=>$fieldParams
            )
        );

        $page = $this->render();

        return $page;
    }

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Field->findRow(array('conditions'=>array('Field.fieldid = ' . $id)));

		return cmsFramework::jsonResponse($row);
	}

	function _save()
    {
    	$response = array('success'=>false,'str'=>array());

		$this->action = 'index';

        $apply = Sanitize::getBool($this->params,'apply',false);

		$isNew = false;

		$group_id = Sanitize::getInt($this->data['Field'],'groupid');

		$location = Sanitize::getString($this->data['Field'],'location');

        $control_value = Sanitize::getVar($this->data['Field'],'control_value');

        $control_field = Sanitize::getVar($this->data['Field'],'control_field');

        if(empty($control_value) || empty($control_field)) {

            $this->data['Field']['control_field'] = $this->data['Field']['control_value'] = '';
        }

		// Begin validation
		if ($location == '') {

			$response['str'][] = 'FIELD_VALIDATE_LOCATION';
		}

		if ($this->data['Field']['type']=='') {

			$response['str'][] = 'FIELD_VALIDATE_TYPE';
		}

		if ($group_id == 0) {

			$response['str'][] = 'FIELD_VALIDATE_GROUP';
		}

		if ($this->data['Field']['name']=='') {

			$response['str'][] = 'FIELD_VALIDATE_NAME';

		}
		else {

			$table = $this->Field->query(null,'getTableColumns','#__jreviews_content');

			$contentFields = isset($table['#__jreviews_content']) ? array_keys($table['#__jreviews_content']) : array_keys($table);

			$table = $this->Field->query(null,'getTableColumns','#__jreviews_review_fields');

			$reviewFields = isset($table['#__jreviews_review_fields']) ? array_keys($table['#__jreviews_review_fields']) : array_keys($table);

			$fields = array_merge($contentFields,$reviewFields);

			if(in_array('jr_'.$this->data['Field']['name'], $fields)){

				$response['str'][] = 'FIELD_VALIDATE_DUPLICATE';
			}
		}

		if ($this->data['Field']['title'] == '') {

			$response['str'][] = 'FIELD_VALIDATE_TITLE';
		}

		if (!empty($response['str'])) {

			return cmsFramework::jsonResponse($response);
		}


		// Convert array settings to comma separated list
        if(isset($this->data['Field']['params']) && !empty($this->data['Field']['params']['listing_type']))
        {
            $this->data['__raw']['Field']['params']['listing_type'] = implode(',',$this->data['Field']['params']['listing_type']);
        } else {
            $this->data['__raw']['Field']['params']['listing_type'] = '';
        }

        if(isset($this->data['Field']['access']) && !empty($this->data['Field']['access']))
        {
            $this->data['Field']['access'] = implode(',',$this->data['Field']['access']);
        } else {
            $this->data['Field']['access'] = 'none';
        }

        if(isset($this->data['Field']['access_view']) && !empty($this->data['Field']['access_view']))
        {
            $this->data['Field']['access_view'] = implode(',',$this->data['Field']['access_view']);
        } else {
            $this->data['Field']['access_view'] = 'none';
        }

		// Process different field options (parameters)
		$params = Sanitize::getVar( $this->data['__raw']['Field'], 'params', '');

		if (is_array( $params ))
        {
			$txt = array();
			foreach ($params as $k=>$v) {
				$v = str_replace("\n","<br />",$v);
				$txt[] = "$k=$v";
			}

	 		$this->data['Field']['options'] = implode("\n", $txt );

			unset($this->data['Field']['params']);
		}

		// If new field, then add jr_ prefix to it.
		if (!Sanitize::getInt($this->data['Field'],'fieldid')) {

			$this->data['Field']['name'] = "jr_".strtolower(Inflector::slug($this->data['Field']['name'],''));

			$isNew = true;
		}

		// Add last in the order for current group if new field
		if ($isNew) {

			$this->_db->setQuery("select max(ordering) FROM #__jreviews_fields WHERE groupid = " . $group_id);

			$max = $this->_db->loadResult();

			if ($max > 0) $this->data['Field']['ordering'] = $max+1; else $this->data['Field']['ordering'] = 1;
		}

		// If multiple option field type (multipleselect or checkboxes) then force listsort to 0;
		if (in_array($this->data['Field']['type'],array("selectmultiple","checkboxes"))) {

			$this->data['Field']['listsort'] = 0;
		}

//        elseif ($this->data['Field']['type'] == 'banner') {
            $this->data['Field']['description'] = $this->data['__raw']['Field']['description'];
//        }

		// First lets create the new column in the table in case it fails we don't add the field
		if ($isNew)
        {
			$added = $this->Field->addTableColumn($this->data['Field'], $this->data['Field']['location']);

			if ($added != '') {

				$response['str'][] = 'DB_ERROR';

            	return cmsFramework::jsonResponse($response);
			}
		}

		// Now let's add the new field to the field list
		$this->Field->store($this->data);

        $response['success'] = true;

        if($apply){

            return cmsFramework::jsonResponse($response);
        }

        if($isNew) {

        	$query = "
            	SELECT
            		count(*)
            	FROM
            		#__jreviews_fields
            	WHERE
            		groupid = " . $group_id . " AND location = '". $location ."'";

            $total = $this->Field->query($query,'loadResult');

            $this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;

            $this->offset = ($this->page-1) * $this->limit;

        	$this->params['location'] = $location;

        	$this->params['groupid'] = $group_id;

	        $response['id'] = $this->data['Field']['fieldid'];

	        $response['isNew'] = true;

			$response['html'] = $this->index();
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

		$ids = cleanIntegerCommaList($ids);

		// need to drop column from #__jreviews_content
		$query = "
			SELECT
				name, type, location
			FROM
				#__jreviews_fields
		 	WHERE
		 		fieldid IN (" . $ids . ")" ;

		$fields = $this->Field->query($query,'loadAssocList');

		$field = reset($fields);

		$location = $field['location'];

		$removed = $this->Field->deleteTableColumn($fields, $location);

		if (!$removed) {

			return cmsFramework::jsonResponse($response);
		}

		$query = "
			DELETE
				Field,
				FieldOption
			FROM
				#__jreviews_fields AS Field
			LEFT JOIN
				#__jreviews_fieldoptions AS FieldOption ON Field.fieldid = FieldOption.fieldid
			WHERE
				Field.fieldid IN (" . $ids . ")
		";

		$this->Field->query($query);

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

 		$response['success'] = true;

 		return cmsFramework::jsonResponse($response);

	}

	function _changeFieldLength()
	{
		$response = array('success'=>false,'str'=>array());

		$field_id = Sanitize::getString($this->params,'id',Sanitize::getInt($this->data,'id'));

		$task = Sanitize::getString($this->params,'task',Sanitize::getString($this->params,'task'));

		$field = $this->Field->findRow(array('conditions'=>array('Field.fieldid = ' . $field_id)));

		$fname = $field['Field']['name'];

		$max_length = $this->Field->getMaxDataLength($fname,$field['Field']['location']);

		if($task == 'form')
		{
			$this->set(array(
				'field'=>$field,
				'max_length'=>$max_length
			));

			return $this->render('fields','create_fieldlength');
		}

		# Process length change here
		$db = cmsFramework::getDB();

		$db_version = explode('.',$db->getVersion());

		if($db_version[0] >= 5 && $db_version[1] >= 0 && $db_version[2] >= 3) {

			$max = 65535;
		}
		else {

			$max = 255;
		}

		$new_maxlength = min($max,Sanitize::getInt($this->data['Field'],'maxlength'));

		if($new_maxlength == 0)
		{
			$response['str'][] = 'FIELD_VALIDATE_ZERO_LENGTH';

			return cmsFramework::jsonResponse($response);
		}

		if($new_maxlength < $max_length)
		{
			$response['str'][] = 'FIELD_VALIDATE_STORED_LENGTH';

			return cmsFramework::jsonResponse($response);
		}


		if($this->Field->modifyTableColumn($field, $new_maxlength))
		{
			$response['success'] = true;

 			$response['maxlength'] = $new_maxlength;

			return cmsFramework::jsonResponse($response);
		}

		$response['str'][] = 'FIELD_LENGTH_CHANGE_FAILED';

		return cmsFramework::jsonResponse($response);
	}

    /**
    * Checks if there are any field option=>field option relationships
    *
    */
    function _controlledByCheck()
    {
        $count = 0;

        if($field_id = Sanitize::getInt($this->params,'id')) {

            $query = "
                SELECT
                    count(*)
                FROM
                    #__jreviews_fields
                WHERE
                    fieldid = " . $field_id . "
                    AND
                    control_field <> ''
            ";

            $count =  $this->Field->query($query,'loadResult');
        }

        return $count;
    }

	function reorder() {

		$ordering = Sanitize::getVar($this->data,'order');

		$reorder = $this->Field->reorder($ordering);

		return $reorder;
	}

	function checkType()
    {
        $success = true;

        $fieldid = Sanitize::getString($this->params,'id');

        $type = Sanitize::getString($this->params,'type');

        $location = Sanitize::getString($this->params,'location');

        if($type !='' && $location)
        {
            $page = $this->getAdvancedOptions($type,$fieldid,$location);

        }
        else {

            $page = '';
        }

        return $page;
	}
}
