<?php
   /**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2009 Neal Chambers
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComResourceModel extends MyModel  {


    var $name = 'Listing';

    var $useTable = '#__js_res_record AS Listing';

    var $primaryKey = 'Listing.listing_id';

    var $realKey = 'id';

    /**
     * Used for listing module - latest listings ordering
     * mtime - last time the article was edited
     * ctime - when the article was first published
     */
    var $dateKey = 'mtime';  // mtime | ctime

    var $extension = 'com_resource';

    var $listingUrl = 'index.php?option=com_resource&amp;controller=article&amp;article=%s&amp;category_id=%s&amp;Itemid=%s';

    var $fields = array(
        'Listing.id AS `Listing.listing_id`',
        'Listing.title AS `Listing.title`',
        'Listing.user_id AS `Listing.user`',
        "'com_resource' AS `Listing.extension`",
        'JreviewsCategory.id AS `Listing.cat_id`',
        'Category.name AS `Category.title`',
        'ExtensionCategory.type_id AS `Category.cat_id`',
        'ResourceCategory.catid AS `ResourceCategory.catid`',
        'PictureField.id AS `PictureField.id`',
        'Picture.field_value AS `Listing.images`',
        'Criteria.id AS `Criteria.criteria_id`',
        'Criteria.criteria AS `Criteria.criteria`',
        'Criteria.tooltips AS `Criteria.tooltips`',
        'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',
        'Criteria.state AS `Criteria.state`',
        'Criteria.config AS `ListingType.config`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`'
    );

    /**
     * Used for detail listing page
     */
    var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_resource'",
        'LEFT JOIN #__js_res_record AS ExtensionCategory ON Listing.id = ExtensionCategory.id',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.type_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_resource'",
 //Jresouce is a content system that you can create multiple types of articles for. I want to set criteria based on the type of article
        'LEFT JOIN #__js_res_types AS Category ON JreviewsCategory.id = Category.id',
        'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
 //Types can be organized into multiple categories.  We need to pull the first one so we can use it for the ListingURL
        'LEFT JOIN #__js_res_record_category AS ResourceCategory ON ResourceCategory.record_id = Listing.id',
 //This is a search for a picture field in the type to be used for the preview picture (image)
        "LEFT JOIN #__js_res_fields AS PictureField ON ExtensionCategory.type_id = PictureField.type_id AND PictureField.type = 'picture'",
        'LEFT JOIN #__js_res_record_values AS Picture ON Picture.field_id = PictureField.id AND Picture.record_id = Listing.id'
    );

    /**
     * Used to complete the listing information for reviews based on the Review.pid
     */
    var $joinsReviews = array(
        'LEFT JOIN #__js_res_record AS ExtensionCategory ON Review.pid = ExtensionCategory.id',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.type_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_resource'",
        'LEFT JOIN #__js_res_types AS Category ON JreviewsCategory.id = Category.id',
        'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        'LEFT JOIN #__js_res_record_category AS ResourceCategory ON Review.pid = ResourceCategory.record_id',
        "LEFT JOIN #__js_res_fields AS PictureField ON ExtensionCategory.type_id = PictureField.type_id AND PictureField.type = 'picture'",
        'LEFT JOIN #__js_res_record_values AS Picture ON Picture.field_id = PictureField.id AND Picture.record_id = Review.pid'
    );

    function __construct() {
        parent::__construct();

        $this->tag = __t("RESOURCE_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

        $this->fields[] = "'{$this->tag }' AS `Listing.tag`";
    }



    function listingUrl($listing) {

        return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['ResourceCategory']['catid'],$listing['Listing']['menu_id']);

    }

    function afterFind($results) {

        if (empty($results))
        {
            return $results;
        }

        # Find Itemid for component
        $Menu = ClassRegistry::getClass('MenuModel');

        $menu_id = $Menu->getComponentMenuId($this->extension);

        foreach($results AS $key=>$result)
        {
            // Process component menu id
            $results[$key][$this->name]['menu_id'] = $menu_id;

            // Process listing url
            $results[$key][$this->name]['url'] = $this->listingUrl($results[$key]);

            // Process criteria
            if(isset($result['Criteria']['criteria']) && $result['Criteria']['criteria'] != '') {
                $results[$key]['Criteria']['criteria'] = explode("\n",$result['Criteria']['criteria']);
            }

            if(isset($result['Criteria']['tooltips']) && $result['Criteria']['tooltips'] != '') {
                $results[$key]['Criteria']['tooltips'] = explode("\n",$result['Criteria']['tooltips']);
            }

            if(isset($result['Criteria']['weights']) && $result['Criteria']['weights'] != '') {
                $results[$key]['Criteria']['weights'] = explode("\n",$result['Criteria']['weights']);
            }

              // Process images
            $images = $result['Listing']['images'];
            unset($results[$key]['Listing']['images']);
            $results[$key]['Listing']['images'] = array();

            $upload_dir = ''; // You can hard set an upload directory here and comment out the code below. e.g. ('/uploads')

            //dynamically pull the directory information
            if (!defined('RES_GENERAL_UPLOAD')) {

                // initialyze resource Controller, this will set the RES_GENERAL_UPLOAD constant
                require_once ('..'.DS.'administrator'.DS.'components'.DS.'com_resource'.DS.'controllers'.DS.'config.php');
                ResControllerConfig::initialyze();

             }

                $upload_dir = RES_GENERAL_UPLOAD;



            if($images != '') {
                if ( @file_exists($upload_dir.DS.'picture'.DS.$result['Listing']['user'].DS.'thumbnail_blog'.DS.$images) ) {
                    $imagePath = $upload_dir.DS.'picture'.DS.$result['Listing']['user'].DS.'thumbnail_blog'.DS.$images;
                } else if ( @file_exists($upload_dir.DS.'picture'.DS.$result['Listing']['user'].DS.$images) ) {
                    $imagePath = $upload_dir.DS.'picture'.DS.$result['Listing']['user'].DS.$images;}
            } else {
                // Put a noimage path here?
                $imagePath = $upload_dir.DS.'na.jpg'; // remember to put an na.jpg in your standard upload folder!
            }

            $results[$key]['Listing']['images'][] = array(
                'path'=>$imagePath,
               'caption'=>$results[$key]['Listing']['title'],
                'basepath'=>true
            );

        }

        return $results;
    }

    /**
     * ADMIN FUNCTIONS BELOW
     */
     function getNewCategories()
    {
        $query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

        $query = "SELECT Component.id AS value,Component.name as text"
        . "\n FROM #__js_res_types AS Component"
        . "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
        . ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
        . "\n ORDER BY Component.name ASC"
        ;

        return $this->query($query,'loadAssocList');
    }

    function getUsedCategories()
    {
        $query = "SELECT Component.id AS `Component.cat_id`,Component.name as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
        . "\n FROM #__js_res_types AS Component"
        . "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
        . "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
        . "\n LIMIT $this->offset,$this->limit"
        ;
        $this->_db->setQuery($query);
        $results = $this->_db->loadObjectList();
        $results = $this->__reformatArray($results);
        $results = $this->changeKeys($results,'Component','cat_id');

        $query = "SELECT count(JreviewCategory.id)"
        . "\n FROM #__jreviews_categories AS JreviewCategory"
        . "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
        ;
        $this->_db->setQuery($query);
        $count = $this->_db->loadResult();

        return array('rows'=>$results,'count'=>$count);
    }
}
?>