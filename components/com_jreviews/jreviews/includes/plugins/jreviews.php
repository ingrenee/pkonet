<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.');

// Stops /cli/finder_indexer.php from running this file
if(isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] == 'finder_indexer.php') {

    return;
}

if(!function_exists('plgStringToArray')) {

    function plgStringToArray($string, $separator = "\n")
    {
        $version = new JVersion();

        if($version->RELEASE >= 1.6) return json_decode($string,true); /*J16*/

        $out = array();

        $array = explode($separator,$string);

        foreach($array as $key => $value) {
            if($value != '') {
                $pos = strpos( $value, '=' );
                $property = trim( substr( $value, 0, $pos ));
                $pvalue = trim( substr( $value, $pos + 1 ) );
                $out[$property] = $pvalue;
            }
        }

        return $out;
    }

    $option = JRequest::getVar('option', '');
    $view = JRequest::getVar('view', '');
    $layout = JRequest::getVar('layout', '');
    $id = JRequest::getVar('id');

    if ($option != 'com_content' && $option  != 'com_frontpage' && $option != '') {
        return;
    }

    $database = JFactory::getDBO();

    $version = new JVersion();

    $query = "
        SELECT
            enabled AS published, params
        FROM
            #__extensions
        WHERE
            element = 'jreviews' AND type = 'plugin' AND folder = 'content' LIMIT 1";

    $database->setQuery($query);

    $pluginSetup = current($database->loadObjectList());

    $params = plgStringToArray($pluginSetup->params);

    if (!$pluginSetup->published) return;

    $frontpageOff = isset($params['frontpage']) && $params['frontpage'] == 1;
    $blogLayoutOff = isset($params['blog']) && $params['blog'] == 1;

    # Get theme, suffix and load CSS so it's not killed by the built-in cache

    if ($blogLayoutOff && $option=='com_content' && ($view == 'category') && ($layout == 'blog' || $layout == 'blogfull')) {
        return;
    }
    elseif (($frontpageOff && ($view == 'frontpage' || $view == 'featured'))) {
        return ;
    }

    require($root . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php');

    jimport('joomla.plugin.plugin');

    class plgContentJreviews extends JPlugin
    {
        var $cms_version = '';

        function plgContentJreviews(& $subject, $params )
        {
            $version = new JVersion();
            $this->cms_version = $version->RELEASE;
            if(!$this->checkJreviewsCategory($subject, $params)) return;
            parent::__construct( $subject, $params );
        }

        private function checkJreviewsCategory($subject, $params)
        {
            $option = JRequest::getVar('option', '');
            $view = JRequest::getVar('view', '');
            $layout = JRequest::getVar('layout', '');
            $id = (int) JRequest::getVar('id');
            $database = JFactory::getDBO();

            if($view == 'category') {

                $query = "SELECT count(*) FROM #__jreviews_categories WHERE id = " . $id;

                $database->setQuery($query);

                $count = $database->loadResult();

                return $count;
            }
            elseif ($view == 'article') {

                return $this->articleinJReviewsCategory($id);
            }
            elseif ($view == 'featured') {

                return true;
            }
        }

        private function articleinJReviewsCategory($id)
        {
            $database = JFactory::getDBO();
            $query = "SELECT catid FROM #__content WHERE id = " . $id;
            $database->setQuery($query);
            $catid = $database->loadResult();
            if($catid)
            {
                $query = "
                    SELECT
                        count(*)
                    FROM
                        #__jreviews_categories AS Category
                    WHERE
                        Category.option = 'com_content'
                        AND
                        Category.id = " . $catid;

                $database->setQuery($query);
                $count = $database->loadResult();
                return $count;
            }
            return false;
        }

        function onContentBeforeDisplay( $context, &$article, &$params) /*J16*/
        {
            /***********************************************************************
            * BELOW BLOCK HERE BECAUSE J16 DOESN'T MAKE THE WHOLE ARTICLE OBJECT
            * AVAILABLE IN THE ONCONTENTPREPARE CALLBACK IN BLOG LAYOUT PAGES
            ***********************************************************************/
            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

            $this->setCmsVersion();

            // Check whether to perform the replacement or not
            $option = Sanitize::getString($_REQUEST, 'option', '');
            $view = Sanitize::getString($_REQUEST, 'view', '');
            $layout = Sanitize::getString($_REQUEST, 'layout', '');
            $id = Sanitize::getInt($_REQUEST,'id');

    		if($option == 'com_content' && $view == 'featured') {
    			if(!$this->articleinJReviewsCategory($article->id)) return;
    		}

            if($option == 'com_content' && ($layout == 'blog' || $view == 'featured'))
            {
                $row = &$article;
                $row->text = &$row->introtext;
                if(
                    (isset($row->params) || isset($row->parameters))
                    && isset($row->id) && $row->id > 0
                    && isset($row->catid) && $row->catid > 0
                ) {
                    $cache_file = S2CacheKey('jreviews_config');

                    $Config = S2Cache::read($cache_file,'_s2framework_core_');

                    $debug = false;

                    $debug_php = Sanitize::getBool($Config,'debug_enable',false);

                    $debug_ipaddress = Sanitize::getString($Config,'debug_ipaddress');

                    if($debug_php &&
                        ($debug_ipaddress == '' || $debug_ipaddress == s2GetIpAddress())) {

                        $debug = true;
                    }

                    $Dispatcher = new S2Dispatcher(array('app'=>'jreviews','debug'=>$debug));

                    if ($option=='com_content' && $view == 'article' & $id > 0)
                    {
                        $_GET['url'] = 'com_content/com_content_view';
                    }
                    elseif ($option=='com_content' && (($layout == 'blog' && $view=='category') || $view == 'featured'))
                    {
                        $_GET['url'] = 'com_content/com_content_blog';
                    }

                    $passedArgs = array(
                        'params'=>$params,
                        'row'=>$row,
                        'component'=>'com_content'
                        );

                    $passedArgs['cat'] = $row->catid;
                    $passedArgs['listing_id'] = $row->id;

                    $output = $Dispatcher->dispatch($passedArgs);

                    if($output)
                    {
                        $row = &$output['row'];
                        unset($params);
                        $params = &$output['params'];
                    }

                    /**
                    * Store a copy of the $listing and $crumbs arrays in memory for use in the onBeforeDisplayContent method
                    */
                    ClassRegistry::setObject(array('listing'=>&$output['listing'],'crumbs'=>&$output['crumbs']),'jreviewsplugin');

                    // Destroy pathway
                    if(!empty($output['crumbs']))
                    {
                        cmsFramework::setPathway(array());
                    }

                    unset($output,$passedArgs,$Dispatcher);
                }
            }
            /***********************************************************************
            * ABOVE BLOCK HERE BECAUSE J16 DOESN'T MAKE THE WHOLE ARTICLE OBJECT
            * AVAILABLE IN THE ONCONTENTPREPARE CALLBACK IN BLOG LAYOUT PAGES
            ***********************************************************************/

            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

           // Make sure this is a Joomla article page
            $option = Sanitize::getString($_REQUEST, 'option', '');

            $view = Sanitize::getString($_REQUEST, 'view', '');

            $layout = Sanitize::getString($_REQUEST, 'layout', '');

            $id = Sanitize::getInt($_REQUEST,'id');

            if(!($option == 'com_content' && $view == 'article' && $id)) return;

            /**
            * Retrieve $listing array from memory
            */
            $Config = Configure::read('JreviewsSystem.Config');

            if(Sanitize::getInt($Config,'override_listing_title')) {

                $title = '{title}';
            }
            else {

                $title = trim(Sanitize::getString($Config,'type_metatitle'));
            }

            if($this->cms_version >= 1.6) {

                // Allow title override via article menu
                if(!$params->exists('display_num') && $params->get('page_title') != '') {

                    $params->set('page_title',$params->get('page_title'));
                }
                else {

                    $params->set('page_title',$article->title); // Fixes J16 bug that uses cat menu title as page title
                }
            }

            $keywords = trim(Sanitize::getString($Config,'type_metakey'));

            $description = trim(Sanitize::getString($Config,'type_metadesc'));

            $listing = classRegistry::getObject('listing','jreviewsplugin'); // Has all the data that's also available in the detail.thtml theme file so you can create any sort of conditionals with it

            $crumbs = classRegistry::getObject('crumbs','jreviewsplugin');

            if($title != '' || $keywords != '' || $description != '')
            {

                if($listing && is_array($listing))
                {
                    // Get and process all tags
                    $tags = plgContentJreviews::extractTags($title.$keywords.$description);
                    $tags_array = array();
                    foreach($tags AS $tag)
                    {
                        switch($tag)
                        {
                            case 'title':
                                $tags_array['{title}'] = Sanitize::stripAll($listing['Listing'],'title');
                            break;
                            case 'directory':
                                $tags_array['{directory}'] = Sanitize::stripAll($listing['Directory'],'title');
                            break;
                            case 'category':
                                $tags_array['{category}'] = Sanitize::stripAll($listing['Category'],'title');
                            break;
                            case 'metakey':
                                $tags_array['{metakey}'] = Sanitize::stripAll($listing['Listing'],'metakey');
                            break;
                            case 'metadesc':
                                $tags_array['{metadesc}'] = Sanitize::stripAll($listing['Listing'],'metadesc');
                            break;
                            case 'summary':
                                $tags_array['{summary}'] = Sanitize::htmlClean(Sanitize::stripAll($listing['Listing'],'summary'));
                            break;
                            case 'description':
                                $tags_array['{description}'] = Sanitize::htmlClean(Sanitize::stripAll($listing['Listing'],'description'));
                            break;
                            default:
                                if(substr($tag,0,3) == 'jr_')
                                {
                                    $fields = $listing['Field']['pairs'];
                                    $tags_array['{'.$tag.'}'] = isset($listing['Field']['pairs'][$tag]) && isset($fields[$tag]['text']) ? html_entity_decode(implode(", ", $fields[$tag]['text']),ENT_QUOTES,'utf-8') : '';
                                }
                            break;
                        }
                    }

                   # Process title
                    $title != '' and $title = str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$title)) and cmsFramework::meta('title', $title);
                    $title != '' and $this->cms_version >= 1.6 and $params->set('page_title',$title);

                    # Process description
                    $description != '' and $description= str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$description)) and cmsFramework::meta('description', $description);
                    $description != ''and $this->cms_version >= 1.6 and $article->metadesc = htmlspecialchars($description,ENT_COMPAT,'utf-8');

                    # Process keywords
                    $keywords != '' and $keywords = mb_strtolower(str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$keywords)),'utf-8') and cmsFramework::meta('keywords', $keywords);
                    $keywords != '' and $this->cms_version >= 1.6 and $article->metakey = htmlspecialchars($keywords,ENT_COMPAT,'utf-8');
                }
            }
            elseif(
                isset($article->parameters)
                && $article->parameters->get('show_page_title')
                && $article->parameters->get('num_leading_articles') == '' /* run only if it's an article menu */
                && $article->parameters->get('filter_type') == '' /* run only if it's an article menu */
            ) {
                    $title = $article->parameters->get('page_title');
                    $title != '' and $params->set('page_title',$title);
            }

            if($crumbs && !empty($crumbs))
            {
    			array_pop($crumbs); // Remove extra title from breadcrumb because it's automatically appended by Joomla

                cmsFramework::setPathway($crumbs);
            }

                $this->facebookOpenGraph($listing, compact('title','keywords','description'));
        }

        function onContentPrepare( $context, &$article, &$params)
        {
            $this->setCmsVersion();

    		if($context == 'com_content.article') {
    			//Override Joomla article params
    			if(method_exists($params, 'set')) {
    				$params->set('show_item_navigation',0);
    	            $params->set('show_vote',0);
    			}
    			elseif(isset($this->params) && method_exists($this->params,'set')) {
    				$this->params->set('show_item_navigation',0);
    			            $this->params->set('show_vote',0);
    			}
    		}

            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

            // Check whether to perform the replacement or not
            $option = Sanitize::getString($_REQUEST, 'option', '');
            $view = Sanitize::getString($_REQUEST, 'view', '');
            $layout = Sanitize::getString($_REQUEST, 'layout', '');
            $id = Sanitize::getInt($_REQUEST,'id');
            if(
                $option == 'com_content'
                &&
                in_array($view,array('article','category','frontpage'))
                && ($layout != '' || in_array($view,array('article','frontpage')))
            )
            {
                $row = &$article;
                if(isset($row->id)
                    && $row->id > 0
                    && isset($row->catid)
                    && $row->catid > 0
                ) {

                    $cache_file = s2CacheKey('jreviews_config');

                    $Config = S2Cache::read($cache_file,'_s2framework_core_');

                    $debug = false;

                    $debug_php = Sanitize::getBool($Config,'debug_enable',false);

                    $debug_ipaddress = Sanitize::getString($Config,'debug_ipaddress');

                    if($debug_php &&
                        ($debug_ipaddress == '' || $debug_ipaddress == s2GetIpAddress())) {

                        $debug = true;
                    }

                    $Dispatcher = new S2Dispatcher(array('app'=>'jreviews','debug'=>$debug));

                    if ($option=='com_content' && $view == 'article' & $id > 0) {

                        $_GET['url'] = 'com_content/com_content_view';

                    } elseif ($option=='com_content' && ((($layout == 'blog' || $layout == 'blogfull') && $view == 'category') || $view == 'frontpage')) {

                        $_GET['url'] = 'com_content/com_content_blog';

                    }

                    $passedArgs = array(
                        'params'=>$params,
                        'row'=>$row,
                        'component'=>'com_content'
                        );

                    $passedArgs['cat'] = $row->catid;

                    $passedArgs['listing_id'] = $row->id;

                    $output = $Dispatcher->dispatch($passedArgs);

                    if($output)
                    {
                        $row = &$output['row'];
                        unset($params);
                        $params = &$output['params'];
                    }

                    /**
                    * Store a copy of the $listing and $crumbs arrays in memory for use in the onBeforeDisplayContent method
                    */
                    classRegistry::setObject(array('listing'=>&$output['listing'],'crumbs'=>&$output['crumbs']),'jreviewsplugin');

                    // Destroy pathway
                    if(!empty($output['crumbs']))
                    {
                        cmsFramework::setPathway(array());
                    }
                    unset($output,$passedArgs,$Dispatcher);
                }
            }
        }

        function extractTags($text)
        {
            $pattern = '/{([a-z0-9_|]*)}/i';

            $matches = array();

            $result = preg_match_all( $pattern, $text, $matches );

            if( $result == false ) {
                return array();
            }

            return array_unique(array_values($matches[1]));
        }

        function setCmsVersion()
        {
             if($this->cms_version == '') {
                $version = new JVersion();
                $this->cms_version = $version->RELEASE;
             }
        }

        function stringToArray($string, $separator = "\n")
        {
            $version = new JVersion();

            if($version->RELEASE >= 1.6 && !strstr($string,$separator)) return json_decode($string,true); /*J16*/

            $out = array();

            $array = explode($separator,$string);

            foreach($array as $key => $value) {
                if($value != '') {
                    $pos = strpos( $value, '=' );
                    $property = trim( substr( $value, 0, $pos ));
                    $pvalue = trim( substr( $value, $pos + 1 ) );
                    $out[$property] = $pvalue;
                }
            }

            return $out;
        }

        /**
        * Facebook Open Graph implementation
        *
        * @param mixed $listing
        * @param mixed $meta
        */
        function facebookOpenGraph(&$listing, $meta)
        {
            // http://developers.facebook.com/docs/opengraph/

            $option = Sanitize::getString($_REQUEST, 'option', '');

            $view = Sanitize::getString($_REQUEST, 'view', '');

            $id = Sanitize::getInt($_REQUEST,'id');

            // Make sure this is a Joomla article page
            if(!($option == 'com_content' && $view == 'article' && $id)) return;

            $Config = Configure::read('JreviewsSystem.Config');

            if(empty($Config)) {

                $cache_file = s2CacheKey('jreviews_config');

                $Config = S2Cache::read($cache_file,'_s2framework_core_');
            }

            $facebook_xfbml = Sanitize::getBool($Config,'facebook_opengraph');

            // Make sure FB is enabled and we have an FB App Id
            if(!$facebook_xfbml) return;

            extract($meta);

            $title == '' and $title = $listing['Listing']['title'];

            $description == '' and $description = Sanitize::htmlClean(Sanitize::stripAll($listing['Listing'],'summary'));

            $image = '';

            if(isset($listing['MainMedia'])) {

                $file_extension = Sanitize::getString($listing['MainMedia'],'file_extension');

                $image_url = Sanitize::getString($listing['MainMedia'],'media_path');

                if($image_url && $file_extension) $image =  $image_url. '.' . $file_extension;
            }

            if($image == '') {

                $img_src = '/<img[^>]+src[\\s=\'"]+([^"\'>\\s]+(jpg)+)/is';

                preg_match($img_src,$listing['Listing']['summary'],$matches);

                if(isset($matches[1])) $image = $matches[1];
            }

            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true,'ampreplace'=>true));

            $fields = $listing['Field']['pairs'];

            // You can add other Open Graph meta tags by adding the attribute, custom field pair to the array below
            $tags = array(
                'og:title'=>$title,
                'og:url'=>$url,
                'og:image'=>$image,
                'og:site_name'=>cmsFramework::getConfig('sitename'),
                'og:description'=>$description,
                'og:type'=>Sanitize::getString($listing['ListingType']['config'],'facebook_opengraph_type'),
                'og:latitude'=>Sanitize::getString($Config,'geomaps.latitude'),
                'og:longitude'=>Sanitize::getString($Config,'geomaps.longitude'),
                'og:street_address'=>Sanitize::getString($Config,'geomaps.address1'),
                'og:locality'=>Sanitize::getString($Config,'geomaps.city'), // city
                'og:region'=>Sanitize::getString($Config,'geomaps.state'), // state
                'og:postal_code'=>Sanitize::getString($Config,'geomaps.postal_code'),
                'og:country_name'=>Sanitize::getString($Config,'geomaps.country',Sanitize::getString($Config,'geomaps.default_country'))
    //			,'open-graph-tag'=>'jr_fieldname'
            );

            $app_id = Sanitize::getString($Config,'facebook_appid');

            $app_id != '' and cmsFramework::addScript('<meta property="fb:app_id" content="'.$app_id.'"/>');

    //        cmsFramework::addScript('<meta property="fb:admins" content="YOUR-ADMIN-ID"/>'); // It's app_id or this, not both

            # Loop through the tags array to add the additional FB meta tags
            foreach($tags AS $attr=>$fname)
            {
                $content = '';

                if(substr($fname,0,3) == 'jr_') {
                    // It's a custom field
                    $content = isset($fields[$fname]) ? htmlspecialchars($fields[$fname]['text'][0],ENT_QUOTES,'utf-8') : '';
                }
                elseif($fname != '') {
                    // It's a static text, not a custom field
                    $content = htmlspecialchars($fname);
                }

                $content != '' and cmsFramework::addScript('<meta property="'.$attr.'" content="'.$content.'"/>');
            }
        }
    }
}
