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
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_listing_totals'
    AND COLUMN_NAME='media_count_user'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_listing_totals`
	ADD COLUMN `media_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `video_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `photo_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `audio_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `attachment_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0;
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}
