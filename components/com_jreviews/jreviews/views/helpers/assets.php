<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/**
* All required css/js assets are conviniently defined here per controller and controller action (per page)
*/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AssetsHelper extends MyHelper
{
    var $helpers = array('html','libraries','custom_fields','editor');

    var $assetParams = array();

    var $useJavascriptLoader = false;

    var $useMinifiedScripts = true;

    // Keep a record of js files already loaded to avoid duplicates when using Head JS Javascript Loader
    var $jsPaths = array();
    /**
    * These arrays can be set at the controller level
    * and in plugin callbacks with any extra css or js files that should be loaded
    *
    * @var mixed
    */
    var $assets = array('js'=>array(),'css'=>array(),'absurl'=>array());

    function load()
    {
        $assetParams = func_get_args();

        $this->assetParams = array_merge($this->assetParams,$assetParams);

        $methodAction = Inflector::camelize($this->name.'_'.$this->action);

        $methodName = Inflector::camelize($this->name);

		if(method_exists($this,$methodAction)) {

            $this->{$methodAction}();

        } elseif(method_exists($this,$methodName)) {

            $this->{$methodName}();
        }
        elseif(!empty($this->assets)) {

            $this->send($this->assets);
        }
    }

    function send($assets,$inline=false)
    {
        $this->Html->app = $this->app;

        unset($this->viewVars);

        $this->useJavascriptLoader = Sanitize::getInt($this->Config,'libraries_scripts_loader',0);

        $this->useMinifiedScripts = Sanitize::getInt($this->Config,'libraries_scripts_minified',1);

        if(!isset($assets['js'])) {

            $assets['js'] = array();
        }

        if(!isset($assets['css'])) {

            $assets['css'] = array();
        }

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-top'])) {

            foreach($this->assets['head-top'] AS $head) {

                cmsFramework::addScript($head);
            }
        }

        // Incorporate controller set assets before sending
        if(!empty($this->assets['js'])) {

            $assets['js'] = array_merge($assets['js'],$this->assets['js']);
        }

        if(!empty($this->assets['css'])) {

            $assets['css'] = array_merge($assets['css'],$this->assets['css']);
        }

        $assets['js'] = array_unique($assets['js']);

        $version = explode('.',$this->Config->version);

        $build = 'v='.array_sum($version);

        /***********************************************************
         *                      LOAD CSS                           *
        /***********************************************************/

        if(!Sanitize::getBool($this->Config,'libraries_jqueryui')) {

            array_unshift($assets['css'],'jq.ui'); // Load Jquery UI unless disabled in the configuration
        }

        cmsFramework::isRTL() and $assets['css'][] = 'rtl';

        $assets['css'][] = 'custom_styles'; // Aloways load custom_styles

        $assets['css'] = array_unique($assets['css']);

        $this->Html->css(arrayFilter($assets['css'], $this->Libraries->css()),array('inline'=>$inline,'params'=>$build));

        /***********************************************************
         *                      LOAD JS                            *
        /***********************************************************/

        // Load locale language object
        $locale_js = WWW_ROOT_REL . 'components/com_s2framework/tmp/cache/core/locale-'.cmsFramework::getLocale().'.js';

        // Load icons for IE7
        $jrIconsUrl = WWW_ROOT_REL . 'components/com_jreviews/jreviews/views/js/jreviews.icons.ie7.js';
        cmsFramework::addScript('<!--[if lte IE 7]><script type="text/javascript" src="'.$jrIconsUrl.'"></script><![endif]-->');

        // Load comparison storage script for IE7.
        $jrStorageUrl = WWW_ROOT_REL . 'components/com_jreviews/jreviews/views/js/storage.ie7.min.js';
        cmsFramework::addScript('<!--[if lte IE 7]><script type="text/javascript" src="'.$jrStorageUrl.'"></script><![endif]-->');

        array_unshift($assets['js'],'jreviews'); // Always load jreviews.js

        // Load jQuery UI unless it's disabled. Also load if it's forced via presense in the $assets array
        if(!Sanitize::getBool($this->Config,'libraries_jqueryui') || in_array('jq.ui',$assets['js'])) {

            array_unshift($assets['js'],'jquery/i18n/jquery.ui.datepicker-' . cmsFramework::locale());

            // Remove from current position.
            $index = array_search('jq.ui',$assets['js']);

            if($index) unset($assets['js'][$index]);

            array_unshift($assets['js'],'jq.ui'); // Load Jquery UI unless disabled in the configuration
        }

        // Load jQuery unless it's disabled. Also load if it's forced via presense in the $assets array
        if(!Sanitize::getBool($this->Config,'libraries_jquery') || in_array('jquery',$assets['js']))
        {
            // Remove from current position
            $index = array_search('jquery',$assets['js']);

            if($index) unset($assets['js'][$index]);

            array_unshift($assets['js'],'jquery');
        }

        $jsPaths = array();

        $jsPathsDependent = array();

        if(in_array('jquery',$assets['js'])) {

            $jsDependencies = array(
                'jq.treeview'=>'jquery',
                'jq.scrollable'=>'jquery',
                'geomaps'=>'jreviews',
                'compare'=>'jreviews',
                'fields'=>'jreviews'
            );
        }
        else {

            $jsDependencies = array(
                'geomaps'=>'jreviews',
                'compare'=>'jreviews',
                'fields'=>'jreviews'
            );
        }

        $jsFiles = arrayFilter($assets['js'], $this->Libraries->js());

        $absUrls = Sanitize::getVar($this->assets,'absurl',array());

        if($this->useJavascriptLoader) {

            foreach($jsFiles AS $key=>$jsfile) {

                if(!isset($this->jsPaths[$jsfile])) {

                    $relative = in_array($jsfile,$absUrls) ? false : true;

                    $js_path = $this->locateScript($jsfile,array('admin'=>false,'relative'=>$relative,'minified'=>$this->useMinifiedScripts));

                    $this->jsPaths[$jsfile] = 1;

                    if($js_path) {

                        if(!isset($jsDependencies[$key])) {

                            $jsPaths[$key] = stripslashes(json_encode(array($key=>$js_path)));
                        }
                        else {

                            $jsPathsDependent[$jsDependencies[$key]][] = stripslashes(json_encode(array($key=>$js_path)));
                        }
                    }
                }
            }

            if(!isset($this->jsPaths['locale'])) {

                array_unshift($jsPaths, stripslashes(json_encode(array('locale'=>$locale_js))));

                $this->jsPaths['locale'] = 1;
            }

            # Head JS - overwrite the js array so only head.js is loaded
            $jsFiles = array('head.load.min'); // Load script for async loading
        }
        else {

            $assets['js'] = array_unique($assets['js']);

            cmsFramework::addScript('<script type="text/javascript" src="'.$locale_js.'"></script>');

        }

        $this->Html->js($jsFiles,array('inline'=>$inline,'params'=>$build,'minified'=>$this->useMinifiedScripts,'absUrls'=>$absUrls));

        // Send scripts to head using Javascript Loader
        if(!empty($jsPaths)) {

            $jsPaths = implode(",",$jsPaths);

            cmsFramework::addScript("<script type='text/javascript'>head.js($jsPaths);</script>");

        }

        if(!empty($jsPathsDependent)) {

            foreach($jsPathsDependent AS $parent=>$paths) {

                $paths = implode(",",$paths);

                cmsFramework::addScript('<script type="text/javascript">head.ready("'.$parent.'",function() {head.js('.$paths.');});</script>');

            }
        }

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-bottom'])) {

            $head_bottom = '';

            foreach($this->assets['head-bottom'] AS $head) {

                $head_bottom .= $head;
            }
            cmsFramework::addScript($head_bottom);
        }
    }

/**********************************************************************************
 *  Categories Controller
 **********************************************************************************/
     function Categories()
     {
        $assets = array(
            'js'=>array('jq.scrollable','compare'),
            'css'=>array('theme','theme.list')
        );

        if(Sanitize::getString($this,'listview') == 'masonry') {

            $assets['js'][] = 'jquery/jquery.masonry.min';
        }

        $this->send($assets);
     }

/**********************************************************************************
 *  ComContent Controller
 **********************************************************************************/
    function ComContentComContentView()
    {
        $assets = array(
            'js'=>array('jq.fancybox','jq.scrollable','compare'),
            'css'=>array('theme','theme.detail','theme.form','jq.fancybox','modules'/* for related listings */,'custom_styles_modules')
        );

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.rating';
            }

            $assets['js'][] = 'fields';
        }

        $listing = Sanitize::getVar($this->viewVars,'listing');

        if(!empty($listing) && $listing['Listing']['media_count'] > 0) {

    		if($listing['Listing']['photo_count'] > 0 && in_array($this->Config->media_detail_photo_layout, array('gallery_large','gallery_small')))
    		{
                $assets['js']['media'] = 'media';

    			$assets['js'][] = 'jq.galleria';

    			$assets['js'][] = 'jq.galleria.classic';

    			$assets['css'][] = 'jq.galleria';
    		}

    		if($listing['Listing']['video_count'] > 0 && $this->Config->media_detail_video_layout == 'video_player')
    		{
                $assets['js']['media'] = 'media';

    			$assets['js'][] = 'jq.video';

    			$assets['css'][] = 'jq.video';
    		}

            if($listing['Listing']['audio_count'])
            {
                $assets['js']['media'] = 'media';

                $assets['js'][] = 'jq.audio';

                $assets['js'][] = 'jq.audio.playlist';
            }

            if($listing['Listing']['attachment_count']) {

                $assets['js']['media'] = 'media';
            }
        }

        $this->send($assets);
    }

    function ComContentComContentBlog()
    {
        $assets = array(
            'css'=>array('theme','theme.list')
        );

        $this->send($assets);
    }

/**********************************************************************************
 *  Community Listings Plugin   Controller
 **********************************************************************************/
     function CommunityListings()
     {
        $assets = array();

        $assets['css'] = array('theme','modules','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        $page_count = ceil($total/$limit);

        $page_count > 1 and $assets['js'] = array('jq.scrollable');

        $compare = Sanitize::getInt($this->viewVars,'compare');

        $assets['js'] = array('jq.scrollable');

        $assets['css'] = array('theme','theme.list','modules','custom_styles_modules');

        if ($compare) {

            $assets['js'][] = array('compare');

        }

        $this->send($assets);
     }

/**********************************************************************************
 *  Community Reviews Plugin   Controller
 **********************************************************************************/
     function CommunityReviews()
     {
        $assets = array();

        $assets['css'] = array('theme','modules','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        if($limit) {

            $page_count = ceil($total/$limit);

            $page_count > 1 and $assets['js'] = array('jq.scrollable');
        }

        $this->send($assets);
     }

/**********************************************************************************
 *  Directories Controller
 **********************************************************************************/
     function DirectoriesDirectory()
     {
         $assets = array(
            'css'=>array('theme')
         );

        $this->send($assets);
     }

/**********************************************************************************
 *  Discussions Controller
 **********************************************************************************/
     function Discussions()
     {
        $assets = array(
            'js'=>array('jq.fancybox'),
            'css'=>array('theme','theme.detail','theme.form','jq.fancybox')
        );

        if($this->action == 'review') {

            $listing = $this->viewVars['listing'];

            if(Sanitize::getInt($listing['Listing'],'media_count_user') > 0) {

                if($listing['Listing']['audio_count_user'] + $listing['Listing']['attachment_count_user'] > 0) {

                    $assets['js']['media'] = 'media';
                }

                if($listing['Listing']['audio_count_user']) {

                    $assets['js'][] = 'jq.audio';

                    $assets['js'][] = 'jq.audio.playlist';
                }

            }
        }

        $this->send($assets);
     }

/**********************************************************************************
 *  Everywhere Controller
 **********************************************************************************/
    function EverywhereIndex()
    {
        // need to load jQuery for review edit/report and voting
        $assets = array(
            'js'=>array('jq.fancybox'),
            'css'=>array('theme','theme.detail','theme.form','modules','jq.fancybox')
        );

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            $assets['js'][] = 'fields';
            $assets['js'][] = 'jq.fancybox';

            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.rating';
            }
        }

        $this->send($assets);
    }

    function EverywhereCategory()
    {
        $assets = array();

        if(Sanitize::getString($this->params,'option')!='com_comprofiler'){
            $assets = array('css'=>array('theme'));
        }

        $this->send($assets);
    }

/**********************************************************************************
 *  Media Controller
 **********************************************************************************/
	function MediaUploadCreate()
	{
        $assets = array(
            'js'=>array('media','jq.uploader'),
            'css'=>array('theme','theme.form')
        );
        $this->send($assets);
	}

	function MediaPhotoGallery() {

        $assets = array(
            'js'=>array('media','jq.galleria','jq.galleria.classic'),
            'css'=>array('theme','theme.form','jq.galleria')
        );

        $this->send($assets);
	}

    function MediaVideoGallery() {

        $lightbox = Sanitize::getInt($this->params,'lightbox');

        $assets = array(
            'js'=>array('media','jq.video','jq.scrollable'),
            'css'=>array('theme','theme.form','jq.video')
        );

        if($lightbox)
        {
            array_unshift($assets['js'], 'jq.ui');

            array_unshift($assets['js'], 'jquery');
        }

        $this->send($assets);
    }

	function MediaListing()
	{
        $assets = array(
            'js'=>array('media','jq.galleria','jq.galleria.classic','jq.video','jq.audio','jq.audio.playlist'),
            'css'=>array('theme','theme.detail','form','jq.galleria','jq.video')
        );

        $assets['js'][] = 'jquery/jquery.masonry.min';

        $this->send($assets);
	}

	function MediaMyMedia()
	{
		$this->MediaListing();
	}

	function MediaMediaList()
	{
        $media_types = array('photo','video','attachment','audio');

        $canEdit = false;

        foreach($media_types AS $media_type) {

            if($this->Access->canEditMedia($media_type)) {

                $canEdit = true;

                break;
            }
        }

        if($canEdit) {

            $assets = array(
                'js'=>array('media','jq.galleria','jq.galleria.classic','jq.video','jq.audio','jq.audio.playlist'),
                'css'=>array('theme','theme.detail','form','jq.galleria','jq.video')
            );
        }
        else {

            $assets = array(
                'js'=>array('media'),
                'css'=>array('theme','form')
            );

        }

        $assets['js'][] = 'jquery/jquery.masonry.min';

        $this->send($assets);
	}

	function MediaAttachments()
	{
        $assets = array(
            'css'=>array('theme','form')
        );
        $this->send($assets);
	}


/**********************************************************************************
 *  Listings Controller
 **********************************************************************************/
    function ListingsCreate()
    {
        $assets = array(
            'js'=>array('fields','jq.rating','media','jq.uploader'),
            'css'=>array('theme','theme.form')
        );

        $this->send($assets);

        # Transforms class="jr-wysiwyg-editor" textareas
        if($this->Access->loadWysiwygEditor()) {
            $this->Editor->load();
        }
    }

    function ListingsEdit()
    {
        $this->ListingsCreate();
    }

    function ListingsDetail()
    {
        $assets = array(
            'js'=>array('fields','jq.rating','jq.fancybox','compare'),
            'css'=>array('theme','theme.detail','theme.form','jq.fancybox')
		);

        $this->send($assets);
    }

/**********************************************************************************
 *  Module Advanced Search Controller
 **********************************************************************************/
    function ModuleAdvancedSearch()
    {
        $assets = array(
             'js'=>array('fields'),
             'css'=>array('theme','theme.form','custom_styles_modules')
        );

        $this->send($assets);
    }

/**********************************************************************************
 *  Module Directories Controller
 **********************************************************************************/
    function ModuleDirectories()
    {
        $assets = array('js'=>array('jq.treeview'),'css'=>array('theme','jq.treeview','custom_styles_modules'));

        $this->send($assets);
    }

/**********************************************************************************
 *  Module Favorite Users Controller
 **********************************************************************************/
    function ModuleFavoriteUsers()
    {
        $assets = array();

        $assets['css'] = array('theme','modules','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        $page_count = ceil($total/$limit);

        $page_count > 1 and $assets['js'] = array('jq.scrollable');

        $this->send($assets);
    }

/**********************************************************************************
 *  Module Fields Controller
 **********************************************************************************/
    function ModuleFields()
    {
        $assets= array();
        $assets['css'] = array('theme','modules','custom_styles_modules');
        $this->send($assets);
    }

/**********************************************************************************
 *  Module Range Controller
 **********************************************************************************/
    function ModuleRange()
    {
        $assets= array();
        $assets['css'] = array('theme','modules','custom_styles_modules');
        $this->send($assets);
    }

/**********************************************************************************
 *  Module Listings Controller
 **********************************************************************************/
    function ModuleListings()
    {
        $assets = array();

        $assets['css'] = array('theme','modules','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        $page_count = $limit > 0 ? ceil($total/$limit) : 0;

        $page_count > 1 and $assets['js'] = array('jq.scrollable');

        $compare = Sanitize::getInt($this->viewVars,'compare');

        if ($compare) {

            $assets['js'] = array('jq.scrollable','compare');
            $assets['css'] = array('theme','theme.list','modules','custom_styles_modules');

        }

        $this->send($assets);

    }

/**********************************************************************************
 *  Module Reviews Controller
 **********************************************************************************/
    function ModuleReviews()
    {
        $assets = array();

        $assets['css'] = array('theme','modules','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        $page_count = ceil($total/$limit);

        $page_count > 1 and $assets['js'] = array('jq.scrollable');

        $this->send($assets);
    }

/**********************************************************************************
 *  Module Media Controller
 **********************************************************************************/
    function ModuleMedia()
    {
        $assets = array(
            'css'=>array('theme','modules','custom_styles_modules')
        );

        $total = Sanitize::getInt($this->viewVars,'total');

        $limit = Sanitize::getInt($this->viewVars,'limit');

        $page_count = ceil($total/$limit);

        $page_count > 1 and $assets['js'] = array('jq.scrollable');

        $this->send($assets);
    }


/**********************************************************************************
 *  Reviews Controller
 **********************************************************************************/
    function ReviewsCreate()
    {
        //
    }

    function ReviewsLatest()
    {
        $assets = array(
            'css'=>array('theme','theme.detail','theme.form')
        );

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.rating';
            }

            $assets['js'][] = 'fields';
        }

        $this->send($assets);
    }

    function ReviewsMyReviews()
    {

        $assets = array(
            'css'=>array('theme','theme.detail','theme.form')
        );

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.rating';
            }

            $assets['js'][] = 'fields';
        }

        $this->send($assets);
    }

    function ReviewsRankings()
    {
        $assets = array(
            'css'=>array('theme')
        );
        $this->send($assets);
    }

/**********************************************************************************
 *  Search Controller
 **********************************************************************************/
    function SearchAdvanced()
    {
        $assets = array(
            'js'=>array('fields'),
            'css'=>array('theme','theme.form')
        );

        $this->send($assets);
    }
}
