<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$db_name = cmsFramework::getConfig('db');
$db_prefix = cmsFramework::getConfig('dbprefix');

# Add media columns to comments table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_comments'
    AND COLUMN_NAME='media_count'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE  `#__jreviews_comments`
	ADD  `media_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `photo_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `audio_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `attachment_count` int(10) unsigned NOT NULL DEFAULT '0';
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add media columns to listings totals table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_listing_totals'
    AND COLUMN_NAME='media_count'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE  `#__jreviews_listing_totals`
	ADD  `media_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `photo_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `audio_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `attachment_count` int(10) unsigned NOT NULL DEFAULT '0';
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add media_id column to reports table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_reports'
    AND COLUMN_NAME='media_id'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE  `#__jreviews_reports`
	ADD  `media_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `review_id`;
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add ipaddress column to content table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_content'
    AND COLUMN_NAME='ipaddress'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_content`
	ADD `ipaddress` INT(10) UNSIGNED NOT NULL DEFAULT 0  AFTER `email`;
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add search column to criteia table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_criteria'
    AND COLUMN_NAME='search'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_criteria`
	ADD `search` tinyint(1) NOT NULL DEFAULT '1';
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

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
if(!in_array('media_count',$indexes)) {
	$queries = array(
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `media_count` (`media_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `video_count` (`video_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `photo_count` (`photo_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `audio_count` (`audio_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `attachment_count` (`attachment_count`);"
	);

	foreach($queries AS $query) {
		$this->_db->setQuery($query);
		$this->_db->query();
		usleep(500000);
	}
}

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
		table_name = '". str_replace('#__',$db_prefix,'#__jreviews_comments') ."'
";

$this->_db->setQuery($query);

$indexes = method_exists($this->_db,'loadColumn') ? $this->_db->loadColumn() : $this->_db->loadResultArray();

// Now create new indexes
if(!in_array('media_count',$indexes)) {
	$queries = array(
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `media_count` (`media_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `video_count` (`video_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `photo_count` (`photo_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `audio_count` (`audio_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `attachment_count` (`attachment_count`);"
	);

	foreach($queries AS $query) {
		$this->_db->setQuery($query);
		$this->_db->query();
		usleep(500000);
	}
}

