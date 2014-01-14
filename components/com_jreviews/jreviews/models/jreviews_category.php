<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsCategoryModel extends MyModel  {

	var $name = 'JreviewsCategory';

	var $useTable = '#__jreviews_categories AS JreviewsCategory';

	var $primaryKey = 'JreviewsCategory.id';

	var $realKey = 'id';

	var $fields = array(
		'JreviewsCategory.id AS `JreviewsCategory.id`',
		'JreviewsCategory.dirid AS `JreviewsCategory.dir_id`',
		'JreviewsCategory.criteriaid AS `JreviewsCategory.criteria_id`',
		'JreviewsCategory.tmpl AS `JreviewsCategory.tmpl`',
		'JreviewsCategory.tmpl_suffix AS `JreviewsCategory.tmpl_suffix`',
        'ListingType.id AS `ListingType.criteria_id`',
        'ListingType.title AS `ListingType.title`',
        'ListingType.criteria AS `ListingType.criteria`',
        'ListingType.required AS `ListingType.required`',
        'ListingType.weights AS `ListingType.weights`',
        'ListingType.tooltips AS `ListingType.tooltips`',
        'ListingType.qty AS `ListingType.quantity`',
        'ListingType.groupid AS `ListingType.group_id`',
        'ListingType.state AS `ListingType.state`',
        'ListingType.config AS `ListingType.config`'  # Configuration overrides
	);

    var $joins = array(
        'LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id'
    );

	function getEverywhereExtensions() {

        # Check for cached version
        $cache_file = s2CacheKey('jreviews_category_extensions');

        if($cache = S2Cache::read($cache_file)) {

        	return $cache;
        }

        $query = "
        	SELECT
        		DISTINCT `option`
        	FROM #__jreviews_categories
        ";

        $extensions = $this->query($query,'loadColumn');

        $valid_extensions = array();

        // Check which extensions are currently installed and unset those that are not
		foreach($extensions AS $extension)
        {
			$model = Inflector::camelize('everywhere_'.$extension);

			S2App::import('Model',$model);

			$class_name = $model . 'Model';

			if($extension == 'com_content' ||
					($extension != 'com_content' && method_exists($class_name,'exists') && call_user_func(array($class_name,'exists')))) {

				$valid_extensions[$extension]['extension'] = $extension;

				if(property_exists($class_name,'joinListingState')) {

					$class_vars = get_class_vars($class_name);

					$valid_extensions[$extension]['listing_join'] = $class_vars['joinListingState'];
				}
			}
		}

        S2Cache::write($cache_file, $valid_extensions);

        return $valid_extensions;
	}
}