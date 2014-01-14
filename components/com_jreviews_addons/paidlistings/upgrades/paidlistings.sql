--
-- Table structure for table `#__jreviews_paid_account`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_account` (
  `account_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `business` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `country` varchar(255) NOT NULL,
  `tax_id` varchar(255) NOT NULL,
  PRIMARY KEY  (`account_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_paid_emails`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_emails` (
  `email_id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `trigger` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `admin_emails` text NOT NULL,
  `state` tinyint(1) NOT NULL default '0',
  `ordering` int(3) NOT NULL,
  PRIMARY KEY  (`email_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__jreviews_paid_emails`
--

INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(2, 'Admin Order Processed', 'Triggered when a payment is received', 'admin_order_processed', 'Paid listing order #{order_id} was processed', 'A payment attempt was made for order #{order_id}. Below you''ll find the complete order and transaction details:\n\nUser: {user_name}\nTitle: {listing_title}\nUrl: {listing_url}\nAmount: {order_amount}\nPlan: {plan_name}\n\nTransaction Details\n\n{txn_array}\n', '', 0, 2);
INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(3, 'User Order Placed', 'Triggered when an order is first placed', 'user_order_placed', 'Your order confirmation for: {listing_title}', 'Dear {user_name},\n\nThank you for submitting your listing, {listing_title}, to our website. An order was created for this paid listing with Order#{order_id}. However, you still need to complete the payment for this order before the listing is published on the site.\n\nBelow the complete details of your order:\n\nListing: {listing_title}\nUrl: {listing_url}\nAmount: {order_amount}\nPlan: {plan_name} - {plan_description}\nOrder expires: {order_expires}\nOrder amount:{order_amount}\n\nThank you,\n\nThe Administration\n{site_url}', '', 0, 3);
INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(4, 'User Order Processed', 'Triggered when payment handler notifies script of payment results', 'user_order_processed', 'Payment received for: {listing_title}', 'Dear {user_name},\n\nWe have received your payment for the following order #{order_id} and your listing will now be published.\n\nListing: {listing_title}\nUrl: {listing_url}\nAmount: {order_amount}\nPlan: {plan_name} - {plan_description}\nOrder expires: {order_expires}\nOrder amount:{order_amount}\n\nThank you,\n\nThe Administration\n{site_url}', '', 0, 4);
INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(1, 'Admin Order Placed', 'Triggered when an order is first placed', 'admin_order_placed', 'New paid listing order #{order_id} - {order_amount}', 'A new order has been received for a paid listing. Below you''ll find the complete details for this order.\n\nUser: {user_name}\nTitle: {listing_title}\nUrl: {listing_url}\nAmount: {order_amount}\nPlan: {plan_name}\n', '', 0, 1);
INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(6, 'User Order Expiration Notice 1', 'This is the first expiration notice sent before order expiration.', 'user_order_expiration1', 'Expiring soon: {listing_title}', 'Dear {user_name},\n\nWe wanted to remind you that your listing, {listing_title}, is expiring soon. Please head over to {site_url} and renew the listing in the MyAccount page.\n\nBest regards,\n\nThe Administration\n{site_url}', '', 0, 5);
INSERT INTO `#__jreviews_paid_emails` (`email_id`, `name`, `description`, `trigger`, `subject`, `body`, `admin_emails`, `state`, `ordering`) VALUES(7, 'User Order Expiration Notice 2', 'This is the second expiration notice sent before order expiration.', 'user_order_expiration2', 'Final expiration notice for: {listing_title}', 'Dear {user_name},\n\nWe wanted to notify you one last time that your listing, {listing_title}, is about to expire. Please head over to {site_url} and renew the listing in the MyAccount page.\n\nBest regards,\n\nThe Administration\n{site_url}', '', 0, 6);

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_paid_handlers`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_handlers` (
  `handler_id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `plugin_file` varchar(50) NOT NULL,
  `theme_file` varchar(50) NOT NULL,
  `settings` text NOT NULL,
  `subscriptions` int(1) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ordering` int(11) NOT NULL,
  PRIMARY KEY  (`handler_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__jreviews_paid_handlers`
--

INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(1, 'Paypal', 'handler_paypal', 'handler_paypal', '{"handler_email":"","sandbox_email":"","button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/paypal.gif"}', 1, 0, 1);
INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(2, '2Checkout', 'handler_2checkout', 'handler_2checkout', '{"sid":"","secret_word":"","button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/2checkout.jpg"}', 0, 0, 2);
INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(3, 'Offline', 'handler_offline', 'handler_offline', '{"button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/offline.jpg"}', 0, 0, 3);
INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(4, 'Authorize.net', 'handler_authorize_net', 'handler_authorize_net', '{"test_gateway":"1","api_login":"","transaction_key":"","secret_word":"","button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/authorize-net.gif"}', 0, 0, 4);
INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(5, 'JomSocial Points', 'handler_jomsocial', 'handler_jomsocial', '{"exchange_rate":"1","display_zero":"1","button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/jomsocial.jpg"}', 0, 0, 5);
INSERT INTO `#__jreviews_paid_handlers` (`handler_id`, `name`, `plugin_file`, `theme_file`, `settings`, `subscriptions`, `state`, `ordering`) VALUES(6, 'AlphaUserPoints', 'handler_alphauserpoints', 'handler_alphauserpoints', '{"exchange_rate":"1","display_zero":"1","button":"\\/components\\/com_jreviews_addons\\/paidlistings\\/images\\/alphauserpoints.jpg"}', 0, 0, 6);

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_paid_orders`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_orders` (
  `order_id` int(11) NOT NULL auto_increment,
  `order_id_renewal` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_amount` DECIMAL( 8, 2 ) NOT NULL,
  `order_discount` DECIMAL( 8, 2 ) NOT NULL,
  `order_tax` DECIMAL( 8, 2 ) NOT NULL,
  `coupon_name` VARCHAR( 50 ) NOT NULL,
  `order_created` datetime NOT NULL,
  `order_renewal` DATE NOT NULL,
  `order_expires` DATE NOT NULL,
  `order_never_expires` tinyint(1) NOT NULL COMMENT 'Overrides expiration date',
  `plan_id` int(11) NOT NULL,
  `plan_type` int(1) NOT NULL,
  `payment_type` tinyint(1) NOT NULL,
  `plan_info` text NOT NULL,
  `plan_updated` datetime NOT NULL,
  `listing_info` text NOT NULL,
  `handler_id` int(11) NOT NULL,
  `order_status` enum('Incomplete','Pending','Processing','Complete','Cancelled','Fraud','Failed') NOT NULL,
  `order_active` tinyint(1) NOT NULL default '0',
  `order_notify1` tinyint(1) NOT NULL,
  `order_notify2` tinyint(1) NOT NULL,
  `admin_notes` text NOT NULL,
  PRIMARY KEY  (`order_id`),
  KEY (`order_id_renewal`),
  KEY (`listing_id`),
  KEY (`user_id`),
  KEY (`plan_id`),
  KEY (`plan_type`),
  KEY (`plan_updated`),
  KEY (`payment_type`),
  KEY (`order_status`),
  KEY (`order_active`),
  KEY (`order_notify1`),
  KEY (`order_notify2`),
  KEY (`order_created`),
  KEY (`order_renewal`),
  KEY (`order_expires`),
  KEY (`order_never_expires`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_paid_listing_fields`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_listing_fields` (
  `listing_id` int(11) NOT NULL,
  `fields` longtext NOT NULL,
  PRIMARY KEY (`listing_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_paid_plans`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_plans` (
  `plan_id` int(11) NOT NULL auto_increment,
  `plan_name` varchar(255) NOT NULL,
  `plan_price` decimal(8,2) NOT NULL,
  `payment_type` int(1) NOT NULL,
  `plan_type` tinyint(1) NOT NULL,
  `plan_upgrade_exclusive` tinyint(1) NOT NULL,
  `plan_featured` tinyint(1) NOT NULL,
  `plan_array` text NOT NULL,
  `plan_default` tinyint(1) NOT NULL default '0',
  `plan_state` tinyint(1) NOT NULL,
  `plan_updated` datetime NOT NULL,
  `photo` int(11) unsigned DEFAULT NULL,
  `video` int(11) unsigned DEFAULT NULL,
  `attachment` int(11) unsigned DEFAULT NULL,
  `audio` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY  (`plan_id`),
  KEY (`plan_default`),
  KEY (`payment_type`),
  KEY (`plan_type`),
  KEY (`plan_upgrade_exclusive`),
  KEY (`plan_state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_paid_plans_categories`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_plans_categories` (
  `plan_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  PRIMARY KEY  (`plan_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_paid_txn_logs`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_txn_logs` (
  `log_id` int(11) NOT NULL auto_increment,
  `txn_id` varchar(255) NOT NULL,
  `txn_date` datetime NOT NULL,
  `handler_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `txn_data` text NOT NULL,
  `txn_success` tinyint(1) NOT NULL,
  `log_note` text NOT NULL,
  PRIMARY KEY  (`log_id`),
  KEY (`txn_id`),
  KEY (`order_id`),
  KEY (`txn_success`),
  KEY (`txn_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jreviews_paid_coupons` (
  `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_name` varchar(50) NOT NULL,
  `coupon_discount` int(3) NOT NULL,
  `coupon_starts` datetime NOT NULL,
  `coupon_ends` datetime NOT NULL,
  `coupon_users` text NOT NULL,
  `coupon_plans` text NOT NULL,
  `coupon_categories` text NOT NULL,
  `coupon_count` int(11) NOT NULL,
  `coupon_count_type` enum('user','global') NOT NULL,
  `coupon_renewals_only` tinyint(1) NOT NULL,
  `coupon_state` tinyint(1) NOT NULL,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `coupon_name` (`coupon_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

