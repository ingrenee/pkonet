<?php
/**
 * @version       1.0.0 August 18, 2013
 * @author        ClickFWD http://www.reviewsforjoomla.com
 * @copyright     Copyright (C) 2010 - 2013 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */
defined('_JEXEC') or die;

class JReviewsRouter extends JRouter {

    protected $uri;

    var $remove_article_id = 1;

    var $use_core_cat_menus = 1;

    var $replacements = array(
        'com_jreviews'=>'jreviews',
        'newlisting'=>'new',
        'viewallreviews'=>'reviews',
        'preview'=>'preview',
        'photos'=>'photos',
        'videos'=>'videos',
        'searchresults'=>'search-results',
        'upload'=>'upload',
    );

    var $sef_suffix;

    var $sef_rewrite;

    var $suffix_str = '.html';

    var $plugin;

    public function __construct($params, $plugin) {

        $this->plugin = & $plugin;

        $this->remove_article_id = $params->get('remove_article_id', 1);

        $this->use_core_cat_menus = $params->get('use_core_cat_menus', 0);

        $this->replacements['com_jreviews'] = $params->get('replacement_com_jreviews', 'jreviews');

        $this->replacements['viewallreviews'] = $params->get('replacement_viewallreviews', 'reviews');

        $this->replacements['photos'] = $params->get('replacement_photos', 'photos');

        $this->replacements['videos'] = $params->get('replacement_videos', 'videos');

        $this->replacements['searchresults'] = $params->get('replacement_searchresults', 'search-results');

        if($this->use_core_cat_menus)
        {
            define('JREVIEWS_SEF_PLUGIN',1);
        }

        $JConfig = JFactory::getConfig();

        $this->sef_rewrite = $JConfig->get('sef_rewrite');

        $this->sef_suffix = $params->get('sef_suffix');

        parent::__construct();
    }

    static function prx($var)
    {
        echo '<pre>'.print_r($var,true).'</pre>';
    }

    static function getArrVal($array, $key, $default = null)
    {
        if(isset($array[$key])) {

            return $array[$key];
        }

        return $default;
    }

    protected function finalizeBuildRoute($route)
    {
        if(!$this->sef_rewrite)
        {
            $route = 'index.php/' . $route;
        }

        if($this->suffix_str != '' && $route != ''
            && $route !='/' && !stristr($route,'#') && $this->sef_suffix) {

            $route = $route . $this->suffix_str;
        }

        return $route;
    }

    protected function getPath()
    {
        $path = $this->uri->getPath();

        // Remove suffix if enabled

        if ($this->sef_suffix) {

            if ($suffix = pathinfo($path, PATHINFO_EXTENSION)) {

                $path = str_replace('.' . $suffix, '', $path);

                $this->uri->setPath($path);
            }
        }

        return $path;
    }

    public function &buildJReviews($siteRoute)
    {
        list($siteRouter, $uri) = func_get_args();

        $query = $uri->getQuery(true);

        $route = $uri->getPath(); // This will be 'index.php' for the home page

        $url = $uri->toString();

        $app  = JApplication::getInstance('site');

        $JMenu = $app->getMenu();

        $JInput = JFactory::getApplication()->input;

        $menu = $joomla_cat_menu = $curr_page_menu = $is_joomla_cat_menu = $params = $action_id = $cat_menu_id = null;

        $curr_page_menu_id = $JInput->get('Itemid');

        if($curr_page_menu_id) {

            $curr_page_menu = $JMenu->getItem($curr_page_menu_id);
        }

        // Read URL query parameters

        $option = self::getArrVal($query,'option');

        $view = self::getArrVal($query,'view');

        $menu_id = self::getArrVal($query,'Itemid');

        $menu_id_url = $JInput->get('Itemid', 'INT');

        if($menu_id)
        {
            $menu = $JMenu->getItem($menu_id);

            $menu and $action_id = (int) $menu->params->get('action');
        }

        $url_param = rtrim(self::getArrVal($query,'url'),'/');

        $action = self::getArrVal($query,'action');

        $cat_id_query = self::getArrVal($query,'cat');

        // Get category id from URL when a JReviews category menu doens't exit
        if($option == 'com_jreviews' && preg_match('/_c([0-9]+)/',$url_param,$matches)) {

            $cat_id_query = $matches[1];
        }

        $id = $id_backup = self::getArrVal($query,'id');

        $extension = self::getArrVal($query,'extension');

        $tmpl = JRequest::getVar('tmpl');

        $is_joomla_cat_url = false;

        /**
         * For JReviews category menus
         */
        /**
         * Skip this build method if conditions below are true
         * 1. It's a 3rd party extension URL
         * 2. It's a JReviews URL without a menu. Replace the component/jreviews segment and leave the rest the same
         */

        if(!in_array($option,array('com_content','com_jreviews'))
            // Content URL without a menu
            || ($option == 'com_content' && !$menu_id)
            // JReviews URL without a menu, as long as it is not a category URL and a Joomla category menu exists
            // And not
            || ($option == 'com_jreviews'
                    && $url_param != ''
                    && !$menu_id
                    && !$cat_id_query
                    && !$extension
                    && $url_param != 'listings/detail' // Not a view all reviews page
                    && !preg_match('/[\w].*_l[0-9]+/',$url_param) // Not a view all reviews page
                    && !strstr($url_param,'.rss') // Not a feed
                    && $action != 'xml' // Not a feed
                )
            // Directory URL
            || ($option == 'com_jreviews' && preg_match('/_d([0-9]+)$/',$url_param, $matches_dir))
            // Category URL
            || ($option == 'com_jreviews' && preg_match('/_c([0-9]+)$/',$url_param, $matches_cat) && !$menu_id)
            // RSS URL in category page without menu
            || ($option == 'com_jreviews' && preg_match('/url=.*_c[0-9]+.*&action=xml|_c([0-9]+)\.rss/',$url,$matches_cat) && !$menu_id)
            )
        {
            $skip = false;

            // Special case for directory links in directory module. They inherit current page Itemid which may not be the correct one.
            if(!empty($matches_dir) && $menu_id)
            {
                $dir_id = $menu->params->get('dirid');

                if($dir_id == $matches_dir[1]) $skip = true;
            }

            // Special case for rss links in category pages
            if(!empty($matches_cat))
            {
                $cat_id_menu = $this->getJoomlaCatMenu($matches_cat[1]);

                if($cat_id_menu) {

                    $skip = true;
                }
            }

            if(!$skip && $option == 'com_jreviews' && $url_param != '')
            {
                $route = $this->replacements['com_jreviews'] . '/' . $url_param;

                unset($query['Itemid'],$query['option'], $query['url']);

                $uri->setQuery($query);

                $uri->setPath($this->finalizeBuildRoute($route));
            }

            if(!$skip) return $uri;
        }

        /**
         * Re-write JReviews category URLs to native Joomla category URLs
         */
        if($option == 'com_jreviews')
        {
            /**************************************************************************************
             * First we'll set the base route. After that we modify it depending on the type of URL
             *************************************************************************************/

            // It's a JReviews link with the menu id of a Joomla category

            if($menu && $menu->query['option'] != $option) {

                $is_joomla_cat_url = true;

                $cat_id = $cat_id_query > 0 ? $cat_id_query : $menu->query['id'];

                if($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category') {

                    // Run the content router here so we can modify the segments

                    $query = array_merge($query,array(
                        'option'=>'com_content',
                        'view'=>'category',
                        'id'=>$cat_id,
                        'Itemid'=>$menu_id,
                        ));

                    require_once JPATH_SITE . '/components/com_content/router.php';

                    $route_class = 'ContentBuildRoute';

                    $segments = $route_class($query);

                    $slug = str_replace(':','-',implode('/', $segments));

                    $route = $menu->route . ($slug != ''? '/' . $slug : '');

                    if($url_param == 'preview')
                    {
                        $query['id'] = $id_backup;
                    }

                    // Prevent the default content router from running again

                    unset($query['option'], $query['Itemid'], $query['url'], $query['cat']);
                }
            }

            // It's a JReviews category menu. Lets see if there's a Joomla category menu to replace the alias.

            elseif($menu_id && $action_id == 2) {

                $cat_id_menu = $menu->params->get('catid');

                if($cat_id_menu > 0
                        && $url_param != 'search_results' /* don't use category alias for search URLS*/ ) {

                    $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id_menu);

                    if($this->use_core_cat_menus && $joomla_cat_menu) {

                        $route = $joomla_cat_menu->route; // Use the Joomla category route

                    }
                    else {

                        $route = $menu->route; // Use the JReviews category route

                        unset($query['cat']);
                    }
                }

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // Reviewer rank. If it has a user anchor, remove the 'menu' segment
            elseif($menu_id && $action_id == 18)
            {
                $route = str_replace('/menu','',$menu->route);

                unset($query['Itemid'],$query['option'],$query['url']);

                $uri->setPath($this->finalizeBuildRoute($route));

                $uri->setQuery($query);

                return $uri;
            }

            // JReviews category menu not found, so we find the Joomla category menu to display that instead

            elseif($cat_id_query && $url_param != 'search-results') {

                $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id_query);

                if($joomla_cat_menu)
                {
                    $menu_id = $joomla_cat_menu->id;

                    $route = $route = $joomla_cat_menu->route;

                    unset($query['cat']);
                }
                else {

                    // Neither JReviews nor Joomla category menus found, so we use the 'com_jreviews' segment

                    $route = $this->replacements['com_jreviews'];
                }

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // It's a JReviews URL, but not a menu because it has parameters

            elseif(!$menu && $url_param != '') {

                $route = $this->replacements['com_jreviews'];

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // It's a JReviews menu

            elseif($menu) {

                $route = $menu->route;

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            /************************************************************************
             * We have the base route, now we add any additional segments required
             *************************************************************************/

            $patterns = array(
                'alphaindex',
                'discussions\/review',
                'new-listing',
                'rss',
                'tag',
                '^preview$',
                '^photos$',
                'media\/listing',
                'media\/photoGallery',
                '^videos$',
                'media\/videoGallery',
                '^upload$',
                '^search-results$',
                'listings\/detail',
                'categories\/category',
                'categories\/search',
                '_l[0-9]+$',
            );

            preg_match('/'. implode('|',$patterns) .'/',$url_param,$matches);

            $page_type = !empty($matches) ? $matches[0] : '';

            preg_match('/(?<alias>[\w].*)(?<viewallreviews>_l)(?<id>[0-9]+|)/',$url_param, $typematch);

            if($page_type != 'rss' && isset($typematch['viewallreviews'])) {

                $page_type = 'viewallreviews';
            }

            if($action == 'xml') $page_type = 'rss';

            switch($page_type)
            {
                case 'alphaindex':

                    preg_match('/alphaindex_([\p{L}\s0]{1})+/isu',$url_param, $matches);

                    $index = !empty($matches) ? $matches[1] : $query['index'];

                    $route = $menu->route . '/' . 'index' . '/' . $index;

                    unset($query['index'], $query['dir']);

                break;

                case 'discussions/review':

                    if($is_joomla_cat_url || $action_id == 17) {

                        $route .= '/' . $url_param;

                        $query['id'] = $id;
                    }
                    else {

                        $menu_discussions = $JMenu->getItems(array('link'),array('index.php?option=com_jreviews&view=discussions'),true);

                        if($route == $this->replacements['com_jreviews'] && $menu_discussions)
                        {
                            $route = $menu_tmp->route;
                        }

                        $route .= '/' . $url_param;
                    }

                break;

                case 'new-listing':

                    if($menu && $menu->query['option'] == 'com_jreviews' && $action_id === 0)
                    {
                        $route = $menu->route . '/' . $this->replacements['newlisting'] . '/'. $cat_id_query;
                    }
                    else {

                        $route .= '/' . $this->replacements['newlisting'];
                    }

                break;

                case 'search-results':

                    // With adv. search alias or custom Itemid parameter
                    if($action_id == 11
                        || (!$cat_id_query && $menu->query['option'] == 'com_jreviews')
                        || ($cat_id_query && $menu->query['option'] == 'com_jreviews' && $menu->params->get('catid') != $cat_id_query))
                    {
                        $route = $menu->route . '/' . $this->replacements['searchresults'];
                    }
                    else {

                        $route = $this->replacements['com_jreviews'] . '/' . $this->replacements['searchresults'];
                    }

                break;

                case 'rss':

                    // Listing detail page review feed
                    if(preg_match('/(?<alias>.*)_l(?<id>[0-9]+)_(?<extension>com_[0-9a-z_]*)[.]rss/', $url_param, $matches))
                    {
                        $article_menu = null;

                        if(isset($matches['id']) && ($extension == 'com_content' || $extension == '')) {

                            $article_menu = $this->getJoomlaArticleMenu($matches['id']);
                        }

                        if($article_menu)
                        {
                            $route = $article_menu->route . '/rss';
                        }
                        elseif($extension == 'com_content') {

                            $route .=  '/' . $matches[1] .'/rss';

                            unset($query['extension']);
                        }
                        else {

                            $route .= '/' . (!$this->remove_article_id ? $matches['id'] . '-' : '') . $matches['alias'] . '/rss';

                            if($matches['extension'] != 'com_content') {

                                $query['extension'] = $matches['extension'];
                            }
                            else {

                                unset($query['extension']);
                            }
                        }
                    }

                    // Feeds in directory page with menu
                    elseif(($action_id === 0 && $curr_page_menu->query['option'] == 'com_jreviews')
                            || ($route == $this->replacements['com_jreviews'] && isset($query['dir']))
                    )
                    {
                        if(strstr($url_param,'categories/latest'))
                        {
                            if($menu && $action_id === 0 && $menu->params->get('dirid') == $query['dir'])
                            {
                                $route = $menu->route . '/rss';
                            }
                            else {

                                $route = str_replace(JURI::base(),'',JURI::current()) . '/rss';
                            }
                        }
                        else {

                            $route .= '/rss/reviews';
                        }

                        unset($query['action'], $query['dir']);
                    }

                    // List page listing and review feeds

                    elseif(strstr($url_param,'.rss') || $action == 'xml')
                    {
                        if($action == 'xml')
                        {
                            $route .= '/rss';
                        }
                        else
                        {
                            $route = str_replace(JURI::base(),'',JURI::current()) . '/rss/reviews';
                        }

                        unset($query['action'], $query['dir'], $query['cat'], $query['order'], $query['page']);
                    }

                break;

                case 'categories/category':

                    unset($query['cat']);

                    $route = str_replace(JURI::base(),'',JURI::current());

                break;

                case 'categories/search':

                    $route .= '/' . $this->replacements['searchresults'];

                break;

                case 'listings/detail':

                    unset($query['id']);

                    $route = str_replace(JURI::base(),'',JURI::current());

                break;

                case 'tag':

                    if($cat_id_query && $is_joomla_cat_menu && $cat_id_query != $menu->query['id'])
                    {
                        $route = $this->replacements['com_jreviews'];
                    }
                    elseif($cat_id_query && $is_joomla_cat_menu && $cat_id_query == $menu->query['id'])
                    {
                        unset($query['cat']);
                    }

                    $route .= '/' . $url_param;

                break;

                case 'preview':

                    $query['id'] = $id_backup;

                    $route .= '/' . $this->replacements['preview'];

                break;

                case 'media/listing':

                    $query['id'] = $id_backup;

                    if($action_id == 101)
                    {
                        $route .= '/media/listing';
                    }
                    else {

                        $route = $this->replacements['com_jreviews'] . '/media/listing';
                    }

                break;

                case 'photos':
                case 'media/photoGallery':

                    $route .= '/' . $this->replacements['photos'];

                break;

                case 'videos':
                case 'media/videoGallery':

                    $route .= '/' . $this->replacements['videos'];

                break;

                case 'upload':

                    $route .= '/' . $this->replacements['upload'];

                break;

                case 'viewallreviews':

                    $article_menu = null;

                    if(isset($typematch['id']) && ($extension == 'com_content' || $extension == '')) {

                        $article_menu = $this->getJoomlaArticleMenu($typematch['id']);
                    }

                    if($article_menu)
                    {
                        $route = $article_menu->route . '/' . $this->replacements['viewallreviews'];
                    }
                    // It's a view all reviews catch all menu
                    elseif($menu && $action_id == 105) {

                        $route .= '/' . $typematch['id'] . '-' . $typematch['alias'];
                    }
                    elseif($menu && $menu->query['option'] == 'com_content' && ($extension == '' || $extension == 'com_content') && $this->remove_article_id) {

                        $route .= '/' . $typematch['alias'] . '/' . $this->replacements['viewallreviews'];
                    }
                    else {

                        $route .= '/' . $typematch['id'] . '-' . $typematch['alias'] . '/' . $this->replacements['viewallreviews'];
                    }

                break;

                default:

                    if($url_param && !$is_joomla_cat_url) {

                        $route .= '/' . $url_param;

                    // self::prx($url);
                    // self::prx($url_param);
                    }

                break;
            }

            unset($query['option'], $query['Itemid'], $query['url']);
        }
        elseif($option == 'com_content' && $view == 'article'
            && $menu->query['option'] == 'com_content'
            // && !$menu->query['view'] == 'article'
            )
        {
            // Run the content router here so we can modify the segments

            require_once JPATH_SITE . '/components/com_content/router.php';

            $route_class = 'ContentBuildRoute';

            $segments = $route_class($query);

            $last = array_pop($segments);

            if($last != '')
            {
                if($this->remove_article_id)
                {
                    list($article_id,$slug) = explode(':',$last);
                }
                else {

                    $slug = str_replace(':','-',$last);
                }

                // There are subcategories without menus

                if(!empty($segments)) {

                    foreach($segments AS $key=>$val)
                    {
                        $segments[$key] = str_replace(':','-',$val);
                    }
                }

                $segments[] = $slug;

                $route = $menu->route . '/' . implode('/', $segments);

                // Prevent the default content router from running again
                $query = '';
            }
        }

        if(empty($_POST) && !self::isAjax()) {

            $tmpl and $query['tmpl'] = $tmpl;
        }

        $uri->setPath($this->finalizeBuildRoute($route));

        $uri->setQuery($query);

        return $uri;
    }

    protected function setActiveMenu($path)
    {
        if($path == '') return false;

        $app  = JFactory::getApplication();

        $JMenu = $app->getMenu();

        $menu = $JMenu->getItems(array('route'),array($path),true);

        if(!$menu) {

            $segments = explode('/', $path);

            array_pop($segments);

            $path = implode('/', $segments);

            if($path != '') {

                $this->setActiveMenu($path);
            }
        }

        else {

            $JMenu->setActive($menu->id);
        }
    }

    public function &parseJReviews(&$siteRouter)
    {
        list($siteRouter, $uri) = func_get_args();

        $this->uri = $uri;

        $app  = JFactory::getApplication();

        $JMenu = $app->getMenu();

        $vars = array();

        $option = $id = $joomla_cat_menu = $page_type = null;

        $query = $uri->getQuery(true);

        $query_string = $uri->getQuery();

        $path = $path_orig = $this->getPath();

        $this->setActiveMenu($path);

        $menu = $JMenu->getActive();

        $action_id = null;

        $view = self::getArrVal($query,'view');

        $task = self::getArrVal($query,'task');

        if($menu && $menu->query['option'] == 'com_jreviews')  {

            $action_id = (int) $menu->params->get('action');
        }

        $segments = explode('/',$path);

        // If this is a post request, the home page then let Joomla handle it
        if(!empty($_POST)
                // Home page
                || empty($path)
                // Any non-content,non-jreviews url menu
                || ($menu && !in_array($menu->query['option'],array('com_content','com_jreviews')))
                // Any content menu that is not 'article' or 'category'
                || ($menu && $menu->query['option'] == 'com_content' && !in_array($menu->query['view'],array('article','category')))
                // Any non-content,non-jreviews url without a menu
                || (self::getArrVal($segments,0) == 'component' && !in_array(self::getArrVal($segments,1),array('jreviews','content')))
                // Or any non-menu content URL that has a 'task' parameter in it and is not an ugly article URL (without a menu)
                || (self::getArrVal($segments,0) == 'component' && self::getArrVal($segments,1) == 'content' && self::getArrVal($segments,2) != 'article' && !in_array($view,array('article','category')))
                // Any url that matches a menu route exactly and that is not com_content, except article menus,
                // because we need to process extra segments
                || ($menu && $menu->query['option'] == 'com_jreviews' && $menu->route == $path && $action_id != 2)
                // Article menu
                || ($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'article' && $path == $menu->route)
            ) {

            return $vars;
        }

        // First remove any of the extra segments added for additional functionaly: new, feeds, index

        $patterns_simple = array(
            'discussions\/review',
            'my-reviews',
            'new',
            'rss',
            'rss\/reviews',
            'media\/listing',
            'listings\/edit',
            $this->replacements['preview'],
            $this->replacements['photos'],
            $this->replacements['videos'],
            $this->replacements['searchresults'],
            $this->replacements['viewallreviews'],
            $this->replacements['upload']
        );

        $first = reset($segments);

        if(preg_match('/\/('.implode('|',$patterns_simple).')$|\/(index|tag|new)/', $path, $matches))
        {
            $page_type = array_pop($matches);

            $path = str_replace('/' . $page_type,'',$path);
        }

        $segments = explode('/',$path);

        // Some further checking of page type
        if(!$page_type)
        {
            switch($action_id)
            {
                case 105:
                    $page_type = $this->replacements['viewallreviews'];
                break;
            }
        }

        // JReviews URL without a menu

        if($first == $this->replacements['com_jreviews'] && !in_array($page_type,array('rss')))
        {
            $segments[0] = 'component/jreviews';

            $path = implode('/',$segments);

            $vars['url'] = implode('/',array_slice($segments, 1));

            $uri->setPath($path);

        }
        // It's a menu based URL

        elseif(
            (count($segments) == 1 && $menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
            ||
            ($menu && $menu->route == $path_orig)
            // ||
            // ($menu && $menu->query['option'] == 'com_jreviews' && $action_id === 0)
            ) {

            // Don't do any special processing for non-JReviews categories

            if($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category' && !$this->isJReviewsCategory($menu->query['id']))
            {
                return $vars;
            }

            if($this->use_core_cat_menus) {

                // If it's a JReviews category we redirect to the Joomla equivalent if found

                $this->jreviewsCatRedirect($menu);
            }

            // If it's a JReviews non-category menu, then there's no need for further processing
            if(count($segments) == 1 && $menu && $menu->query['option'] == 'com_jreviews' && $action_id != 2)
            {
                $vars = $menu->query;

                return $vars;
            }

            unset($menu->query['layout']); // It gets confused with the JReviews theme suffix

            $vars = $menu->query;
        }

        // Use native content router when the URL structure cannot be matched directly to a menu
        elseif((!$menu && count($segments) > 3 && $segments[1] == 'content' && $segments[2] == 'article')
            ||
            ($menu && $menu->query['option'] == 'com_content' && !in_array($page_type,array('tag',$this->replacements['newlisting'])))) {

            // This is an article URL without the required menu
            if($segments[1] == 'content' && $segments[2] == 'article') {

                array_shift($segments);

                array_shift($segments);

                array_shift($segments);

                $this->redirectUglyContentArticleURL($segments, $page_type);
            }

            require_once JPATH_SITE . '/components/com_content/router.php';

            if($menu && count($segments) > 1) array_shift($segments);

            $last = array_pop($segments);

            $last = preg_replace('/^([0-9]+)(-)(.*)/','$1:$3',$last);

            $segments[] = $last;

            if($this->remove_article_id
                // && $page_type == '' /* only do this for article URLs. If it has an extra segment appended, then process as JReviews URL*/
            ) {
                // Get back the article id if it was removed

                $segments = $this->addBackID($menu, $segments);
            }

            $vars = ContentParseRoute($segments);

            if($segments == array_values($vars)) {

                $vars = array();

                return $vars;
            }

            if($vars['view'] == 'article' && !in_array($page_type,array('rss',$this->replacements['viewallreviews'])))
            {
                if($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category') {

                    $vars['catid'] = $menu->query['id'];
                }

                $this->setCanonical($path);

                return $vars;
            }
            elseif($vars['view'] == 'category' && $vars['id'] == 0 && $page_type == 0) {

                return JError::raiseError(404, JText::_('JGLOBAL_RESOURCE_NOT_FOUND'));
            }
        }

        // Category page or menu based pages

        if($menu
            && (
            ($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
            ||
            ($menu->query['option'] == 'com_content' && $menu->query['view'] == 'article')
            ||
            ($menu->query['option'] == 'com_jreviews')
            // ($menu->query['option'] == 'com_jreviews' && $action_id == 2) /* category */
            // ||
            // ($menu->query['option'] == 'com_jreviews' && $action_id === 0) /* directory */
            // ||
            // ($menu->query['option'] == 'com_jreviews' && $action_id == 11) /* adv. search */
            // ||
            // ($menu->query['option'] == 'com_jreviews' && $action_id == 101) /* media catch-all */
            )) {

            if($menu->query['view'] != 'article') {

                $vars['option'] = 'com_jreviews';
            }

            // Need to set the Itemid using the menu id of the Joomla category menu
            // for proper module assignments to Joomla category pages

            $vars['Itemid'] = $menu->id;

            $id = self::getArrVal($vars,'id');

            if($id == 0) unset($vars['id']);

            $last = end($segments);

            if($menu->query['option'] == 'com_jreviews' && (
                $action_id == 2
                ||
                ($action_id === 0 && preg_match('/_c(?<catid>[0-9]+)/',$last,$cat_matches))
            ))
            {
                // JReviews Category URL without a menu
                if(isset($cat_matches['catid'])) {

                    $vars['cat'] = $cat_matches['catid'];

                    $page_type = '';
                }
                else {

                    !$page_type and $page_type = 'jreviews_category_menu';

                    $vars['cat'] = $menu->params->get('catid');
                }
            }
            elseif($id > 0) {

                $vars['cat'] = $vars['id'];
            }
            elseif(isset($menu->query['id'])) {

                $vars['cat'] = $menu->query['id'];
            }

            switch($page_type) {

                case 'index':

                    $path = $this->getPath();

                    preg_match('/(.*)\/index\/([\p{L}\s0]{1}).*/isu',$path,$matches);

                    $dir_id = $menu->params->get('dirid');

                    $vars['dir'] = $dir_id;

                    $vars['url'] = 'categories/alphaindex';

                    $vars['index'] = $matches[2];

                break;

                case 'discussions/review':

                    $vars['url'] = 'discussions/review';

                break;

                case 'media/listing':

                    $vars['url'] = 'media/listing';

                break;

                case 'listings/edit':

                    $vars['url'] = 'listings/edit';

                break;

                case 'new':

                    if($action_id === 0) {

                        $vars['cat'] = $last;
                    }

                    $vars['url'] = 'listings/create';

                break;

                case 'rss':

                    if($menu->query['option'] == 'com_content' && self::getArrVal($vars,'view') == 'article') {

                        unset($vars['cat'], $vars['catid']);

                        $vars['url'] = 'feeds/reviews';
                    }
                    elseif($menu->query['option'] == 'com_jreviews' && $action_id === 0)
                    {
                        $vars['dir'] = $menu->params->get('dirid');

                        $vars['url'] = 'categories/latest';

                        $vars['action'] = 'xml';
                    }
                    else {

                        unset($vars['id']);

                        $vars['url'] = 'categories/category';

                        $vars['action'] = 'xml';
                    }

                    unset($vars['view']);

                break;

                case 'rss/reviews':

                    $vars['url'] = 'feeds/reviews';

                    unset($vars['id']);

                    if($menu->query['option'] == 'com_jreviews' && $action_id === 0)
                    {
                        $vars['dir'] = $menu->params->get('dirid');
                    }

                break;

                case $this->replacements['viewallreviews']:

                    unset($vars['cat']);

                    if($menu && $menu->query['option'] == 'com_jreviews') {

                        $vars['id'] = (int) end($segments);
                    }

                    $vars['url'] = 'listings/detail';

                break;

                case 'tag':

                    $value = array_pop($segments);

                    $field = array_pop($segments);

                    $vars['url'] = 'tag/' . $field . '/' . $value;

                    if($this->use_core_cat_menus) {

                        // If it's a JReviews category we redirect to the Joomla equivalent if found

                        $this->jreviewsCatRedirect($menu, $vars['url']);
                    }

                break;

                case $this->replacements['preview']:

                    $vars['url'] = 'com_content/com_content_view';

                    $vars['preview'] = 1;

                break;

                case $this->replacements['photos']:

                    unset($vars['view'],$vars['layout'],$vars['id']);

                    $vars['url'] = 'media/photoGallery';

                break;

                case $this->replacements['videos']:

                    unset($vars['view'],$vars['layout'],$vars['id']);

                    $vars['url'] = 'media/videoGallery';

                break;

                case $this->replacements['searchresults']:

                    $vars['url'] = 'categories/search';

                break;

                case $this->replacements['upload']:

                    $vars['url'] = 'media_upload/create';

                break;

                case '':

                    $vars['url'] = 'categories/category';

                break;
            }
        }

        // There's no menu
        else {

            $vars['option'] = 'com_jreviews';

            switch($page_type)
            {
                case 'index':

                    $path = $this->getPath();

                    preg_match('/(.*)\/index\/([\p{L}\s0]{1}).*/isu',$path,$matches);

                    $menu = $JMenu->getItems(array('route'),array($matches[1]),true);

                    $dir_id = $menu->params->get('dirid');

                    if($action_id == 0) {

                        $vars['dir'] = $dir_id;
                    }

                    $vars['Itemid'] = $menu->id;

                    $vars['url'] = 'categories/alphaindex';

                    $vars['index'] = $matches[2];

                break;

                case 'media/listing':

                    $vars['url'] = 'media/listing';

                break;

                case 'listings/edit':

                    $vars['url'] = 'listings/edit';

                break;

                case 'my-reviews':

                    $vars['url'] = 'reviews/myreviews';

                break;

                case $this->replacements['preview']:

                    $vars['url'] = 'com_content/com_content_view';

                    $vars['preview'] = 1;

                break;

                case $this->replacements['viewallreviews']:

                    $vars['url'] = 'listings/detail';

                    $vars['id'] = (int) $last;

                break;

                case 'rss':

                    //  Latest listings feed for directory url without menu

                    if(preg_match('/_d(?<dirid>[0-9]+)$/',$last,$matches_dir)) {

                        if(isset($matches_dir['dirid'])) {

                            $vars['dir'] = $matches_dir['dirid'];

                            $vars['url'] = 'categories/latest';

                            $vars['action'] = 'xml';
                        }
                    }
                    elseif(isset($vars['url'])) {

                        // Everywhere extension detail page review feeds
                        $vars['id'] = (int) $vars['url'];

                        $vars['url'] = 'feeds/reviews';
                    }
                    elseif($menu && $menu->query['view'] == 'category') {

                        $vars['Itemid'] = $menu->id;

                        $vars['action'] = 'xml';
                    }

                break;

                case 'rss/reviews':

                    if(preg_match('/_d(?<dirid>[0-9]+)$/',$last,$matches_dir)) {

                        if(isset($matches_dir['dirid'])) {

                            $vars['dir'] = $matches_dir['dirid'];

                            $vars['url'] = 'feeds/reviews';
                        }
                    }

                break;

                case 'tag':

                    $value = array_pop($segments);

                    $field = array_pop($segments);

                    $vars['url'] = 'tag/' . $field . '/' . $value;

                break;

                case $this->replacements['upload']:

                    $vars['url'] = 'media_upload/create';

                break;

                default:

                    if($page_type) {

                        $vars['url'] = $page_type;
                    }
                    elseif(isset($last) && $last !='')
                    {

                        $vars['url'] = $last;
                    }

                break;
            }
        }

        $canonical_path = $this->getPath();

        $this->setCanonical($canonical_path);

        //Set the route
        $uri->setPath('');

        $query_string = JURI::buildQuery($vars) . ($query_string != '' ? '&' . $query_string : '');

        $uri->setQuery($query_string);

        return $vars;
    }

    protected static function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }

    protected function addBackID($menu, $segments)
    {
        $segments_bak = $segments;

        if($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
        {
            $db = JFactory::getDBO();

            $cat_id = (int) $menu->query['id'];

            $last = array_pop($segments);

            $id = (int) $last;

            // There are subcategories without menus. We need to get the correct cat id.

            if(!empty($segments))
            {
                $category = end($segments);

                if((int) $category > 0) {

                    $cat_id = (int) $category;
                }
            }

            if((is_numeric($last) && $id == $last) || (!is_numeric($last) && $id == 0)) {

                $alias = $last;

                $sql = "
                    SELECT
                        id
                    FROM
                        #__content
                    WHERE
                        catid = " . $cat_id . " AND alias = '" . $db->escape($alias) . "'"
                ;

                $id = $db->setQuery($sql)->loadResult();

                if($id) {

                    $segments[] = $id . '-' . $alias;

                    return $segments;
                }
            }

            // The id is already in the URL. Redirect to the no-id version if the article exists in this category

            elseif(!is_numeric($last) && $id > 0)
            {
                $sql = "
                    SELECT
                        catid, alias
                    FROM
                        #__content
                    WHERE
                        id = " . $id
                ;

                $article = $db->setQuery($sql)->loadObjectList();

                if($article) $article = $article[0];

                $alias = $article->alias;

                if($article && $cat_id == $article->catid)
                {
                    $middle = !empty($segments) ? '/' . implode('/', $segments) : '';

                    $url = JURI::base() . $menu->route . $middle . '/' . $alias;

                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $url);

                    die();
                }
                // The menu route doesn't match the category id for the article
                elseif($article)
                {
                    $sql = "
                        SELECT
                            ParentCategory.id
                        FROM
                            #__categories AS Category,
                            #__categories AS ParentCategory
                        WHERE
                             ParentCategory.parent_id > 0
                            AND Category.id = " . (int) $article->catid . "
                            AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                    ";

                    $parent_catids = $db->setQuery($sql)->loadAssocList('id');

                    $cat_ids = array_reverse($parent_catids);

                    foreach($cat_ids AS $cat)
                    {
                        $menu = $this->getJoomlaCatMenu($cat['id']);

                        if($menu) {

                            $url = JURI::base() . $menu->route . '/' . (!$this->remove_article_id ? $id . '-' : '') . $article->alias;

                            header("HTTP/1.1 301 Moved Permanently");

                            header("Location: " . $url);

                            die();
                        }
                    }

                    return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
                }
                // It's an article that starts with a number in the alias. Try to find the article id only using the alias
                else {

                    $sql = "
                        SELECT
                            id
                        FROM
                            #__content
                        WHERE
                            alias = '" . $db->escape(str_replace(':','-',$last)) . "'
                            AND catid = " . $cat_id
                    ;

                    $id = $db->setQuery($sql)->loadResult();

                    if($id) {

                        $segments[] = $id . '-' . $alias;

                        return $segments;
                    }
                }
                /**
                 * Else, it's a subcategory URL without a menu so we continue processing outside this function
                 */
            }
        }

        return $segments_bak;
    }

    /**
     * Redirects /component/content/article urls to the sef urls when required menus are created
     * @param  [type] $segments       [description]
     * @param  [type] $extra_segments [description]
     * @return [type]                 [description]
     */
    protected function redirectUglyContentArticleURL($segments, $extra_segments)
    {
        $url = null;

        $cat_id = (int) array_shift($segments);

        $id = (int) array_shift($segments);

        if($cat_id && $id) {

            $article_menu = $this->getJoomlaArticleMenu($id);

            if($article_menu) {

                $url = $article_menu->route;
            }
            else {

                $cat_id_menu = $this->getJoomlaCatMenu($cat_id);

                if($cat_id_menu)
                {
                    $db = JFactory::getDBO();

                    $sql = "
                        SELECT
                            alias
                        FROM
                            #__content
                        WHERE
                            id = " . $id . "
                            AND catid = " . $cat_id
                    ;

                    $alias = $db->setQuery($sql)->loadResult();

                    if($alias)
                    {
                        $url = $cat_id_menu->route;

                        if($this->remove_article_id)
                        {
                            $url .= '/' . $alias;
                        }
                        else {

                            $url .= '/' . $id . '-' . $alias;
                        }
                    }
                }
            }
        }

        if($url)
        {
            if(!empty($extra_segments)) {

                $extra_segments = '/' . $extra_segments;
            }

            $query_string = $this->uri->getQuery();

            $query_string =  ($query_string != '' ? '?' . $query_string : '');

            $url = JURI::base() . $url . $extra_segments . $query_string;

            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $url);

            die();
        }
    }

    protected function jreviewsCatRedirect($menu, $extra_segments = '')
    {
        $cat_id = null;

        $path = $this->getPath();

        $action_id = (int) $menu->params->get('action');

        if(($menu->query['option'] == 'com_jreviews' && $action_id == 2)
            ||
            ($menu->query['option'] == 'com_jreviews' && $action_id === 0 && $path != $menu->route))
        {
            $query_string = $this->uri->getQuery();

            $query_string =  ($query_string != '' ? '?' . $query_string : '');

            $extra_segments != '' and $extra_segments = '/' . $extra_segments;

            if($action_id === 0)
            {
                $path = $this->getPath();

                $segments = explode('/', $path);

                $last = end($segments);

                preg_match('/_c(?<catid>[0-9]+)/', $last, $matches);

                if(isset($matches['catid'])) {

                    $cat_id = $matches['catid'];
                }
            }
            else {

                $cat_id = $menu->params->get('catid');
            }

            if($cat_id) {

                $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id);

                if($joomla_cat_menu)
                {
                    $url = JURI::base() . $joomla_cat_menu->route . $extra_segments . $query_string;

                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $url);

                    die();
                }
            }
        }
    }

    protected function setCanonical($path)
    {
        $query = $this->uri->getQuery(true);

        $query_string = '';

        $vars = array();

        if(isset($query['page'])) {

            $vars['page'] = $query['page'];

            $query_string = JURI::buildQuery($vars);
        }

        $this->plugin->canonical_url = JURI::base() . $path . ($query_string != '' ? '?' . $query_string : '');
    }

    protected function getJoomlaArticleMenu($id)
    {
        $app  = JApplication::getInstance('site');

        $JMenu = $app->getMenu();

        $menu = $JMenu->getItems(array('link'),array('index.php?option=com_content&view=article&id='.$id),true);

        if($menu) return $menu; else return false;
    }

    protected function getJoomlaCatMenu($cat_id)
    {
        $app  = JApplication::getInstance('site');

        $JMenu = $app->getMenu();

        $menu = $JMenu->getItems(array('link'),array('index.php?option=com_content&view=category&layout=blog&id='.$cat_id),true);

        if(!$menu)
        {
            $menu = $JMenu->getItems(array('link'),array('index.php?option=com_content&view=category&id='.$cat_id),true);
        }

        return $menu;
    }

    protected function isJReviewsCategory($cat_id)
    {
        $db = JFactory::getDBO();

        $sql = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories
            WHERE
                id = " . (int) $cat_id . " AND `option` = 'com_content'"
        ;

        $count = $db->setQuery($sql)->loadResult();

        return $count;
    }

}