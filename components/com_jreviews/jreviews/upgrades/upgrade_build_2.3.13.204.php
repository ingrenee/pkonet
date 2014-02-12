<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$db_name = cmsFramework::getConfig('db');
$db_prefix = cmsFramework::getConfig('dbprefix');

/**
 * Modify and add indexes for #__jreviews_comments
 */
// Create copy of content table in case something goes wrong
$query = "
	DROP TABLE IF EXISTS `#__jreviews_comments_copy`;
";
$this->_db->setQuery($query);
$this->_db->query();

$query = "
	CREATE TABLE `#__jreviews_comments_copy` LIKE `#__jreviews_comments`;
";
$this->_db->setQuery($query);
$this->_db->query();

$query = "
	INSERT `#__jreviews_comments_copy` SELECT * FROM `#__jreviews_comments`;
";
$this->_db->setQuery($query);
$this->_db->query();

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

// First modified existing indexes
if(in_array('created',$indexes)) {
	$query = "
		DROP INDEX `created` ON #__jreviews_comments;
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `created` ( `pid`, `created` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

if(in_array('modified',$indexes)) {
	$query = "
		DROP INDEX `modified` ON #__jreviews_comments;
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `modified` ( `pid`, `modified`, `created` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

if(in_array('userid',$indexes)) {
	$query = "
		DROP INDEX `userid` ON #__jreviews_comments;
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `userid` ( `userid`, `published` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

// Now create new indexes
if(!in_array('votes_helpful',$indexes)) {
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes_helpful` ( `pid`,`vote_helpful` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

if(!in_array('votes',$indexes)) {
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes` ( `userid` ,  `published` ,  `vote_helpful` ,  `vote_total` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

if(!in_array('posts',$indexes)) {
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `posts` ( `pid`,`posts` );
	";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(500000);
}

/**
 * Add #__content indexes, first backup table
 */

// Create copy of content table in case something goes wrong
$query = "
	DROP TABLE IF EXISTS `#__content_copy`;
";
$this->_db->setQuery($query);
$this->_db->query();

$query = "
	CREATE TABLE `#__content_copy` LIKE `#__content`;
";
$this->_db->setQuery($query);
$this->_db->query();

$query = "
	INSERT `#__content_copy` SELECT * FROM `#__content`;
";
$this->_db->setQuery($query);
$this->_db->query();

$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__content') ."'
";

$this->_db->setQuery($query);

$indexes = method_exists($this->_db,'loadColumn') ? $this->_db->loadColumn() : $this->_db->loadResultArray();

# Add core table indexes for JReviews
if(!in_array('jr_created',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX  `jr_created` (  `created` );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}

if(!in_array('jr_modified',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX  `jr_modified` (  `modified` );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}

if(!in_array('jr_hits',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX  `jr_hits` (  `hits` );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}

if(!in_array('jr_ordering',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX  `jr_ordering` (  `ordering` );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}

if(!in_array('jr_title',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX  `jr_title` (  `title` ( 3 ) );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}

if(!in_array('jr_listing_count',$indexes)) {
	$query = "ALTER TABLE `#__content` ADD INDEX `jr_listing_count` ( `catid` , `state` , `access` , `publish_up` , `publish_down` );";
	$this->_db->setQuery($query);
	$this->_db->query();
	usleep(1000000);
}


