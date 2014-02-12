<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleFieldsController extends MyController {

	var $uses = array('menu','field_option');

	var $helpers = array('routes','form','html','assets','text','custom_fields');

	var $components = array('config','access');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
    {
		parent::beforeFilter();

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

		# Set Theme
		$this->viewTheme = $this->Config->template;
		$this->viewImages = S2Paths::get($this->app, 'S2_THEMES_URL') . $this->viewTheme . _DS . 'theme_images' . _DS;

	}

	function index()
	{
            global $Itemid;

            $cat_id = null;
            $conditions = array();
            $joins = array();
            $order = array();
            $menu_id = '';

            // Read module params
            $dir_id = Sanitize::getString($this->params['module'],'dir');
            $cat_id = Sanitize::getString($this->params['module'],'cat');
            $criteria_id = Sanitize::getString($this->params['module'],'criteria');

            $itemid_options = Sanitize::getString($this->params['module'],'itemid_options' );
            $itemid_hc = Sanitize::getInt($this->params['module'],'hc_itemid' );

            $field = Sanitize::paranoid(Sanitize::getString($this->params['module'],'field'),array('_'));
            $option_length = Sanitize::getInt($this->params['module'],'fieldoption_length');
            $custom_params = Sanitize::getString($this->params['module'],'custom_params');
            $sort = Sanitize::paranoid(Sanitize::getString($this->params['module'],'fieldoption_order'));

            # Category auto detect
            if(Sanitize::getInt($this->params['module'],'catauto'))
            {
                $ids = CommonController::_discoverIDs($this);
                extract($ids);
            }

            # Set menu id
            switch($itemid_options)
            {
                case 'category':

					$click2searchUrl = 'index.php?option=com_jreviews&amp;Itemid={itemid}&amp;url=tag/{field}/{optionvalue}';

					if(is_numeric($cat_id) && $cat_id >0) {

						$query = "
							SELECT
								dirid
							FROM
								#__jreviews_categories
							WHERE
								id = " . (int) $cat_id . "
								AND
								`option` = 'com_content'
						";

						$this->_db->setQuery($query);

						$dir_id = $this->_db->loadResult();

						$menu_id = $this->Menu->getCategory(array('cat_id'=>$cat_id,'dir_id'=>$dir_id));
					}
					elseif(is_numeric($dir_id) && $dir_id >0) {

						$menu_id = $this->Menu->getDir($dir_id);
					}
					else {

						$click2searchUrl = 'index.php?option=com_jreviews&amp;Itemid=&amp;url=tag/{field}/{optionvalue}';
					}

					$click2searchUrl = str_ireplace(array('{itemid}','{field}','/cat:{catid}','/criteria:{criteriaid}'),array($menu_id,substr($field,3),'',''),$click2searchUrl);

					break;

				case 'search':

					// Need a criteria id. If not specified in the module settings, we can get it from the category id if one is detected
					if(empty($criteria_id) && $cat_id > 0) {

						$query = "
							SELECT
								criteriaid
							FROM
								#__jreviews_categories
							WHERE
								id = " . (int) $cat_id . "
						";

						$this->_db->setQuery($query);

						$criteria_id = $this->_db->loadResult();
					}

					$click2searchUrl = 'index.php?option=com_jreviews&amp;Itemid={itemid}&amp;url=tag/{field}/{optionvalue}';

					if(is_numeric($criteria_id) && $criteria_id>0)
					{
						$menu_id = $this->Menu->get('jr_advsearch_'.$criteria_id);
					}

					if(empty($menu_id))
					{
						$menu_id = $this->Menu->get('jr_advsearch');
					}

					$click2searchUrl = str_ireplace(array('{itemid}','{field}','/cat:{catid}','/criteria:{criteriaid}'),array($menu_id,substr($field,3),'',''),$click2searchUrl);

					break;

                case 'hardcode':

					$click2searchUrl = 'index.php?option=com_jreviews&amp;Itemid={itemid}&amp;url=tag/{field}/{optionvalue}';

					$urlParamArray = array('dir'=>$dir_id,'criteria'=>$criteria_id,'cat'=>$cat_id);

					$params = arrayToParams($urlParamArray);

					$click2searchUrl .= ($params != '' ? '/' . $params . '/' : '');

                    $menu_id = $itemid_hc;

					$click2searchUrl = str_ireplace(array('{itemid}','{field}'),array($menu_id,substr($field,3)),$click2searchUrl);

                break;

                case 'none':

				default:

					$click2searchUrl = 'index.php?option=com_jreviews&amp;url=tag/{field}/{optionvalue}';

					$urlParamArray = array('dir'=>$dir_id,'criteria'=>$criteria_id,'cat'=>$cat_id);

					$params = arrayToParams($urlParamArray);

					$click2searchUrl .= ($params != '' ? '/' . $params . '/' : '');

                    $menu_id = $itemid_hc;

					$click2searchUrl = str_ireplace('{field}',substr($field,3),$click2searchUrl);

                break;
            }


			$this->FieldOption->modelUnbind(array(
                'FieldOption.value AS `FieldOption.value`',
                'FieldOption.fieldid AS `FieldOption.fieldid`',
                'FieldOption.image AS `FieldOption.image`',
                'FieldOption.ordering AS `FieldOption.ordering`',
                'FieldOption.optionid AS `FieldOption.optionid`',
                'FieldOption.text AS `FieldOption.text`'
            ));

            $fields[] = 'FieldOption.optionid AS `FieldOption.optionid`';
            $fields[] = 'FieldOption.value AS `FieldOption.value`';

            if($option_length) {
                    $fields[] = 'IF(CHAR_LENGTH(FieldOption.text)>'.$option_length.',CONCAT(SUBSTR(FieldOption.text,1,'.$option_length.'),"..."),FieldOption.text) AS `FieldOption.text`';
            } else {
                    $fields[] = 'FieldOption.text AS `FieldOption.text`';
            }
            // $joins[] = 'INNER JOIN #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid';
            $order[] = 'FieldOption.'.$sort;

            $field_options = $this->FieldOption->findAll(array(
                'fields'=>$fields,
                'conditions'=>'Field.name = ' . $this->Quote($field),
                'joins'=>$joins,
                'order'=>$order
            ));

			# Send variables to view template
            $this->set(array(
                'field'=>$field,
				'click2searchUrl'=>$click2searchUrl,
                'field_options'=>$field_options,
                'custom_params'=>$custom_params
            ));

            return $this->render('modules','fields');

	}
}