<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoryModel extends MyModel
{
    var $name = 'Category';

    var $useTable = '#__categories AS Category';

    var $primaryKey = 'Category.cat_id';

    var $realKey = 'id';

    var $fields = array(
        'cat_id'=>'Category.id AS `Category.cat_id`',
                    'Category.title AS `Category.title`',
                    'Category.alias AS `Category.slug`',
                    'Category.level AS `Category.level`',
                    'Category.params AS `Category.params`',
                    'Category.parent_id AS `Category.parent_id`',
                    'JreviewsCategory.criteriaid AS `Category.criteria_id`',
                    'JreviewsCategory.tmpl AS `Category.tmpl`',
                    'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`',
                    'Directory.id AS `Directory.dir_id`',
                    'Directory.desc AS `Directory.title`',
                    'Directory.title AS `Directory.slug`',
                    'ListingType.config AS `ListingType.config`'
    );

    var $joins = array(
        'INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = "com_content"',
        'LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id',
        'LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id'
    );

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks if core category is setup for jReviews
     */
    function isJreviewsCategory($cat_id)
    {
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories AS JreviewCategory
            WHERE
                JreviewCategory.id = " . (int) $cat_id . "
                AND
                JreviewCategory.option = 'com_content'
                AND
                JreviewCategory.criteriaid > 0
        ";


		return $this->query($query, 'loadResult');
    }

    function afterFind($results)
    {
        $Menu = ClassRegistry::getClass('MenuModel');

        $results = $Menu->addMenuCategory($results);

        foreach($results AS $key=>$result)
        {
            isset($result['ListingType']['config']) and $results[$key]['ListingType']['config'] = json_decode($result['ListingType']['config'],true);
            !is_array($results[$key]['ListingType']['config']) and $results[$key]['ListingType']['config'] = array();
        }

        return $results;
    }

    function afterSave($ret)
    {
        clearCache('','__data');

        clearCache('','views');
    }

    /***********************************************************
    * Joomla 16 specific class methods
    ************************************************************/

    /**
    * Recursive method to generate array for jsTree implementation
    *
    */
    function makeParentChildRelations(&$inArray, &$outArray, $currentParentId = 1)
    {
        if(!is_array($inArray)) {
            return;
        }

        if(!is_array($outArray)) {
            return;
        }

        foreach($inArray as $key => $item)
        {
            $item = (array) $item;
            $item['attr'] = array('id'=>$item['value']);
            $item['data'] = $item['text'];
            if($item['parent_id'] == $currentParentId)
            {
                $item['children'] = array();
                CategoryModel::makeParentChildRelations($inArray, $item['children'], $item['value']);
                if(empty($item['children'])) unset($item['children']);
                $outArray[] = $item;
            }
        }
    }

    /**
    * Returns array of cat id/title value pairs given a listing type used for creating a tree list
    * Used in search and listing controllers
    *
    */
    function getCategoryList($options = array())
    {
        $Access = Configure::read('JreviewsSystem.Access');

        $options = array_merge(array(
                'indent'=>true,
                'disabled'=>true
            ),
            $options
        );

        $fields = array(
                'Category.id AS value',
                'Category.level AS level',
                'Category.parent_id AS parent_id',
                'JreviewCategory.criteriaid'
        );

		// Add listing type config to query
		$listing_type_join = '';

		if(isset($options['listing_type'])) {
			$fields[] = 'ListingType.config';
			$listing_type_join = "
				LEFT JOIN
					#__jreviews_criteria AS ListingType ON JreviewCategory.criteriaid = ListingType.id
			";
		}

		unset($options['listing_type']);

        Sanitize::getBool($options,'disabled') and $fields[] = 'IF(JreviewCategory.criteriaid = 0,1,0) AS disabled';

        $fields[] = Sanitize::getBool($options,'indent')
            ?
            "CONCAT(REPEAT('- ', IF(Category.level>0,Category.level - 1,1)), Category.title) AS text"
            :
            "Category.title AS text"
        ;

        # Category conditions
        $cat_condition = array();
        isset($options['cat_id']) and !empty($options['cat_id']) and $cat_condition[] = "Category.id IN ({$options['cat_id']})";
        isset($options['parent_id']) and !empty($options['parent_id']) and $cat_condition[] = "Category.parent_id IN ({$options['parent_id']})";


		$query = "
			SELECT
                " . implode(',',$fields) . "
			FROM
				#__categories AS ParentCategory,
				#__categories AS Category

			LEFT JOIN
				#__jreviews_categories AS JreviewCategory ON Category.id = JreviewCategory.id AND JreviewCategory.option = 'com_content'

			" . $listing_type_join . "

			WHERE
				Category.published = 1
				AND Category.extension = 'com_content'
                " . (isset($options['level']) && $options['level'] == 1 ? " AND Category.parent_id = 1" : '' ) /* Speeds up top level category list query with thousands of categories */. "
				AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
				AND Category.extension = 'com_content'
				AND ParentCategory.access IN ( {$Access->getAccessLevels()}  )

                " .
                (isset($options['level']) && !empty($options['level']) ? " AND Category.level = {$options['level']} " : '' )
                .
                (!empty($cat_condition) ? " AND (" . implode(" OR ", $cat_condition) . ')' : '')
                .
                (isset($options['type_id']) && !empty($options['type_id']) ? " AND JreviewCategory.criteriaid IN (" . ( is_array($options['type_id']) ? implode(',',$options['type_id']) : $options['type_id'] ) . ")" : '' )
                .
                (isset($options['dir_id']) && !empty($options['dir_id']) ? " AND JreviewCategory.dirid IN (" . cleanIntegerCommaList($options['dir_id']). ")" : '' )
                .
                (isset($options['conditions']) ? " AND (" . implode(" AND " , $options['conditions']) . ")" : '')
                . "

			GROUP BY
				Category.id

			HAVING

				JreviewCategory.criteriaid >= 0

			ORDER BY
				Category.lft
		";

/*
        $query = "
            SELECT
                " . implode(',',$fields) . "
            FROM
                #__categories AS Category
            LEFT JOIN
                #__categories AS ParentCategory ON Category.lft <= ParentCategory.lft AND Category.rgt >= ParentCategory.rgt
            INNER JOIN
                #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
			" . $listing_type_join . "
            WHERE
                Category.extension = 'com_content'
                " . (isset($options['level']) && $options['level'] == 1 ? " AND Category.parent_id = 1" : '' ) /* Speeds up top level category list query with thousands of categories . "
                AND Category.published = 1
                AND ParentCategory.access IN ( {$Access->getAccessLevels()} )
                " .
                (isset($options['level']) && !empty($options['level']) ? " AND Category.level = {$options['level']} " : '' )
                .
                (!empty($cat_condition) ? " AND (" . implode(" OR ", $cat_condition) . ')' : '')
                .
                (isset($options['type_id']) && !empty($options['type_id']) ? " AND JreviewCategory.criteriaid IN (" . ( is_array($options['type_id']) ? implode(',',$options['type_id']) : $options['type_id'] ) . ")" : '' )
                .
                (isset($options['dir_id']) && !empty($options['dir_id']) ? " AND JreviewCategory.dirid IN (" . cleanIntegerCommaList($options['dir_id']). ")" : '' )
                .
                (isset($options['conditions']) ? " AND (" . implode(" AND " , $options['conditions']) . ")" : '')
                . "
            GROUP BY
                Category.id
            ORDER
                BY Category.lft
        ";
*/

		$rows = $this->query($query, 'loadObjectList', 'value');

        if(isset($options['jstree']) && $options['jstree'])
        {
            $nodes = array();

            $first = current($rows);

            CategoryModel::makeParentChildRelations($rows, $nodes);

            return cmsFramework::jsonResponse($nodes);
        }

        return $rows;
    }

    /**
    * Category Manager, Theme Manager
    *
    * @param mixed $cat_id
    * @param mixed $offset
    * @param mixed $limit
    * @param mixed $total
    */
    function getReviewCategories($alias, $offset=0, $limit, &$total)
    {
        $where = $alias ? " AND Category.path LIKE '{$alias}%'" : '';

        // get the total number of records
        $query = "
            SELECT
                COUNT(*)
            FROM
                `#__jreviews_categories` AS jrcat
            LEFT JOIN
                #__categories AS Category ON Category.id = jrcat.id
            WHERE
                Category.extension = 'com_content'
                AND jrcat.option = 'com_content'"
            . $where
        ;

		$total = $this->query($query, 'loadResult');

        $query = "
            SELECT
                Category.id AS value, Category.title AS text, Category.level AS level,
                Category.metadesc, Category.metakey, Category.metadata,
                Directory.desc AS dir_title, ListingType.title AS listing_type_title,
                JreviewCategory.*
            FROM
                #__categories AS Category
                    INNER JOIN #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
                    LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewCategory.criteriaid = ListingType.id
                    LEFT JOIN #__jreviews_directories AS Directory ON JreviewCategory.dirid = Directory.id
                ,#__categories AS parent
            WHERE
                Category.extension = 'com_content'
                AND Category.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id =  1
                " . $where . "
            ORDER
                BY Category.lft
            LIMIT {$offset}, {$limit}
        ";

		$rows = $this->query($query, 'loadObjectList');

        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }

    /**
    * Used in category manager for new category setup
    *
    */
    function getReviewCategoryIds()
    {
        $query = "
            SELECT
                id AS cat_id
            FROM
                #__jreviews_categories
            WHERE
                `option` = 'com_content'
        ";

		$rows = $this->query($query, 'loadColumn');

        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }

    /**
    * Used in category manager to get a list of categories not setup for JReviews
    *
    */
    function getNonReviewCategories()
    {
        $query = "
            SELECT
                node.id AS value, node.title AS text, node.level AS level
            FROM
                #__categories AS node,
                #__categories AS parent
            WHERE
                node.extension = 'com_content'
                AND node.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id = 1
            ORDER
                BY node.lft
        ";

		$rows = $this->query($query, 'loadObjectList');

        return $rows;
    }

    /*
    * Used in category manager to show parent cats for filtering
    *
    * @param mixed $parent_id
    * @param mixed $depth
    */
    function getChildren($parent_id = 1, $depth = null)
    {
        $query = "
            SELECT
                node.id, node.alias AS value, node.title AS text
            FROM
                #__categories AS node,
                #__categories AS parent
            WHERE
                node.extension = 'com_content'
                AND node.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id = {$parent_id}
                ". ($depth > 0 ? "AND node.level BETWEEN (parent.level + 1) AND (parent.level + " . ($depth) . ")" : '') . "
            ORDER
                BY node.lft
        ";

		return $this->query($query, 'loadObjectList', 'id');
    }

    /**
    * Directories Controller, Categories Controller
    * Generate the category tree array
    */
    function findTree($options = array())
    {
        $fields = array();
        $joins = array();
        $conditions = array();
        $group = array();
        $order = array();
        $having = array();

        $Config = Configure::read('JreviewsSystem.Config');
        $Access = Configure::read('JreviewsSystem.Access');

        // Force straight join for query optimization
        $this->fields['cat_id'] = 'STRAIGHT_JOIN Category.id AS `Category.cat_id`';

        $fields[] = 'COUNT(Listing.id) AS `Category.listing_count`';

        $conditions[] = 'Category.published = 1';

//        $conditions[] = "Category.access IN ( {$Access->getAccessLevels()} )";

		$conditions[] = "ParentCategory.access IN ( {$Access->getAccessLevels()} )";

		$conditions[] = 'Category.extension = "com_content"';

		isset($options['parent_id']) and !empty($options['parent_id']) and $conditions[] = 'ParentCategory.id = ' . $options['parent_id'];

		isset($options['level']) and !empty($options['level']) and $conditions[] = 'Category.level <= '. $options['level'];

		isset($options['dir_id']) and !empty($options['dir_id']) and $conditions[] = 'Directory.id IN ('.$options['dir_id'].')';

		isset($options['cat_id']) and !empty($options['cat_id']) and $conditions[] = "(Category.id = {$options['cat_id']} OR Category.parent_id = {$options['cat_id']})";

        array_unshift(
				$this->joins,
				'LEFT JOIN #__content AS Listing ON ParentCategory.id = Listing.catid AND ' . ($Access->isPublisher()		?
						'Listing.state >= 0'
						:
						'(Listing.state = 1'
//							. ' AND Listing.access IN (' . $Access->getAccessId() . ')'
							. ' AND ( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" ) '
							. ' AND ( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" ))'
		));


		array_unshift($this->joins,'LEFT JOIN #__categories AS ParentCategory ON Category.lft <= ParentCategory.lft AND Category.rgt >= ParentCategory.rgt');

        $group[] = 'Category.id';
        $order[] = 'Directory.desc';
        $order[] = 'Category.lft';

        if(Sanitize::getBool($Config,'dir_category_hide_empty')) {

            $having = array('`Category.listing_count` > 0');
        }

        $queryData = array(
            'fields'=>$fields,
            'conditions'=>$conditions,
            'joins'=>$joins,
            'group'=>$group,
            'order'=>$order,
            'having'=>$having
        );

        if($limit = Sanitize::getInt($options,'limit',null)) {

            $queryData['limit'] = $limit;
        }

        if($offset = Sanitize::getInt($options,'offset',null)) {

            $queryData['offset'] = $offset;
        }

        $rows = $this->findAll($queryData,array());

        if(isset($options['menu_id']) || isset($options['pad']))
        {
            $results = array();
            S2App::import('Model','menu','jreviews');
            $Menu = ClassRegistry::getClass('MenuModel');

            foreach($rows AS $key=>$row)
            {
                $row['Category']['level'] > 1 and $rows[$key]['Category']['title'] = str_repeat(Sanitize::getVar($options,'pad_char','&nbsp;'),$row['Category']['level']-1) . $row['Category']['title'];
                if(isset($options['menu_id']))
                {
                    $rows[$key]['Category']['menu_id'] = $Menu->getCategory(array('cat_id'=>$row['Category']['cat_id'],'dir_id'=>$row['Directory']['dir_id']));
                    $rows[$key]['Directory']['menu_id'] = $Menu->getDir($row['Directory']['dir_id']);
                    $results[$row['Directory']['dir_id']][$row['Category']['cat_id']] = $rows[$key];
                }
            }
            unset($Config);
            if(!empty($results)) return $results;
        }

        unset($Config);
        return $rows;
    }

    function findParents($cat_id)
    {
        $query = "
        (SELECT
                ParentCategory.id AS `Category.cat_id`,
                ParentCategory.lft AS `Category.lft`,
                ParentCategory.title AS `Category.title`,
                IF(JreviewsCategory.page_title <> '',JreviewsCategory.page_title,ParentCategory.title) AS `Category.title_seo`,
                JreviewsCategory.title_override AS `Category.title_override`,
                ParentCategory.alias AS `Category.slug`,
                ParentCategory.level AS `Category.level`,
                ParentCategory.published AS `Category.published`,
                ParentCategory.access AS `Category.access`,
                ParentCategory.params AS `Category.params`,
                ParentCategory.parent_id AS `Category.parent_id`,
                ParentCategory.metadesc AS `Category.metadesc`,
                ParentCategory.metakey AS `Category.metakey`,
                IF(ParentCategory.metadesc <> '' AND JreviewsCategory.desc_override =1,ParentCategory.metadesc,ParentCategory.description) AS `Category.description`,
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                JreviewsCategory.dirid AS `Directory.dir_id`,
                Directory.title AS `Directory.slug`,
                ListingType.config AS `ListingType.config`
            FROM
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCategory.criteriaid
            WHERE
                ParentCategory.id = " . (int) $cat_id . "
        )
        UNION
        (SELECT
                ParentCategory.id AS `Category.cat_id`,
                ParentCategory.lft AS `Category.lft`,
                ParentCategory.title AS `Category.title`,
                IF(JreviewsCategory.page_title <> '',JreviewsCategory.page_title,ParentCategory.title) AS `Category.title_seo`,
                JreviewsCategory.title_override AS `Category.title_override`,
                ParentCategory.alias AS `Category.slug`,
                ParentCategory.level AS `Category.level`,
                ParentCategory.published AS `Category.published`,
                ParentCategory.access AS `Category.access`,
                ParentCategory.params AS `Category.params`,
                ParentCategory.parent_id AS `Category.parent_id`,
                ParentCategory.metadesc AS `Category.metadesc`,
                ParentCategory.metakey AS `Category.metakey`,
                IF(ParentCategory.metadesc <> '' AND JreviewsCategory.desc_override =1,ParentCategory.metadesc,ParentCategory.description) AS `Category.description`,
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                JreviewsCategory.dirid AS `Directory.dir_id`,
                Directory.title AS `Directory.slug`,
                ListingType.config AS `ListingType.config`
            FROM
                #__categories AS Category,
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCategory.criteriaid
            WHERE
                Category.published = 1
                AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                AND Category.id = " . (int) $cat_id . "
                AND ParentCategory.parent_id > 0
        )
        ORDER BY
            `Category.lft`
        ";

		$rows = $this->query($query, 'loadObjectList');

		$rows = $this->__reformatArray($rows);

		return $rows;
    }

    function isLeaf($cat_id)
    {
        $query = "
            SELECT
                count(*)
            FROM
                #__categories AS Category
            WHERE
                Category.parent_id = " . (int) $cat_id . "
                AND
                Category.extension = 'com_content'
        ";

		return !$this->query($query, 'loadResult');
    }
}
