CREATE TABLE IF NOT EXISTS `#__jreviews_media` (
  `media_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_type` enum('video','photo','audio','attachment') DEFAULT NULL,
  `listing_id` int(10) unsigned NOT NULL DEFAULT '0',
  `review_id` int(10) unsigned NOT NULL DEFAULT '0',
  `extension` varchar(100) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `file_extension` varchar(4) NOT NULL DEFAULT '',
  `rel_path` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Media duration',
  `filesize` int(11) unsigned NOT NULL DEFAULT '0',
  `media_info` text NOT NULL,
  `embed` varchar(50) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Use INET_ATON() and INET_NTOA() to convert back and forth',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `main_media` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `metadata` blob NOT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `likes_up` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `likes_total` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `likes_rank` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ordering` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`media_id`),
  KEY `count` (`media_type`,`approved`,`published`),
  KEY `listing` (`listing_id`,`extension`,`media_id`,`media_type`,`published`,`approved`,`main_media`),
  KEY `review` (`review_id`,`extension`,`media_id`,`media_type`,`published`,`approved`,`main_media`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_media_encoding` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) NOT NULL DEFAULT '',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` enum('processing','cancelled','finished','waiting','failed') NOT NULL DEFAULT 'waiting',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `response` text NOT NULL,
  `notifications` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `job` (`job_id`,`media_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_media_likes` (
  `like_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `vote` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`like_id`),
  KEY `vote` (`vote`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_inquiry` (
  `inquiry_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) unsigned DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `from_name` varchar(128) NOT NULL DEFAULT '',
  `from_email` varchar(128) NOT NULL DEFAULT '',
  `to_email` varchar(128) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Sender user id',
  `message` mediumtext NOT NULL,
  `extra_fields` mediumtext NOT NULL,
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Use INET_ATON() and INET_NTOA() to convert back and forth',
  PRIMARY KEY (`inquiry_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_registration` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `listing_id` int(11) unsigned NOT NULL DEFAULT '0',
  `review_id` int(11) unsigned NOT NULL DEFAULT '0',
  `discussion_id` int(11) unsigned NOT NULL DEFAULT '0',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `session_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

--

ALTER TABLE `#__jreviews_config` CHANGE `id` `id` VARCHAR(255);