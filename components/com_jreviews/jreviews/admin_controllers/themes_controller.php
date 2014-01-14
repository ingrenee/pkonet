<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThemesController extends MyController
{
	var $uses = array('directory','category','jreviews_category');

	var $helpers = array('html','form','admin/paginator');

    var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function index()
    {
        return $this->categories();
	}

	function saveCategory()
    {
		$response = array();

        $tmpl = $this->data['tmpl'];

		$catids = array();

		foreach ($tmpl as $catid=>$value)
		{

			$category = $this->JreviewsCategory->findRow(array('conditions'=>array('JreviewsCategory.id = ' . $catid,'JreviewsCategory.option = "com_content"')));

			$tmpl = $value['name'];

			$suffix = $value['suffix'];

			if ($category['JreviewsCategory']['tmpl'] != $tmpl || $category['JreviewsCategory']['tmpl_suffix'] != $suffix )
			{
				$catids[] = $catid;

				$query = "
					UPDATE
						#__jreviews_categories
					SET
						tmpl = ". $this->Quote($tmpl) .", tmpl_suffix = " . $this->Quote($suffix) . "
					WHERE
						id = " . (int) $catid . " AND `option` = 'com_content'";

				if (!$this->JreviewsCategory->query($query))
                {
					return false;
				}

			}
		}

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

		$page = $this->categories();

		return true;
	}

    function categories()
    {
        $this->action = 'categories';

        $cat_alias = Sanitize::getString($this->params,'cat_alias');

        $lists = array();

        $total = 0;

        $sections = $this->Category->getChildren(1 /*ROOT*/, 1 /*Depth*/);

        $rows = $this->Category->getReviewCategories($cat_alias, $this->offset, $this->limit, $total);

        $this->set(array(
            'rows'=>$rows,
            'sections'=>$sections,
            'sectionid'=>$cat_alias,
            'pagination'=>array('total'=>$total)
        ));

        return $this->render();
    }

}
