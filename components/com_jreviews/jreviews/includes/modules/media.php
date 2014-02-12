<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

# MVC initalization script
require(JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php');

# Populate $params array with module settings
$module_params = isset($params->_raw) ? stringToArray($params->_raw) : $params->toArray();
$moduleParams['module'] = $module_params;
$moduleParams['module_id'] = $module->id;
$moduleParams['page'] = 1;
$moduleParams['data']['module'] = true;
$moduleParams['data']['controller'] = 'module_media';
$moduleParams['data']['action'] = 'index';
$moduleParams['secret'] = cmsFramework::getConfig('secret');
$moduleParams['token'] = cmsFramework::formIntegrityToken($moduleParams,array('module','module_id','form','data'),false);

$Dispatcher = new S2Dispatcher('jreviews');
echo $Dispatcher->dispatch($moduleParams);
unset($Dispatcher);
