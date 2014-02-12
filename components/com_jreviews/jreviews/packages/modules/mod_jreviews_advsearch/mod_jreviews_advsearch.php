<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

$rel_file_path = DS . 'includes' . DS . 'modules' . DS . 'advsearch.php';                                                                         
$overrides_path = JPATH_SITE . DS . 'templates' . DS . 'jreviews_overrides' . $rel_file_path;
$jreviews_path = JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . $rel_file_path;
include(file_exists($overrides_path) ? $overrides_path : $jreviews_path); 
