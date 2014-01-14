<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminCriteriasHelper extends S2Object
{

	function createListFromString ($string, $op = '') {
		if ($string == '') {
			return '';
		}
		$list = '';
		$array = explode ("\n",$string);
		foreach ($array as $element) {
			$list .= "<li>$element</li>";
		}
		$list = "<ol>$list</ol>";
		if ($op == 'sum') {
			$list .= "<center><b>Total:".array_sum($array)."</b></center>";
		}
		return $list;
	}

}
?>