<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoriesController extends MyController
{

    var $uses = array('user','menu','criteria','directory','category','field','media');

    var $helpers = array('assets','cache','routes','libraries','html','text','jreviews','widgets','time','paginator','rating','custom_fields','community','media');

	var $components = array('config','access','feeds','everywhere','media_storage');

    var $autoRender = false; //Output is returned

	var $autoLayout = true;

	var $layout = 'listings';

	var $click2search = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
        $this->Listing->controller = $this->name;
        $this->Listing->action = $this->action;

        # Make configuration available in models
        $this->Listing->Config = &$this->Config;
    }

    function getPluginModel() {
        return $this->Listing;
    }

    function getObserverModel() {
        return $this->Listing;
    }

    function alphaindex() { $this->listings(); }

    function category()
    {
        if(!Sanitize::getString($this->params,'cat'))
        {
            echo "Admin: You need to specify a valid category id in the menu parameters.";
            return;
        }
        $this->listings();
    }

    function favorites()
    {
        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

        if (!$user_id) {
            echo $this->render('elements','login');
            return;
        }

        $this->Listing->joins[] = 'INNER JOIN #__jreviews_favorites AS Favorite ON Listing.id = Favorite.content_id AND Favorite.user_id = ' . $user_id;

        $this->listings();
    }

    function featured()
    {
        $this->Listing->conditions[] = 'Field.featured > 0';
        $this->listings();
    }

    function featuredrandom()
    {
        $this->Listing->conditions[] = 'Field.featured > 0';
        $this->listings();
    }

    function latest() { $this->listings(); }

    function mylistings()
    {
        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);
        if(!$user_id)
        {
            echo $this->render('elements','login');
            return;
        }
        $this->Listing->conditions[] = 'Listing.created_by = '.$user_id;
        $this->listings();
    }

    function mostreviews()
    {
        $this->Listing->conditions[] = 'Totals.user_comment_count > 0';
        $this->listings();
    }

    function toprated()
    {
        $this->Listing->conditions[] = 'Totals.user_rating > 0';
        $this->listings();
    }

    function topratededitor()
    {
        $this->Listing->conditions[] = 'Totals.editor_rating > 0';
        $this->listings();
    }

    function popular() { $this->listings(); }

    function random() { $this->listings(); }

    function listings()
    {
		if(Sanitize::getString($this->params,'action') == 'xml')
		{
            $access =  $this->Access->getAccessLevels();

			$feed_filename = S2_CACHE . 'views' . DS . 'jreviewsfeed_'.md5($access.$this->here).'.xml';

			$this->Feeds->useCached($feed_filename,'listings');
        }

        $this->name = 'categories';   // Required for assets helper

        if($this->_user->id === 0 && ($this->action != 'search' || ($this->action == 'search' && Sanitize::getVar($this->params,'tag') != '')))
        {
            $this->cacheAction = Configure::read('Cache.expires');
        }

        $this->autoRender = false;

        $action = Sanitize::paranoid($this->action);

        $dir_id = str_replace(array('_',' '),array(',',''),Sanitize::getString($this->params,'dir'));

        $cat_id = Sanitize::getString($this->params,'cat');

        $criteria_id = Sanitize::getString($this->params,'criteria');

        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

        $index = Sanitize::getString($this->params,'index');

        $sort = Sanitize::getString($this->params,'order');

        $listview = Sanitize::getString($this->params,'listview');

        $tmpl_suffix = Sanitize::getString($this->params,'tmpl_suffix');

        $order_field = Sanitize::getString($this->Config,'list_order_field');

        $order_default = Sanitize::getString($this->Config,'list_order_default');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort.$listview.$tmpl_suffix != '';

        if($sort == '' && $order_field != '' && in_array($this->action,array('category','alphaindex','search','custom'))) {

            $sort = $order_field;
        }
        elseif($sort == '') {

            $sort = $order_default;
        }

        $menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));

        $query_listings = true; // Check if it can be disabled for parent category pages when listings are disabled

        $total_special = Sanitize::getInt($this->data,'total_special');

        if(!in_array($this->action,array('category')) && $total_special > 0) {

            $total_special <= $this->limit and $this->limit = $total_special;
        }

        $listings = array();

        $parent_categories = array();

        $count = 0;

        $conditions = array();

        $joins = array();

        if($action == 'category' || ($action == 'search' && is_numeric($cat_id) && $cat_id > 0))
        {
            if($parent_categories = $this->Category->findParents($cat_id)) /*J16*/
            {
                $category = end($parent_categories); // This is the current category

                if(!$category['Category']['published'] || !$this->Access->isAuthorized($category['Category']['access']))
                {
                    echo $this->render('elements','login');
                    return;
                }

                $dir_id = $this->params['dir'] = $category['Directory']['dir_id'];

                $categories = $this->Category->findTree(array('cat_id'=>$cat_id )/* result includes parent */);

                # Check the listing type of all subcategories and if it's the same one apply the overrides to the parent category as well
                $overrides = array();

                if(count($categories) > 1 && $category['Category']['criteria_id'] == 0 && !empty($categories)) {

                    foreach($categories AS $tmp_cat) {

                        if($tmp_cat['Category']['criteria_id'] > 0 && !empty($tmp_cat['ListingType']['config'])) {

                            $overrides[$tmp_cat['Category']['criteria_id']] = $tmp_cat['ListingType']['config'];
                        }
                    }

                    if(count($overrides) == 1) {

                        $category['ListingType']['config'] = array_shift($overrides);
                    }
                }

            }

            # Override global configuration
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);

            if(!is_array($category['ListingType']['config'])) {

                $category['ListingType']['config'] = json_decode($category['ListingType']['config'],true);
            }

            $order_field_override = Sanitize::getString($category['ListingType']['config'],'list_order_field');

            $order_default_override = Sanitize::getString($category['ListingType']['config'],'list_order_default');

            if($order_field_override != '') {

                $sort_default = $order_field_override;
            }
            elseif ($order_default_override != -1) {

                $sort_default = $order_default_override;
            }
            elseif($order_field != '') {

                $sort_default = $order_field;
            }
            else {

                $sort_default = $order_default;
            }

            $this->params['default_order'] = $sort_default;

            $sort = Sanitize::getString($this->params,'order',$sort_default);

			// Set default order for pagination
			$sort == '' and $sort = $order_default;
        }

        # Remove unnecessary fields from model query
        $this->Listing->modelUnbind('Listing.fulltext AS `Listing.description`');

        # Set the theme layout and suffix
        $this->Theming->setSuffix(array('categories'=>$parent_categories));

        $this->Theming->setLayout(array('categories'=>$parent_categories));

        if($this->action == 'category'
                && isset($category)
                && !empty($category)
                && (!$this->Access->isAuthorized($category['Category']['access']) || !$category['Category']['published'])
           )
        {
            echo $this->render('elements','login');

            return;
        }

        # Get listings

        # Modify and perform database query based on lisPage type
        // Build where statement
        switch($action) {
            case 'alphaindex':
//                    $index = isset($index{0}) ? $index{0} : '';
                $conditions[] = ($index == '0' ? 'Listing.title REGEXP "^[0-9]"' : 'Listing.title LIKE '.$this->Quote($index.'%'));
                break;
        }

        $cat_id     = cleanIntegerCommaList($cat_id);

        $dir_id     = cleanIntegerCommaList($dir_id);

        $criteria_id = cleanIntegerCommaList($criteria_id);

        if(!empty($cat_id))
        {
            if(!$this->Config->list_show_child_listings)
            {
                $conditions[] = 'ParentCategory.id IN ('.$cat_id.')';

                $conditions[] = 'Category.id IN ('.$cat_id.')';  // Exclude listings from child categories
            }
            else
            {
                $conditions[] = 'ParentCategory.id IN ('.$cat_id.')';
            }
        }
        else
        {
            unset($this->Listing->joins['ParentCategory']);
        }

        empty($cat_id) and !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN ('.$dir_id.')';

        empty($cat_id) and !empty($criteria_id) and $conditions[] = 'JreviewsCategory.criteriaid IN ('.$criteria_id.')';

        if (($this->action == 'mylistings' && $user_id == $this->_user->id) || $this->Access->isPublisher())
        {
            $conditions[] = 'Listing.state >= 0';
        }
        else
        {
            $conditions[] = 'Listing.state = 1';
            $conditions[] = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )';
            $conditions[] = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )';
        }

        # Shows only links users can access
        $conditions[] = 'Category.access IN ( ' . $this->Access->getAccessLevels() . ')';

        $conditions[] = 'Listing.access IN ( ' . $this->Access->getAccessLevels() . ')';

        $queryData = array(
            /*'fields' they are set in the model*/
            'joins'=>$joins,
            'conditions'=>$conditions,
            'limit'=>$this->limit,
            'offset'=>$this->offset
        );

        # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables
        if($this->action != 'custom' || ($this->action == 'custom' && empty($this->Listing->order))) {
            $this->Listing->processSorting($action,$sort);
        }

        // This is used in Listings model to know whether this is a list page to remove the plugin tags
        $this->Listing->controller = 'categories';

        // Check if review scope checked in advancd search
        $scope = explode('_',Sanitize::getString($this->params,'scope'));

        if($this->action == 'search' && in_array('reviews',$scope))
        {
            $queryData['joins'][] = "LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'";
            $queryData['group'][] = "Listing.id"; // Group By required due to one to many relationship between listings => reviews table
        }

        $query_listings and $listings = $this->Listing->findAll($queryData);

        # If only one result then redirect to it
        if($this->Config->search_one_result && count($listings)==1 && $this->action == 'search' && $this->page == 1)
        {
            $listing = array_shift($listings);
            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));
            cmsFramework::redirect($url);
        }

        # Get the listing count
        if(in_array($action,array('category')))
        {
            unset($queryData['joins']);
            $this->Listing->joins = array(
                                "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
                'Category'=>    "LEFT JOIN #__categories AS Category ON JreviewsCategory.id = Category.id",
                'ParentCategory'=>"LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt",
                                "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_content'",
                                "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
                                "LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id"
            );
        }
        elseif($action != 'favorites')
        {
            unset($queryData['joins']);
            $this->Listing->joins = array(
                                "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
                'Category'=>    "LEFT JOIN #__categories AS Category ON JreviewsCategory.id = Category.id",
                'ParentCategory'=>"LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt",
                                "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_content'",
                                "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
                                "LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id"
            );

            if($this->action == 'search' && in_array('reviews',$scope))
            {
                $queryData['joins'][] = "LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'";
            }
        }

        if(empty($cat_id))
        {
            unset($this->Listing->joins['ParentCategory']); // Exclude listings from child categories
        }

        // Need to add user table join for author searches
        if(isset($this->params['author']))
        {
            $queryData['joins'][] = "LEFT JOIN #__users AS User ON User.id = Listing.created_by";
        }

        if($query_listings && !isset($this->Listing->count))
        {
            if(in_array($this->action,array('favorites','mylistings'))) {

                $queryData['session_cache'] = false;
            }

            $count = $this->Listing->findCount($queryData, ($this->action == 'search' && in_array('reviews',$scope)) ? 'DISTINCT Listing.id' : '*');
        }
        elseif(isset($this->Listing->count))
        {
            $count = $this->Listing->count;
        }

        if($total_special > 0 && $total_special < $count)
        {
            $count = Sanitize::getInt($this->data,'total_special');
        }

        # Get directory info for breadcrumb if dir id is a url parameter
        $directory = array();

        if(is_numeric($dir_id)) {
            $directory = $this->Directory->findRow(array(
                'fields'=>array(
                    'Directory.id AS `Directory.dir_id`',
                    'Directory.title AS `Directory.slug`',
                    'Directory.desc AS `Directory.title`'
                ),
                'conditions'=>array('Directory.id = ' . $dir_id)
            ));
        }

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $name_choice = ($this->Config->name_choice == 'alias' ? 'username' : 'name');

        $page = $this->createPageArray($menu_id);

        switch($action)
        {
            case 'category':

                if(isset($category)) {

                    $menu_action = 2;

                    $page['title'] == '' and $page['title'] = $category['Category']['title'];

                    // Could be a direct category menu or a menu for a parent category
                    $menu_exists = !empty($page['menuParams']) && isset($page['menuParams']['action']);

                    $menu_is_for_this_category = $menu_exists
                                                    && $page['menuParams']['action'] == $menu_action
                                                    && $page['menuParams']['catid'] == $category['Category']['cat_id'];

                    $menu_page_title = Sanitize::getString($page['menuParams'],'page_title');

                    $menu_page_heading = Sanitize::getString($page['menuParams'],'page_heading');

                    // Prevent the show_page_heading menu param from disabling the display of the category title
                    $page['show_title'] = true;

                    // Ensure the correct title is displayed in subcategory pages when the subcategory doesn't have its own menu
                    if(!$menu_is_for_this_category) {

                        $page['title'] = $category['Category']['title'];

                        $page['title_seo'] = $category['Category']['title_seo'];
                    }
                    else {

                        // Menu page settings override everything else

                        if($menu_page_title != '') {

                            $page['title_seo'] = $menu_page_title;
                        }
                        else {

                            $page['title_seo'] = $category['Category']['title_seo'];
                        }

                        if($menu_page_heading != '') {

                            $page['title'] = $menu_page_heading;
                        }
                    }

                    if(Sanitize::getString($page,'top_description') == '')  {

                        $page['top_description'] = $category['Category']['description'];
                    }

                    // if($menu_not_for_this_category || Sanitize::getString($category['Category'],'metadesc') != '' && Sanitize::getString($page,'description') == '') {
                    // If category doesn't have a menu, but the meta data is available from the Joomla category manager we use it
                    if(($menu_is_for_this_category && $page['menuParams']['menu-meta_description'] == '')
                        ||
                        (!$menu_is_for_this_category && Sanitize::getString($category['Category'],'metadesc') != '')) {

                        $page['description'] =  Sanitize::htmlClean($category['Category']['metadesc']);

                        // Ensure menu params doesn't override Joomla category manager setting
                        $page['menuParams']['menu-meta_description'] = '';
                    }

                    // If category doesn't have a menu, but the meta data is available from the Joomla category manager we use it
                    if(($menu_is_for_this_category && $page['menuParams']['menu-meta_keywords'] == '')
                        ||
                        (!$menu_is_for_this_category && Sanitize::getString($category['Category'],'metakey') != '')) {
                    // if($menu_not_for_this_category || Sanitize::getString($category['Category'],'metakey') != '' && Sanitize::getString($page,'keywords') == '') {

                        $page['keywords'] =  Sanitize::htmlClean($category['Category']['metakey']);

                        // Ensure sure menu params doesn't override Joomla category manager setting
                        $page['menuParams']['menu-meta_keywords'] = '';
                    }

                    // Process Category SEO Manager title, keywords and description
                    $page['description'] = str_replace('{category}',$category['Category']['title'],Sanitize::getString($page,'description'));

                    $page['keywords'] = str_replace('{category}',$category['Category']['title'],Sanitize::getString($page,'keywords'));

                    $matches1 = $matches2 = $matches3 = array();

                    $tags = $replacements = array();

                    if(!empty($parent_categories) &&
                        (
                            preg_match('/{category[0-9]+}/',$page['description'],$matches1)
                            || preg_match('/{category[0-9]+}/',$page['keywords'],$matches2)
                            || preg_match('/{category[0-9]+}/',$page['title_seo'],$matches3)
                        )
                    ) {
                        $matches = array_merge($matches1,$matches2,$matches3);

                        if(!empty($matches)) {

                            $i = 0;

                            foreach($parent_categories AS $category) {

                                $i++;

                                $tags[] = '{category'.$i.'}';

                                $replacements[] = $category['Category']['title'];
                            }
                        }
                    }

                    $tags[] = '{category}';

                    $replacements[] = $category['Category']['title'];

                    if($menu_page_heading == '') {

                        $page['title'] = str_replace($tags,$replacements,$category['Category']['title_override'] ? $page['title_seo'] : $page['title']);
                    }

                    $page['title_seo'] = str_replace($tags,$replacements,$page['title_seo']);

                    $page['description'] = str_replace($tags,$replacements,$page['description']);

                    $page['keywords'] = str_replace($tags,$replacements,$page['keywords']);

                    $page['top_description'] = str_replace($tags,$replacements,$page['top_description']);

                    // Category image
                    //
                    if($categoryParams = Sanitize::getString($category['Category'],'params')) {

                        $categoryParams = json_decode($categoryParams);

                        $page['image'] = Sanitize::getString($categoryParams,'image');

                    }

                    // Check if this is a listing submit category or disable listing submissions

                    if(Sanitize::getInt($category['Category'],'criteria_id') == 0) {

                        $this->Config->list_show_addnew = 0;
                    }
                }

                break;

            case 'custom':

                break;

            case 'alphaindex':

                $title = isset($directory['Directory']) ? Sanitize::getString($directory['Directory'],'title','') : '';

                $page['title'] = ($title != '' ? $title . ' - ' . ($index == '0' ? '0-9' : $index) : ($index == '0' ? '0-9' : $index));

                break;

            case 'mylistings':

                if($user_id > 0)
                {
                    $this->User->fields = array();

                    $user_name = $this->User->findOne(
                        array(
                            'fields'=>array('User.' . $name_choice. ' AS `User.name`'),
                            'conditions'=>array('User.id = ' . $user_id)
                        )
                    );

                }
                elseif($this->_user->id > 0) {

                    $user_name = $this->_user->{$name_choice};
                }

                $page['title'] = $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LIST_PAGE_LISTINGS_BY_TITLE_SEO'),$user_name);

                $page['show_title'] = 1;

                break;

            case 'favorites':

                if($user_id > 0)
                {
                    $this->User->fields = array();

                    $user_name = $this->User->findOne(
                        array(
                            'fields'=>array('User.' . $name_choice. ' AS `User.name`'),
                            'conditions'=>array('User.id = ' . $user_id)
                        )
                    );

                } elseif($this->_user->id>0) {

                    $user_name = $this->_user->{$name_choice};
                }

                $page['show_title'] = 1;

                $page['title'] = $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LIST_PAGE_FAVORITES_BY_TITLE_SEO'), $user_name);

                break;

            case 'list':
            case 'search':

                $this->__seo_fields($page, $cat_id);

                break;

            case 'featured':
            case 'latest':
            case 'mostreviews':
            case 'popular':
            case 'toprated':
            case 'topratededitor':

                break;

            default:

                $page['title'] = $menu_title;

                break;
        }

        if(Sanitize::getString($page,'top_description') != '') $page['show_description'] = true;

		// If empty unset the keys so they don't overwrite the ones set via menu
        if(trim(strip_tags(Sanitize::getString($page,'description'))) == '') unset($page['description']);

        if(trim(strip_tags(Sanitize::getString($page,'keywords'))) == '') unset($page['keywords']);

        /******************************************************************
        * Generate SEO canonical tags for sorted pages
        *******************************************************************/
        if($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl(array('order','listview','tmpl_suffix'));
        }

        /******************************************************************
        * Generate SEO titles for re-ordered pages (most reviews, top user rated, etc.)
        *******************************************************************/
        if(!isset($page['title_seo']) && isset($page['title'])) {

            $page['title_seo'] = $page['title'];
        }

        # Category ids to be used for ordering list
        $cat_ids = array();

        if(in_array($action,array('search','category')))
        {
            $cat_ids = $cat_id;
        }
        elseif(!empty($categories))
        {
            $cat_ids = implode(',',array_keys($categories));
        }

        $field_order_array = $this->Field->getOrderList($cat_ids,'listing',$this->action,array('category','search','alphaindex'));

        if(($this->action !='search' || Sanitize::getVar($this->params,'tag')) && isset($this->params['order']) && $sort != '')
        {
            S2App::import('helper','jreviews','jreviews');

            $ordering_options = JreviewsHelper::orderingOptions();

            $tmp_order = str_replace('rjr','jr',$sort);

            if(isset($ordering_options[$sort]))
            {

                $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_TITLE_SEO'), mb_strtolower($ordering_options[$sort],'UTF-8'));
            }
            elseif(isset($field_order_array[$tmp_order])) {

                if($sort{0} == 'r')
                {

                    $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_DESC_TITLE_SEO'), mb_strtolower($field_order_array[$tmp_order]['text'],'UTF-8'));
                }
                else {

                    $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_TITLE_SEO'), mb_strtolower($field_order_array[$sort]['text'],'UTF-8'));
                }
            }
        }

        $this->params['order'] = $sort; // This is the param read in the views so we need to update it

        /******************************************************************
        * Set view (theme) vars
        *******************************************************************/
        $this->set(
            array(
                'Config'=>$this->Config,
                'User'=>$this->_user,
                'subclass'=>'listing',
                'page'=>$page,
                'directory'=>$directory,
                'category'=>isset($category) ? $category : array(), // Category list
                'categories'=>isset($categories) ? $categories : array(),
                'parent_categories'=>$parent_categories, // Used for breadcrumb
                'cat_id'=>$cat_id,
                'listings'=>$listings,
                'pagination'=>array('total'=>$count))
        );

        $query_listings and $this->set('order_list',$field_order_array);

        /******************************************************************
        * RSS Feed: caches and displays feed when xml action param is present
        *******************************************************************/
        if(Sanitize::getString($this->params,'action') == 'xml') {
            $this->Feeds->saveFeed($feed_filename,'listings');
        }

        echo $this->render('listings','listings_' . $this->listview);
    }

    function compare()
    {
        $listings = array();

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $listingType = Sanitize::getInt($this->params,'type');

        $menuParams = $this->Menu->getMenuParams($menu_id);

        $is_mobile = Configure::read('System.isMobile');

        $isMenu = false;

        $listing_ids = cleanIntegerCommaList(Sanitize::getString($menuParams,'listing_ids'));

        if(!empty($listing_ids)) {

            $listing_ids = explode(',',$listing_ids);

            $isMenu = true;
        }
        elseif($listing_ids = Sanitize::getString($this->params,'id')) {

            $listing_ids = cleanIntegerCommaList($listing_ids);

            if(!empty($listing_ids)) {

                $listing_ids = explode(',',$listing_ids);
            }
            else {

                $listing_ids = null;
            }

            $isMenu = false;
        }
        else {

            $listing_ids = null;
        }

        if(empty($listing_ids)) {

            cmsFramework::raiseError(404, JreviewsLocale::getPHP('COMPARISON_NO_LISTINGS'));
        }

        $conditions[] = "Listing.id IN (".implode(",",$listing_ids).")";

        $conditions[] = 'Listing.state = 1';

        $conditions[] = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )';

        $conditions[] = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._TODAY.'" )';

        # Shows only links users can access

        $conditions[] = 'Category.access IN ( ' . $this->Access->getAccessLevels() . ')';

        $conditions[] = 'Listing.access IN ( ' . $this->Access->getAccessLevels() . ')';

        $listings = $this->Listing->findAll(array('conditions'=>$conditions,'order'=>array('FIELD(Listing.id,'.implode(",",$listing_ids).')')));

        $listing_type_id = array();

        foreach($listings AS $listing) {

            $listing_type_id[$listing['Criteria']['criteria_id']] = $listing['Criteria']['criteria_id'];
        }

        if(count($listing_type_id) > 1) {
            return '<div class="jrError">'.JreviewsLocale::getPHP('COMPARISON_VALIDATE_DIFFERENT_TYPES').'</div>';
        }

        $firstListing = reset($listings);

        # Override configuration
        isset($firstListing['ListingType']) and $this->Config->override($firstListing['ListingType']['config']);

        $listingType = $firstListing['Criteria'];

        $listing_type_title = $listingType['title'];

        // Get the list of fields for the chosen listing type to render the groups and field in the correct order

        $fieldGroups = $this->Field->getFieldsArrayNew($listingType['criteria_id']);

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

        if($page['title'] == '') {

            $page['show_title'] = true;

            $page['title'] = sprintf(JreviewsLocale::getPHP('COMPARISON_DEFAULT_TITLE'),$listing_type_title);
        }

        if(Sanitize::getInt($menuParams,'action') == '103') {

            $page['title_seo'] = $page['title'];
        }

        $this->set(array(
            'listingType'=>$listingType,
            'Config'=>$this->Config,
            'User'=>$this->_user,
            'fieldGroups'=>$fieldGroups,
            'listings'=>$listings,
            'page'=>$page,
            'isMenu'=>$isMenu
        ));

        if (!$is_mobile) {
            return $this->render('listings','listings_compare');
        } else {
            return $this->render('listings','listings_blogview');
        }


    }

    # Custom List menu - reads custom where and custom order from menu parameters
    function custom() {

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $params = $this->Menu->getMenuParams($menu_id);

        $custom_where = Sanitize::getString($params,'custom_where');

        $custom_order = Sanitize::getString($params,'custom_order');

        if($custom_where !='') {

            $custom_where = str_replace(
                array('{user_id}'),
                array($this->_user->id),
                $custom_where);

            $this->Listing->conditions[] = $custom_where;
        }

        $custom_order !='' and $this->Listing->order[] = $custom_order;

        return $this->listings();
    }

    function search()
    {
        $urlSeparator = "_"; //Used for url parameters that pass something more than just a value
        $simplesearch_custom_fields = 1 ; // Search custom fields in simple search
        $simplesearch_query_type = Sanitize::getString($this->Config,'search_simple_query_type','all'); // any|all
        $min_word_chars = 3; // Only words with min_word_chars or higher will be used in any|all query types
        $category_ids = '';
        $criteria_ids = Sanitize::getString($this->params,'criteria');
        $dir_id = Sanitize::getString($this->params,'dir','');
        $accepted_query_types = array ('any','all','exact');
        $query_type = Sanitize::getString($this->params,'query');
        $keywords = urldecode(Sanitize::getString($this->params,'keywords'));
        $scope = Sanitize::getString($this->params,'scope');
        $author = urldecode(Sanitize::getString($this->params,'author'));
        $ignored_search_words = $keywords != '' ? cmsFramework::getIgnoredSearchWords() : array();

        if (!in_array($query_type,$accepted_query_types)) {
            $query_type = 'all'; // default value if value used is not recognized
        }

        // Build search where statement for standard fields
        $wheres = array();

        # SIMPLE SEARCH
        if ($keywords != '' &&  $scope=='')
        {
//            $scope = array("Listing.title","Listing.introtext","Listing.fulltext","Review.comments","Review.title");
            $scope = array("Listing.title","Listing.introtext","Listing.fulltext","Listing.metakey");

            $words = array_unique(explode( ' ', $keywords));
            // Include custom fields
            if ($simplesearch_custom_fields == 1)
			{
				$fields = $this->Field->getTextBasedFieldNames();
                // TODO: find out which fields have predefined selection values to get the searchable values instead of reference
            }

            $whereFields = array();

            foreach ($scope as $contentfield) {

                $whereContentFields = array();

                foreach ($words as $word)
                {
                    if(strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
                    {
                        $word = urldecode(trim($word));
                        $whereContentFields[] = " $contentfield LIKE " . $this->QuoteLike($word);
                    }
                }

                if(!empty($whereContentFields)){
                    $whereFields[] = " (" . implode( ($simplesearch_query_type == 'all' ? ') AND (' : ') OR ('), $whereContentFields ) . ')';
                }
            }

            if ($simplesearch_custom_fields == 1)
            {
                // add custom fields to where statement
                foreach ($fields as $field) {

                    $whereCustomFields = array();

                    foreach ($words as $word)
                    {
                        $word = urldecode($word);

                        if(strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
                        {
                            $whereCustomFields[]     = "$field LIKE ".$this->QuoteLike($word);
                        }
                    }

                    if (!empty($whereCustomFields)) {
                        $whereFields[] = "\n(" . implode( ($simplesearch_query_type == 'all' ? ') AND (' : ') OR ('), $whereCustomFields ) . ')';
                    }
                }

            }

            if(!empty($whereFields))
            {
            $wheres[] = "\n(" . implode(  ') OR (', $whereFields ) . ')';
            }

        } else {
        # ADVANCED SEARCH
            // Process core content fields and reviews
            if ($keywords != '' && $scope != '') {

                $allowedContentFields = array("title","introtext","fulltext","reviews","metakey");

                $scope = explode($urlSeparator,$scope);
                $scope[] = 'metakey';

                switch ($query_type)
                {
                    case 'exact':
                        foreach ($scope as $contentfield) {

                            if (in_array($contentfield,$allowedContentFields)) {

                                $w     = array();

                                if ($contentfield == 'reviews') {
                                    $w[] = " Review.comments LIKE ".$this->QuoteLike($keywords);
                                    $w[] = " Review.title LIKE ".$this->QuoteLike($keywords);
                                } else {
                                    $w[] = " Listing.$contentfield LIKE ".$this->QuoteLike($keywords);
                                }
                                $whereContentOptions[]     = "\n" . implode( ' OR ', $w);
                            }
                        }

                        $wheres[]     = implode( ' OR ', $whereContentOptions);

                    break;
                    case 'any':
                    case 'all':
                    default:

                        $words = array_unique(explode( ' ', $keywords));
                        $whereFields = array();

                        foreach ($scope as $contentfield) {

                            if (in_array($contentfield,$allowedContentFields)) {

                                $whereContentFields = array();
                                $whereReviewComment = array();
                                $whereReviewTitle = array();

                                foreach ($words as $word)
                                {
                                    if(strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
                                    {
                                        if ($contentfield == 'reviews') {
                                            $whereReviewComment[] = "Review.comments LIKE ".$this->QuoteLike($word);
                                            $whereReviewTitle[] = "Review.title LIKE ".$this->QuoteLike($word);
                                        } else {
                                            $whereContentFields[] = "Listing.$contentfield LIKE ".$this->QuoteLike($word);
                                        }
                                    }
                                }

                                if ($contentfield == 'reviews')
                                {
                                    if(!empty($whereReviewTitle))
                                    {
                                        $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewTitle ) . ")";
                                    }

                                    if(!empty($whereReviewComment))
                                    {
                                        $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewComment ) . ")";
                                    }
                                }
                                elseif(!empty($whereContentFields)) {

                                    $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereContentFields ) . ")";
                                }

                            }
                        }

                        if(!empty($whereFields))
                        {
                            $wheres[] = '(' . implode(  ') OR (', $whereFields ) . ')';
                        }

                    break;
                }

            } else {

                $scope = array();
            }

            // Process author field
            if ($author && $this->Config->search_item_author) {
                $wheres[] = "( User.name LIKE ".$this->QuoteLike($author)." OR "
                ."\n User.username LIKE ".$this->QuoteLike($author)." OR "
                ."\n Listing.created_by_alias LIKE ".$this->QuoteLike($author)
                ." )"
                ;
            }

            // Process custom fields
            $query_string = Sanitize::getString($this->passedArgs,'url');

            if($tag = Sanitize::getVar($this->params,'tag'))
            {
                $this->click2search = true;

                if($menu_id = Sanitize::getInt($this->params,'Itemid'))
                {
                    $menuParams = $this->Menu->getMenuParams($menu_id);

                    $action = Sanitize::getString($menuParams,'action');

                    // If it's an adv. search menu and click2search url, use the menu criteria id
                    switch($action) {
                        case '2':

                            !isset($this->params['cat']) && $this->params['cat'] = $menuParams['catid'];

                            break;
                        case '11':

                            $this->params['criteria'] = $menuParams['criteriaid'];

                            break;

                        default:

                            break;
                    }

                }

                // Field value underscore fix: remove extra menu parameter not removed in routes regex
                $tag['value'] = preg_replace(array('/_m[0-9]+$/','/_m$/','/_$/'),'',$tag['value']);

                // Below is included fix for dash to colon change in J1.5
                $query_string = 'jr_'.$tag['field']. _PARAM_CHAR .str_replace(':','-',$tag['value']) . '/'.$query_string;
            }

            $url_array = explode ("/", $query_string);

            // Include external parameters for custom fields - this is required for components such as sh404sef
            foreach($this->params AS $varName=>$varValue) {
                if(substr($varName,0,3) == "jr_" && false === array_search($varName . _PARAM_CHAR . $varValue,$url_array)) {
                    $url_array[] = $varName . _PARAM_CHAR . $varValue;
                }
            }

            // Get names of custom fields to eliminate queries on non-existent fields
            $customFieldsMeta = $this->Field->query(null,'getTableColumns','#__jreviews_content');

            $customFields = isset($customFieldsMeta['#__jreviews_content']) ? array_keys($customFieldsMeta['#__jreviews_content']) : array_keys($customFieldsMeta);

            empty($customFields) and $customFields = array();

            /****************************************************************************
            * First pass of url params to get all field names and then find their types
            ****************************************************************************/
            $fieldNameArray = array();

            foreach ($url_array as $url_param)
            {
                // Fixes issue where colon separating field name from value gets converted to a dash by Joomla!
                if(preg_match('/^(jr_[a-z0-9]+)-([\S\s]*)/',$url_param,$matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                }
                else {
                    $param = explode (":",$url_param);
                    $key = $param[0];
                    $value = Sanitize::getVar($param,'1',null); // '1' is the key where the value is stored in $param
                }

                if (substr($key,0,3)=="jr_" && in_array($key,$customFields) && !is_null($value) && $value != '') {
                    $fieldNameArray[$key] = $value;
                }
            }

            // Find out the field type to determine whether it's an AND or OR search
            if(!empty($fieldNameArray)) {
                $query = '
                    SELECT
                        name, type
                    FROM
                        #__jreviews_fields
                    WHERE
                        name IN (' .$this->Quote(array_keys($fieldNameArray)) . ')'
                    ;
                $this->_db->setQuery($query);

                $fieldTypesArray = $this->_db->loadAssocList('name');
            }

            $OR_fields = array("select","radiobuttons"); // Single option

            $AND_fields = array("selectmultiple","checkboxes"); // Multiple option

            foreach ($fieldNameArray AS $key=>$value)
            {
                $searchValues = explode($urlSeparator, $value);

                $fieldType = $fieldTypesArray[$key]['type'];

                // Process values with separator for multiple values or operators. The default separator is an underscore
                if (substr_count($value,$urlSeparator)) {

                    // Check if it is a numeric or date value
                    $allowedOperators = array("equal"=>'=',"higher"=>'>=',"lower"=>'<=', "between"=>'between');
                    $operator = $searchValues[0];

                    $isDate = false;
                    if ($searchValues[1] == "date") {
                        $isDate = true;
                    }

                    if (in_array($operator,array_keys($allowedOperators)) && (is_numeric($searchValues[1]) || $isDate))
                    {
                        if ($operator == "between")
                        {
                            if ($isDate)
                            {
                                @$searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];
                                @$searchValues[2] = low($searchValues[3]) == 'today' ? _TODAY : $searchValues[3];
                            }

                            $low = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);
                            $high = is_numeric($searchValues[2]) ? $searchValues[2] : $this->Quote($searchValues[2]);
                            $wheres[] = "\n".$key." BETWEEN " . $low . ' AND ' . $high;
                        }
                        else {
                            if ($searchValues[1] == "date") {
                                $searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];
                            }
                            $value = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);
                            $wheres[] = "\n".$key.$allowedOperators[$operator].$value;
                        }
                    }
                    else {
                        // This is a field with pre-defined options
                        $whereFields = array();

                        if(isset($tag) && $key = 'jr_'.$tag['field']) {
                            // Field value underscore fix
                            if(in_array($fieldType,$OR_fields)) {
                                $whereFields[] = " $key = '*".$this->Quote('*'.urldecode($value).'*');
                            }
                            else {
                                $whereFields[] = " $key LIKE ".$this->Quote('%*'.urldecode($value).'*%');
                            }
                        }
                        elseif(!empty($searchValues))
                        {
                            foreach ($searchValues as $value)
                            {
                                $searchValue = urldecode($value);
                                if(in_array($fieldType,$OR_fields)) {
                                    $whereFields[] = " $key = ".$this->Quote('*'.$value.'*') ;
                                }
                                else {
                                    $whereFields[] = " $key LIKE ".$this->Quote('%*'.$value.'*%');
                                }
                            }
                        }

                        if (in_array($fieldType,$OR_fields)) { // Single option field
                            $wheres[] = '(' . implode( ') OR (', $whereFields ) . ')';
                        } else { // Multiple option field
                            $wheres[] = '(' . implode( ') AND (', $whereFields ) . ')';
                        }
                    }

                }
                else {

                    $value = urldecode($value);

                    $whereFields = array();

                    switch($fieldType) {

                        case in_array($fieldType,$OR_fields):

                            $whereFields[] = " $key = ".$this->Quote('*'.$value.'*') ;

                        break;

                        case in_array($fieldType,$AND_fields):

                            $whereFields[] = " $key LIKE ".$this->Quote('%*'.$value.'*%');

                        break;

                        case 'decimal':

                            $whereFields[] = " $key = " . (bool) $value;

                        break;

                        case 'integer':
                        case 'relatedlisting':

                            $whereFields[] = " $key = " . (int) $value;

                        break;

                        case 'date':

                            $order = Sanitize::getString($this->params,'order');

                            $begin_week = date('Y-m-d', strtotime('monday last week'));

                            $end_week = date('Y-m-d', strtotime('monday last week +6 days')) . ' 23:59:99';

                            $begin_month = date('Y-m-d',mktime(0, 0, 0, date('m'), 1));

                            $end_month = date('Y-m-t', strtotime('this month')) . ' 23:59:99';

                            $lastseven = date('Y-m-d', strtotime('-1 week'));

                            $lastthirty = date('Y-m-d', strtotime('-1 month'));

                            $nextseven = date('Y-m-d', strtotime('+1 week')) . ' 23:59:99';

                            $nextthirty = date('Y-m-d', strtotime('+1 month')) . ' 23:59:99';

                            switch($value) {

                                case 'future':
                                    $whereFields[] = " $key >= " . $this->Quote(_TODAY);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case 'today':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case 'week':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote($begin_week) . ' AND ' . $this->Quote($end_week);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case 'month':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote($begin_month) . ' AND ' . $this->Quote($end_month);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case '+7':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextseven);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case '+30':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextthirty);
                                    $order == '' and $this->Listing->order = array($key . ' ASC');
                                break;
                                case '-7':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote($lastseven) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                    $order == '' and $this->Listing->order = array($key . ' DESC');
                                break;
                                case '-30':
                                    $whereFields[] = " $key BETWEEN " . $this->Quote($lastthirty) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                    $order == '' and $this->Listing->order = array($key . ' DESC');
                                break;
                                default:
                                    $whereFields[] = " $key = " . $this->Quote($value);
                                break;
                            }

                        break;

                        default:

                            if(isset($tag) && $key == 'jr_'.$tag['field'] && $fieldType == 'text')
                            {
                               $whereFields[] = " $key = " . $this->Quote($value);
                            }
                            else {

                               $whereFields[] = " $key LIKE " . $this->QuoteLike($value);
                            }

                        break;
                    }

                    $wheres[] = " (" . implode(  ') AND (', $whereFields ) . ")";
                }

            } // endforeach
        }

        $where = !empty($wheres) ? "\n (" . implode( ") AND (", $wheres ) . ")" : '';

        // Determine which categories to include in the queries
        if ($cat_id = Sanitize::getString($this->params,'cat'))
        {
            $category_ids = explode($urlSeparator,$this->params['cat']);

            // Remove empty or nonpositive values from array
            if(!empty($category_ids))
            {
                foreach ($category_ids as $index => $value)
                {
                    if (empty($value) || $value < 1 || !is_numeric($value))
                    {
                        unset($category_ids[$index]);
                    }
                }
            }

            $category_ids = is_array($category_ids) ? implode (',',$category_ids) : $category_ids;

            $category_ids != '' and $this->params['cat'] = $category_ids;
        }
        elseif (isset($criteria_ids) && trim($criteria_ids) != '')
        {
            $criteria_ids = str_replace($urlSeparator,',',$criteria_ids);

            $criteria_ids != '' and $this->params['criteria'] = $criteria_ids;
        }
        elseif (isset($dir_id) && trim($dir_id) != '')
        {
            $dir_id = str_replace($urlSeparator,',',$dir_id);

            $dir_id != '' and $this->params['dir'] = $dir_id;
        }

        # Add search conditions to Listing model
        if($where != '' ) {

            $this->Listing->conditions[] = $where;
        }
        elseif ((
                    empty($this->Listing->conditions)
                    &&
                    $dir_id == ''
                    &&
                    $category_ids == ''
                    &&
                    $criteria_ids == ''
                    )
                 &&
                 !Sanitize::getBool($this->Config,'search_return_all',false))
        {
            return $this->render('listings','listings_noresults');
        }

        return $this->listings();
    }

    function __seo_fields(&$page, $cat_id = null)
    {
        $category = $parent_category = '';

        if($tag = Sanitize::getVar($this->params,'tag'))
        {
            $field = 'jr_'.$tag['field'];
//            $value = $tag['value'];
            // Field value underscore fix: remove extra menu parameter not removed in routes regex
            $value = preg_replace(array('/_m[0-9]+$/','/_m$/','/_$/','/:/'),array('','','','-'),$tag['value']);

            $query = "
                SELECT
                    fieldid,type,metatitle,metakey,metadesc
                FROM
                    #__jreviews_fields
                WHERE
                    name = ".$this->Quote($field)." AND `location` = 'content'
            ";

            $this->_db->setQuery($query);

            $meta = $this->_db->loadObjectList();

            if($meta)
            {
                $meta = $meta[0];

                $multichoice = array('select','selectmultiple','checkboxes','radiobuttons');

                if (in_array($meta->type,$multichoice))
                {
                    $query = "
                        SELECT
                            optionid, text
                        FROM
                            #__jreviews_fieldoptions
                        WHERE
                            fieldid = '{$meta->fieldid}' AND value = ".$this->Quote(stripslashes($value))
                        ;

                    $option = $this->Field->query($query,'loadAssocList');

                    $fieldValue = array_shift($option);

                    $fieldValue = $fieldValue['text'];
                }
                else {

                    $fieldValue = urldecode($value);
                }

                if($cat_id
                    && ( stristr($meta->metatitle.$meta->metakey.$meta->metadesc,'{category}')
                        || stristr($meta->metatitle.$meta->metakey.$meta->metadesc,'{parent_category}'))
                    )
                {
                    if($categories = $this->Category->findParents($cat_id)) {

                        $category_array = array_pop($categories);

                        $category = $category_array['Category']['title'];

                        if(!empty($categories)) {

                            $parent_category_array = array_pop($categories);

                            $parent_category = $parent_category_array['Category']['title'];

                        }

                    }

                }

                $search = array('{fieldvalue}','{category}','{parent_category}');

                $replace = array($fieldValue, $category, $parent_category);

                $page['title'] = $page['title_seo'] = $meta->metatitle == '' ? $fieldValue : trim(str_ireplace($search,$replace,$meta->metatitle));

                $page['keywords'] = $page['menuParams']['menu-meta_keywords'] = trim(str_ireplace($search,$replace,$meta->metakey));

                $page['description'] = $page['menuParams']['menu-meta_description'] = trim(str_ireplace($search,$replace,$meta->metadesc));

                $page['show_title'] = $this->Config->seo_title;

                $page['show_description'] = $this->Config->seo_description;

                if($page['show_description']) {

                    $page['top_description'] = $page['description'];
                }
            }
        }

    }
}