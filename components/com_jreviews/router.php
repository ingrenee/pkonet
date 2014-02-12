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

$version = new JVersion();

$S2_ROOT = JPATH_BASE . DS . 'components' . DS . 'com_s2framework';

if (!defined('S2_ROOT')) 	define('S2_ROOT',$S2_ROOT);

require_once ( $S2_ROOT . DS . 's2framework' . DS . 'libs' . DS . 'cms_compat' . DS . 'joomla.php');

switch($version->RELEASE)
{
    case 1.5:
        require_once ( $S2_ROOT . DS . 's2framework' . DS . 'libs' . DS . 'cms_compat' . DS . 'joomla15.php');
    break;
}

function JreviewsBuildRoute(&$query)
{
    $segments = array();
    unset($query['view']);

    if(!function_exists('urlencodeParam')) return array();

    if(isset($query['url']))
    {
        $query_string = explode('/',$query['url']);
        // Properly urlencodes the jReviews parameters contained within the url parameter
        foreach($query_string AS $key=>$param) {
            $query_string[$key] = urlencodeParam($param);
        }
        $query['url'] = implode('/',$query_string);
        $segments[0] = $query['url'];
        unset($query['url']);
    }

	if(count($segments) == 1 && ($segments[0] == 'menu/' || $segments[0] == 'menu')) {
		unset($segments[0]);
	}

    return $segments;
}

function JreviewsParseRoute($segments)
{
    $vars = array();
    # Load own uri to overcome Joomla encoding issues with Greek params
    $uri = cmsFramework::_getUri();

    // Fix for Joomfish. Remove the language segment from the url
    if(class_exists('JoomFishManager')) {
        $lang = JFactory::getLanguage();
        $language = $lang->getTag();
        $jfm = JoomFishManager::getInstance();
        $lang_shortcode = $jfm->getLanguageCode($language);
        if(strstr($uri,'/'.$lang_shortcode.'/')) {
            $uri = str_replace('/'.$lang_shortcode.'/','/',$uri);
        }
    }
    $new_segments = cmsFramework::_parseSefRoute($uri);

    if($new_segments != null && end($new_segments) == 'index.php') {
        $new_segments = $segments;
    }

    // Remove Joomla language segment from url
    if(JRequest::getVar('language')!='' && strlen($new_segments[0]) == 2) {
        $new_segments[0] = 'index.php';
    }
    # Fix for sef without mod rewrite. Without it the sort urls don't work.

    // Remove the Itemid related segments when mod rewrite is disabled and Itemid exists
    if($new_segments[0] == 'index.php' && $new_segments[1] != 'component') {

        foreach($new_segments AS $key=>$segment) {

			if(
				!in_array(str_replace(' ','+',$segment),$segments) /* For J1.7+ */
				&& !in_array($segment,$segments) /* For J1.5 */
				&& !in_array(JreviewsStrReplaceOnce('-',':',urlencode($segment)),$segments) /* Joomla converts dash to colon */
                && !in_array(JreviewsStrReplaceOnce('-',':',$segment),$segments) /* Joomla converts dash to colon */
                ) {
                unset($new_segments[$key]);
            }
        }
    }

    if(count($new_segments) >= 3 && isset($new_segments[0]) && $new_segments[0] == 'index.php' && isset($new_segments[1]) && $new_segments[1] == 'component' && isset($new_segments[2]) && $new_segments[2] == 'jreviews') {
        array_shift($new_segments); array_shift($new_segments); array_shift($new_segments);
    }

	if(is_array($new_segments)) {
		$vars['url'] = implode('/',$new_segments);
	}

	return $vars;
}

function JreviewsStrReplaceOnce($str_pattern, $str_replacement, $string)
{
	$ocurrence = strpos($string, $str_pattern);

	if ($ocurrence !== false){
		return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
	}

	return $string;
}
