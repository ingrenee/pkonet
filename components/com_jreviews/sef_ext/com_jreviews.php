<?php
/**
 * sh404SEF support for com_jreviews component.
 * Author : ClickFWD LLC
 * contact : support@reviewsforjoomla.com
 */

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

$format = JRequest::getVar('format');

if($format == 'raw') return;

/**
 * SOME CONFIGURATION OPTIONS
 */

// This is used to properly route URLs without an Itemid to the JReviews component
$default_no_itemid_segment = 'reviews';

// Options are: parents-alias, current-alias
$menu_structure = 'current-alias';

// ------------------  standard plugin initialize function - don't change ---------------------------

global $sh_LANG, $sefConfig, $shGETVars;

$shLangName = '';

$shLangIso = '';

$title = array();

$shItemidString = '';

$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);

if ($dosef == false) return;

// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_XXXXX', $shLangIso, '_SEF_SAMPLE_TEXT_STRING');
// ------------------  load language file - adjust as needed ----------------------------------------

// start by inserting the menu element title (just an idea, this is not required at all)
$Itemid = isset($Itemid) ? $Itemid : null;

$_PARAM_CHAR = '*@*';

$newUrl = '';

$url = isset($url) ? urldecode($url) : '';

switch($menu_structure) {

	case 'parents-alias':

		$item = JFactory::getApplication()->getMenu()->getItem($Itemid);

		$route = isset($item->route) ? $item->route : getMenuTitle($option, null, $Itemid, null, $shLangName );

	break;

	case 'current-alias':
	default:

		$route = getMenuTitle($option, null, $Itemid, null, $shLangName );

	break;
}
/**
 * It's a JReviews link with parameters
 */
if(isset($url) && $url!='' && strpos($url,'menu')===false)
{
	$title[] =  !isset($Itemid) ? $default_no_itemid_segment : $route;

	$urlParams = explode('/', $url);

	foreach($urlParams as $urlParam)
	{
		// Segments
		if (false === strpos($urlParam,$_PARAM_CHAR)) {

			$title[] = rtrim( $urlParam, '/');

			$newUrl .= $urlParam . '/';
		// Internal to external parameter conversion
		}
		else {

			$bits = explode($_PARAM_CHAR,$urlParam);

			shAddToGETVarsList($bits[0], stripslashes(urldecode($bits[1])));
		}
	}

	if($newUrl != '') {

		// Trick to force the new url to be saved to the database
		shAddToGETVarsList('url', stripslashes(rtrim($newUrl, '/')));  // this will establish the new value of $url

		shRemoveFromGETVarsList('url');  // remove from var list as url is processed, so that the new value of $url is stored in the
	}

	$title[] = ''; // Prevents sh404sef from adding the .html at the end of the url for non-menu links

}
/**
 * It's a menu link
 */
else {


	$url = urldecode($url); // 2nd pass urldecode is required

	if($url == '') {

		$title[] = $route;

	} else {

		$urlParams = explode('/', $url);

		foreach( $urlParams as $urlParam)
		{
			if($urlParam != '') {

				// Segments
				if (false === strpos($urlParam,$_PARAM_CHAR)) {

					$tmpParam = str_replace('menu',$route,$urlParam);

					$title[] =  rtrim($tmpParam , '/');

					$newUrl .= $tmpParam . '/';

				// Internal to external parameter conversion
				}
				else {

					$bits = explode($_PARAM_CHAR,$urlParam);

					shAddToGETVarsList($bits[0], stripslashes(urldecode($bits[1])));
				}
			}
		}

		// May 23, 2013 - Line below commented to avoid duplicates for menu links
		// One core link and one with the url=menu param
		// shAddToGETVarsList('url','menu');

		shRemoveFromGETVarsList('url');
	}
}

if(isset($page)) {

	shAddToGETVarsList('page',$page);
}

// Home page - there's no query string
if((isset($Itemid) && $Itemid != '')) {

	shRemoveFromGETVarsList('Itemid');
}
else {

	unset($shGETVars['Itemid']);
}

shRemoveFromGETVarsList('option');

shRemoveFromGETVarsList('lang');

unset($shGETVars['view']); // This param is not required for JReviews

//shRemoveFromGETVarsList('dir');

// ------------------  standard plugin finalize function - don't change ---------------------------

if ($dosef) {

   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString,
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
      (isset($shLangName) ? @$shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------