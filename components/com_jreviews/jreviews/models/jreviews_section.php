<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsSectionModel extends MyModel  {
	
	var $name = 'JreviewsSection';
	
	var $useTable = '#__jreviews_sections AS JreviewsSection';
			
	var $primaryKey = 'JreviewsSection.sectionid';
	
	var $realKey = 'sectionid';
	
	var $fields = array(
		'JreviewsSection.sectionid AS `JreviewsSection.sectionid`',
		'JreviewsSection.tmpl AS `JreviewsSection.tmpl`',
		'JreviewsSection.tmpl_suffix AS `JreviewsSection.tmpl_suffix`'
	);
	
}