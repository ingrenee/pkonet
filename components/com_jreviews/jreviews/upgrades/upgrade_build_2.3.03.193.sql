CREATE TABLE IF NOT EXISTS `#__jreviews_reviewer_ranks` (
  `user_id` int(11) NOT NULL,
  `reviews` int(11) NOT NULL,
  `votes_percent_helpful` decimal(5,4) NOT NULL,
  `votes_total` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM;