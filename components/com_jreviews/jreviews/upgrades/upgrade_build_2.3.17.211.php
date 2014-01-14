<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$db_name = cmsFramework::getConfig('db');
$db_prefix = cmsFramework::getConfig('dbprefix');

/**
 * Add index to #__categories for improved performance of queries with parent_id conditions
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__categories') ."'
";

$this->_db->setQuery($query);

$indexes = method_exists($this->_db,'loadColumn') ? $this->_db->loadColumn() : $this->_db->loadResultArray();

// Now create new indexes
if(!in_array('jr_parent_id',$indexes)) {
	$query = "
		ALTER TABLE `#__categories` ADD INDEX  `jr_parent_id` ( `parent_id` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

