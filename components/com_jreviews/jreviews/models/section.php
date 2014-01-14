<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class SectionModel extends MyModel  {
	
	var $useTable = '#__sections AS `Section`';
	var $primaryKey = 'Section.section_id';
	var $fields = array(
		'Section.id AS `Section.section_id`',
		'Section.title AS `Section.title`',
        'Section.alias AS `Section.slug`',        
		'Section.image AS `Section.image`',
		'Section.image_position AS `Section.image_position`',
		'Section.description AS `Section.description`',		
		'Section.access AS `Section.access`',
		'Section.published AS `Section.published`',
		'JreviewsSection.tmpl AS `Section.tmpl`',
		'JreviewsSection.tmpl_suffix AS `Section.tmpl_suffix`'
	);

	var $joins = array(
		'LEFT JOIN #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid'
	);
		
	function __construct() 
    {
		parent::__construct();
		
/*		if(getCmsVersion() == CMS_JOOMLA15) {
			// Add listing, category aliases to fields
			$this->fields[] = 'CASE WHEN CHAR_LENGTH(Section.alias) THEN Section.alias ELSE Section.title END AS `Section.slug`';
		} else {
			$this->fields[] = 'Section.name AS `Section.slug`';
		}		*/
	}	
	
	/**
	 * Used in both Admin and Frontend controllers for listing create/edit list
	 */
	function getList($cat_ids = '', $section_id = '', $dir_id = '') 
    {                         
		$cat_ids = cleanIntegerCommaList($cat_ids);
        $section_id = cleanIntegerCommaList($section_id);
        $dir_id = cleanIntegerCommaList($dir_id);
        
        // Get section list
		$query = "SELECT Section.id AS value, Section.title AS text"
		. "\n FROM #__sections AS Section"
		. "\n LEFT JOIN #__categories AS Category ON Section.id = Category.section"
        . "\n INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = 'com_content'"
		. " WHERE (1 = 1"
            . (!defined('MVC_FRAMEWORK_ADMIN') ? 
                  ' AND Section.published = 1 AND Category.published = 1'
                  :
                  ''    
            )
            . (!empty($dir_id) ? "\n AND JreviewsCategory.dirid IN ($dir_id)" : '')		
		    . (!empty($section_id) ? "\n AND Section.id IN ($section_id)" : '')
		    . (!empty($cat_ids) ? "\n AND Category.id IN ($cat_ids)" : '')  
        . ")"
		. "\n GROUP BY Section.id"
		. "\n ORDER BY Section.title"
		;
						
		$this->_db->setQuery($query);
	
		$sections = $this->_db->loadObjectList();

		return $sections;	
	}
		
	function getRows($limitstart=0, $limit, &$total) {
		
		$query = "SELECT DISTINCTROW section.id,section.title,jr_section.*"
		. "\n FROM #__sections AS section"
		. "\n LEFT JOIN #__jreviews_sections AS jr_section ON jr_section.sectionid = section.id"
		. "\n LEFT JOIN #__categories AS category ON section.id = category.section"
		. "\n INNER JOIN #__jreviews_categories AS jr_category ON jr_category.id = category.id"
		."\n AND jr_category.option = 'com_content'"
		."\n ORDER BY section.title ASC"
		;
		$this->_db->setQuery( $query );
		$total = $this->_db->loadResult();
		$this->_db->setQuery($query);
		if ($rows = $this->_db -> loadObjectList()) {
			$total = count($rows);
			$rows = array_slice($rows,$limitstart,$limit);
			return $rows;
		}
		$total = 0;
		$rows = array();
	}
	
	function getTemplateSettings($section_id) {
		
		# Check for cached version		
		$cache_prefix = 'section_model_themesettings';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}	
								
		$fields = array(
			'JreviewsSection.tmpl AS `Section.tmpl_list`',
			'JreviewsSection.tmpl_suffix AS	`Section.tmpl_suffix`'
		);
		
		$query = "SELECT " . implode(',',$fields)
		. "\n FROM #__sections AS Section"
		. "\n LEFT JOIN #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid"
		. "\n WHERE Section.id = " . $section_id
		;
		
		$this->_db->setQuery($query);
		
		$result = end($this->__reformatArray($this->_db->loadAssocList()));
		
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);		
		
		return $result;
	}	
	
	function afterFind($results) {

		$Menu = ClassRegistry::getClass('MenuModel');
		
		$results = $Menu->addMenuSection($results);

		return $results;
		
	}	
	
}
