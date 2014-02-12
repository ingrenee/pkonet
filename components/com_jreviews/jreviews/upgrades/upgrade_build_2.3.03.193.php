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
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_fields'
    AND COLUMN_NAME='control_field'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_fields` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add related field columns to groups table

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_groups'
    AND COLUMN_NAME='control_field'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_groups` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}

# Add related field columns to fieldoptions table

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_fieldoptions'
    AND COLUMN_NAME='control_field'";

$this->_db->setQuery($query);

$exists = $this->_db->loadResult();

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_fieldoptions` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $this->_db->setQuery($query);

  $this->_db->query();
}
