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

class FieldOptionsController extends MyController
{
    var $uses = array('field_option','field','acl');

    var $components = array('config');

    var $autoRender = false;

    var $autoLayout = false;

    var $helpers = array('html','form','admin/paginator');

    function index()
    {
        $field_id = Sanitize::getInt($this->params,'id');

        $conditions = '';

        $total = 0;

        $query = "

            SELECT
                fieldid,type,name,title,groupid,location
            FROM
                #__jreviews_fields
            WHERE
                fieldid = " . $field_id;

        $field = $this->FieldOption->query($query,'loadAssoc');

        $filter_title = Sanitize::getString($this->params,'filter_title');

        if($filter_title != '') {

            $conditions = '(text LIKE ' . $this->QuoteLike($filter_title) . ' OR `value` LIKE ' . $this->QuoteLike($filter_title) . ')';
        }

        $rows = $this->FieldOption->getList($field_id, $this->offset, $this->limit, $total, $conditions);

        $this->set(array(
            'field_id'=>$field_id,
            'isNew'=>true,
            'options'=>$rows,
            'option'=>$this->FieldOption->emptyModel(),
            'field'=>$field,
            'pagination'=>array(
                'total'=>$total
            )
        ));

        return $this->render();
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());


        $this->action = 'index';

        $isNew = Sanitize::getInt($this->data['FieldOption'],'optionid') ? false : true;

        $field_id = Sanitize::getInt($this->data['FieldOption'],'fieldid');

        $text = Sanitize::getString($this->data['FieldOption'],'text');

        $value = Sanitize::stripAll($this->data['FieldOption'],'value');

        // Begin validation
        $validation_ids = array();

        $text == '' and $validation_ids[] = "option_text";

        $value == '' and $validation_ids[] = "option_value";

        if(!empty($validation_ids))
        {
            $response['str'] = 'FIELDOPTION_VALIDATE_TEXT_VALUE';

            return cmsFramework::jsonResponse($response);
        }

        // Begin save
        $this->data['FieldOption']['text'] = urlencode(html_entity_decode($this->data['FieldOption']['text'],ENT_QUOTES,'utf-8'));

        $result = $this->FieldOption->save($this->data);

        if($result != 'success')
        {
            $response['str'] = 'FIELDOPTION_VALIDATE_DUPLICATE';

            return cmsFramework::jsonResponse($response);
        }

        if($isNew)
        {
            $this->_db->setQuery("SELECT count(*) FROM #__jreviews_fieldoptions WHERE fieldid=".$field_id);

            $total = $this->_db->loadResult();

            $this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;

            $this->offset = ($this->page-1) * $this->limit;
        }


        $response['success'] = true;

        if($isNew) {

            $this->params['id'] = $field_id;

            $response['html'] = Sanitize::stripWhitespace($this->index());

            $response['id'] = $this->data['FieldOption']['optionid'];
        }

        return cmsFramework::jsonResponse($response);

    }

    function edit()
    {
        $this->name = 'fieldoptions';

        $this->autoRender = false;

        $this->autoLayout = false;

        $optionid =  Sanitize::getInt( $this->params, 'id');

        $isNew = false;

        $option = $this->FieldOption->findRow(array('conditions'=>array('FieldOption.optionid = ' . $optionid)));

        $field_id = $option['FieldOption']['fieldid'];

        $this->_db->setQuery("

            SELECT
                fieldid,type,name,title,groupid,location
            FROM
                #__jreviews_fields
            WHERE
                fieldid = " . $field_id
            );

        $field = current($this->_db->loadAssocList());

        $this->set(compact('field_id','option','field','isNew'));

        return $this->render();
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $this->FieldOption->delete('optionid',$ids);

        // Clear cache
        clearCache('', 'views');

        clearCache('', '__data');

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }

    function reorder() {

        $ordering = Sanitize::getVar($this->data,'order');

        $reorder = $this->FieldOption->reorder($ordering);

        return $reorder;
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->FieldOption->findRow(array('conditions'=>array('FieldOption.optionid = ' . $id)));

        return cmsFramework::jsonResponse($row);
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
                    #__jreviews_fieldoptions
                WHERE
                    fieldid = " . $field_id . "
                    AND
                    control_field <> ''
            ";

            $this->_db->setQuery($query);
            $count =  $this->_db->loadResult();

        }

        return $count;
    }
}
