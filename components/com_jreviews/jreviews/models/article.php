<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ArticleModel extends MyModel  {

	var $name = 'Article';

	var $useTable = '#__content AS Article';

	var $primaryKey = 'Article.article_id';

	var $realKey = 'id';

	var $fields = array(
		'Article.id AS `Article.article_id`',
		'Article.title AS `Article.title`',
		'Article.introtext AS `Article.summary`',
		'Article.fulltext AS `Article.description`',
		'Article.catid AS `Article.cat_id`',
        'Article.alias AS `Article.slug`',
        'Category.alias AS `Category.slug`'
	);

	var $joins = array(
        "LEFT JOIN #__categories AS Category ON Article.catid = Category.id"
	);

	var $conditions = array();
	var $limit;
	var $offset;
    var $order = array();

	function __construct()
    {
		parent::__construct();
	}

	function articleUrl($listing)
    {
		return $this->Routes->content('',$listing,array(),'',false);
	}
}
