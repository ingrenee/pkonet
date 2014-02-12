<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$db_name = cmsFramework::getConfig('db');
$db_prefix = cmsFramework::getConfig('dbprefix');

/**
 * Add index to #__jreviews_listing_totals
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__jreviews_listing_totals') ."'
";

$this->_db->setQuery($query);

$indexes = method_exists($this->_db,'loadColumn') ? $this->_db->loadColumn() : $this->_db->loadResultArray();

// Now create new indexes
if(!in_array('user_rating',$indexes)) {
	$queries = array(
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `user_rating` (  `user_rating` ,  `user_rating_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `editor_rating` (  `editor_rating` ,  `editor_rating_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `user_comment_count` (  `user_comment_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `editor_comment_count` (  `editor_comment_count` );"
	);

	foreach($queries AS $query) {
		$this->_db->setQuery($query);
		$this->_db->query();
		usleep(500000);
	}
}

/**
 * Add index to #__jreviews_directories
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__jreviews_directories') ."'
";

$this->_db->setQuery($query);

$indexes = method_exists($this->_db,'loadColumn') ? $this->_db->loadColumn() : $this->_db->loadResultArray();

// Now create new indexes
if(!in_array('title',$indexes)) {
	$queries = array(
		"ALTER TABLE  `#__jreviews_directories` ADD INDEX `title` ( `title` ( 35 ) );"
	);

	foreach($queries AS $query) {
		$this->_db->setQuery($query);
		$this->_db->query();
		usleep(500000);
	}
}

