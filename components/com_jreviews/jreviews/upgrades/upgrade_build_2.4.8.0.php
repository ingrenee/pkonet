<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$db_name = cmsFramework::getConfig('db');
$db_prefix = cmsFramework::getConfig('dbprefix');

# Add related field columns to fields table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_categories'
    AND COLUMN_NAME='page_title'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_categories`
	ADD COLUMN `page_title` varchar(255) NOT NULL,
	ADD COLUMN `title_override` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `desc_override` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

