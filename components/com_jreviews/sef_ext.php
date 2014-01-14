<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

class sef_jreviews {

	var $__Menu;

	var $__controllerActions = array(
		'tag',
		'search-results',
		'new-listing',
		'discussions',
		'advanced-search',
		'_d[0-9]+',
		'_s[0-9]+',
		'_c[0-9]+',
		'_l[0-9]+',
		'.rss',
		'_alphaindex_',
		'favorites',
		'reviewers',
		'paidlistings_orders',
		'paidlistings_accounts',
		'paidlistings_plans',
		'my-media',
		'widgetfactory'
	);

	var $cmsVersion;

	var $joomla_style_params;

	function &getInstance() {

		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] = new sef_jreviews();
			require( dirname(__FILE__) . DS . 'jreviews' . DS . 'framework.php');

			S2App::import('Model','Menu','jreviews');

			$instance[0]->__Menu = ClassRegistry::getClass('MenuModel');


			// Read url param style setting from JReviews config cache file
			$cache_file = S2CacheKey('jreviews_config');

			$Config = S2Cache::read($cache_file,'_s2framework_core_');

			$instance[0]->joomla_style_params = is_object($Config) and isset($Config->url_param_joomla) ? $Config->url_param_joomla : false;

			$version = new JVersion();

            $instance[0]->cmsVersion = $version->RELEASE;
		}

		return $instance[0];
	}

	function create($string)
	{
		$_this =& sef_jreviews::getInstance();

		$query_string_new = $query_string = array();

		$sefstring = $url = $menu = $trailing_slash = '';

		$string = str_replace(array('&amp;','*@*'),array('&',':'),$string);

		// Get external param to include them at the end
		$p = str_replace('index.php?option=com_jreviews','',$string);

		parse_str(str_replace('&amp;','&',$p),$params);

		$hasMenuId = isset($params['Itemid']) && $params['Itemid'] > 0;

		$isMenuLink = preg_match('/^index.php\?option=com_jreviews&view=[a-z0-9].*&Itemid=[0-9]+|index.php\?option=com_jreviews&Itemid=[0-9]+$/i',$string, $matches);


		if(isset($params['url']))
		{
			$query_string = explode('/',urldecode($params['url']));

			// Properly urlencodes the jReviews parameters contained within the url parameter
			foreach($query_string AS $key=>$param) {
				if($param != 'menu' || ($param == 'menu' && !$hasMenuId)) {
					$query_string_new[$key] = urlencodeParam($param);
					unset($params[$key]);
				}
			}
		}

		// Get the menu name
		if($hasMenuId) {
			$menu = $_this->__Menu->getMenuAlias($params['Itemid']);
			$sefstring .= $menu . '/';
		}

		if($isMenuLink) {
			$sefstring = $menu;
		}
		elseif(!$_this->joomla_style_params && $hasMenuId && !preg_match('/'.implode('|',$this->__controllerActions).'/',isset($params['url']) ? $params['url'] : '')) {
			$sefstring = $menu;
		}
		elseif(!empty($query_string_new)) {

			$url = implode('/',$query_string_new);

			$sefstring .= $url;
		}
		elseif(!$_this->joomla_style_params) {
			$sefstring = $string;
			$params = array();
		}

		// include external params
		unset($params['Itemid'], $params['view'], $params['url']);

		$sefstring = rtrim($sefstring,'/');

		if(!empty($params) && $query_string[0] != 'tag') {
			$sefstring .= '/?' . sef_jreviews::buildUrlParams($params, $_this->joomla_style_params);
		}
		elseif(!empty($params) && $query_string[0] == 'tag') {
			$sefstring .= '?' . sef_jreviews::buildUrlParams($params, $_this->joomla_style_params);
		}
		elseif(empty($params)
			&&
				(
					empty($query_string)
					||
					(!empty($query_string)
						&&
						$query_string[0] != 'tag'
					)
				)
		){
			$trailing_slash = '/';
		}

		return rtrim($sefstring,'/') . $trailing_slash;
	}

	function revert ($url_array, $pos) {

		$_PARAM_CHAR = ':';

		$url = array();

		$menu_id = '';

		$_this =& sef_jreviews::getInstance();

		global $QUERY_STRING;

		// Is the tag semgment present in the url?
		$tag = false;
		if(isset($url_array[$pos+3]) && $url_array[$pos+3] == 'tag') {
			$tag = true;
		}

		// First check if this is a menu link by looking for the menu name to get an Itemid
		if(!$tag && isset($url_array[$pos+2]) && $menu_id = $_this->__Menu->getMenuIdByAlias($url_array[$pos+2])) {

			$_GET['Itemid'] = $_REQUEST['Itemid'] = $menu_id;
			$QUERY_STRING = "option=com_jreviews&Itemid=$menu_id";

			for($i=$pos+2;$i<count($url_array);$i++) {
				if($url_array[$i] != '' && false!==strpos($url_array[$i],$_PARAM_CHAR)) {
					$parts = explode($_PARAM_CHAR,$url_array[$i]);
					if(isset($parts[1]) && $parts[1]!='') {
						$url[$parts[0]] = $parts[1];
						$_GET[$parts[0]] = $_REQUEST[$parts[0]] = $parts[1];
					}
				}
			}

			if(!empty($url) && count($url_array) < 3) {

				$QUERY_STRING .= '&url=menu&' . sef_jreviews::buildUrlParams($url);

			}
			elseif(count($url_array) >= 3) {

				array_shift($url_array);
				array_shift($url_array);
				if (!empty($url_array) && $url_array[0]!='') {
					$QUERY_STRING .= '&url=' . implode('/',$url_array);
					$_GET['url'] = $_REQUEST['url'] = implode('/',$url_array);
				}

			}

		} else {

			$tag and $pos++;

			$menu_id = $_this->__Menu->getMenuIdByAlias($url_array[$pos+1]);

			if(!$menu_id) {
				$menu_id = $_GET['Itemid'] = $_REQUEST['Itemid'] = '';
			}
			else {
				$_GET['Itemid'] = $_REQUEST['Itemid'] = $menu_id;
			}

			// Not a menu link, so we use the url named param
			for($i=$pos+2;$i<count($url_array);$i++) {
				if($url_array[$i] != '') {
					$url[] = $url_array[$i];
				}
			}

			$url = implode('/',$url);

			$_GET['url'] = $_REQUEST['url'] = $url;
			$_GET['option'] = $_REQUEST['option'] = 'com_jreviews';

			$QUERY_STRING = "option=com_jreviews&Itemid=$menu_id&url=$url";

		}

//			return $QUERY_STRING;
	}

	function buildUrlParams($params, $joomla_style_params = false)
	{
		if(empty($params)) return '';

		$query = array();

		foreach($params AS $key=>$val)
		{
			$query[] = $joomla_style_params ? $key.'='.$val : $key.'*@*'.$val;
		}

		return $joomla_style_params ? implode('&',$query) : implode('/',$query) ;
	}

}
