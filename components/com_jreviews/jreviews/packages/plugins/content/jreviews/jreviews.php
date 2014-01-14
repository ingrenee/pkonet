<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2011-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( '_JEXEC') or die( 'Direct Access to this location is not allowed.');

# Only run in frontend
if (JFactory::getApplication()->isAdmin()) return;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

if(defined('JPATH_SITE') )
{    
    $root = JPATH_SITE . DS;
}
else
{
    global $mainframe;
    $root = $mainframe->getCfg('absolute_path') . DS;
}

$overrides_path = $root . 'templates' . DS . 'jreviews_overrides' . DS . 'includes' . DS . 'plugins' . DS . 'jreviews.php';
$jreviews_path = $root . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'includes' . DS . 'plugins' . DS . 'jreviews.php';
include_once(file_exists($overrides_path) ? $overrides_path : $jreviews_path); 