<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MyController extends S2Controller
{
    var $listview = null;

    var $_db; // This should be instantiated only in controllers where it is needed

    var $public_menu_id;

    var $langArray = array();

    var $themeDebug = false; // Outputs the theme path when rending each theme

    function beforeFilter()
    {
        # These should be called in each controller where they are required instead of globally
        $this->_db = cmsFramework::getDB();

        $this->_user = cmsFramework::getUser();

		# Overcome host restrictions
		$query = "SET SQL_BIG_SELECTS=1";

        $this->_db->setQuery($query);

        $this->_db->query();

        # Always load JreviewsLocale class because it is used server side as well
        if(!class_exists('JreviewsLocale')) {

            require(S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'locale.php' );
        }

		# Fix Joomla bug when language filter is active with default language code hidden in url
		if(isset($this->params['lang'])) {
			$this->params['lang'] = cmsFramework::getUrlLanguageCode();
		}

        # Init Access
		if(isset($this->Access))
        {
            $this->Access->init($this->Config);

            $this->set('Access',$this->Access);

            $this->Access->is_guest = $this->_user->id == 0 || $this->_user->block;
        }

        // Check Joomla registration setting and override account creation if disabled
        if($this->_user->id == 0 && isset($this->Config)) {

            $allow_registration = cmsFramework::allowUserRegistration();

            Configure::write('CMS.registration', $allow_registration);

            if(!$allow_registration) {

                $this->Config->user_registration_guest = 0;
            }
        }

        // Automatically convert m param to media_id
        if($media_id = Sanitize::getString($this->params,'m',null)) {
            $this->params['media_id'] = $this->data['media_id'] = s2alphaID($media_id,true,5,cmsFramework::getConfig('secret'));
        }

        S2App::import('Component','theming','jreviews');

        $this->Theming = ClassRegistry::getClass('ThemingComponent');

        $this->Theming->startup($this);

		# Set pagination vars
		// First check url, then menu parameter. Otherwise the limit list in pagination doesn't respond b/c menu params always wins
        $this->limit = Sanitize::getInt($this->params,'limit',Sanitize::getInt($this->data,'limit_special',Sanitize::getInt($this->data,'limit')));

		$this->page = Sanitize::getInt($this->data,'page',Sanitize::getInt($this->params,'page',1));

        if(!$this->limit)
        {
	 		if(Sanitize::getVar($this->params,'action')=='myreviews') {

				$this->limit = Sanitize::getInt($this->params,'limit',$this->Config->user_limit);

                $this->params['default_limit'] = $this->Config->user_limit;
			}
            else {

				$this->limit = Sanitize::getInt($this->params,'limit',$this->Config->list_limit);

                $this->params['default_limit'] = $this->Config->list_limit;
			}
		}

		if(Sanitize::getVar($this,'action')=='myreviews') {

			$this->params['default_limit'] = min($this->limit,$this->Config->user_limit);

		} else {

			$this->params['default_limit'] = min($this->limit,$this->Config->list_limit);
		}

        // Set a hard code limit to prevent abuse
        $this->limit = max(min($this->limit, 50),$this->params['default_limit']);

		$this->page = $this->page === 0 ? 1 : $this->page;

        $this->offset = (int)($this->page-1) * $this->limit;

		if($this->offset < 0) $this->offset = 0;

        # Required further below for Community Model init
        if(!isset($this->Menu))  {

            S2App::import('Model','menu','jreviews');

            $this->Menu = ClassRegistry::getClass('MenuModel');
        }

        $debug_ipaddress = Sanitize::getString($this->Config,'debug_ipaddress');

        if(Sanitize::getBool($this->Config,'debug_theme_enable') &&
            ($debug_ipaddress == '' || $debug_ipaddress == $this->ipaddress)) {

            $this->themeDebug = true;
        }

		if(!$this->ajaxRequest)
		{
			if(!$this->public_menu_id = Configure::read('_public_menu_id')) {

				# Find and set one public Itemid to use for Ajax requests
				$this->public_menu_id = '';

				$this->public_menu_id = $this->Menu->get('jreviews_public');

				$this->public_menu_id = $this->public_menu_id != '' ? $this->public_menu_id : 99999;

				Configure::write('_public_menu_id',$this->public_menu_id);
			}

			if(!$search_itemid = Configure::read('_search_itemid')) {

				// Set search menu Itemid used in several of the controllers
				$option = Sanitize::getString($this->params,'option');

				$auto_itemid = Sanitize::getBool($this->Config,'search_itemid',false);

				$hc_itemid = Sanitize::getInt($this->Config,'search_itemid_hc','');

				$search_menuid = $this->Menu->get('jr_advsearch');

				$search_itemid = '';

				switch($option) {

					case 'com_jreviews':

                    	// page Itemid is enabled
						if(!$auto_itemid && $hc_itemid > 0) {
							$search_itemid = $hc_itemid;
						}
						elseif(!$auto_itemid & $search_menuid >0) {
							$search_itemid = $search_menuid;
						}
					break;

                    default: // Non-JReviews pages - can't use current page Itemid

                    	if($hc_itemid >0) {
							$search_itemid = $hc_itemid;
						}
						else {
							$search_itemid = $search_menuid;
						}
					break;
				}

				$search_itemid == ''and $option == 'com_jreviews' and $search_itemid = Sanitize::getString($this->params,'Itemid');

				Configure::write('_search_itemid',$search_itemid);
			}

			$this->set(array(
				'search_itemid'=>$search_itemid,
				'public_menu_id'=>$this->public_menu_id
			));
		}

        $this->set('themeDebug',$this->themeDebug);

        # Dynamic Community integration loading
        $community_extension = Configure::read('Community.extension');

        if($community_extension != '') {

            S2App::import('Model',$community_extension,'jreviews');

            $this->Community = new CommunityModel();
        }

		# Init plugin system
        $this->_initPlugins();
    }

    function afterFilter()
    {
        $langArray = array();

        $this->headerScripts();

        if(!class_exists('AssetsHelper')) {
            S2App::import('Helper','assets','jreviews');
        }

        $Assets = ClassRegistry::getClass('AssetsHelper');

        // Need to override name and action because using $this->requestAction in theme files replaces the original values (i.e. related listings prevents detail page js/css from loading)
        $Assets->name = $this->name;

        $Assets->action = $this->action;

        $Assets->params = $this->params;

        $Assets->viewVars = & $this->viewVars;

        if(!isset($Assets->Access))
        {
            if(!isset($this->Access)) // View cache
            {
                S2App::import('Component','access','jreviews');

                $Access = new AccessComponent();

                if(!isset($this->Config)) {
					$this->Config = Configure::read('JreviewsSystem.Config');
				}

                $Access->init($this->Config);

                $Assets->Access = &$Access;
            }
            else {

                $Assets->Access = & $this->Access;
            }
        }

        if(!isset($Assets->Config)) {

            if(!isset($this->Config)) {

                $Assets->Config = Configure::read('JreviewsSystem.Config');
            }
            else {

            	$Assets->Config = & $this->Config;
            }
        }

        // Can't use this in ajax requests because it's output outside the json response and breaks it
        if(!$this->ajaxRequest)
        {
            if(!empty($this->assets)) {

                $Assets->assets = array_insert($Assets->assets,$this->assets);
            }

            # Generate js locale file
            $this->createLanguageFile();

            $Assets->load();
        }
    }

    function getFacebookSettings()
    {
        $fb = '{}';

        $post = false;

        $controllerActions = array(
            'com_content'=>array('com_content_view'),
            'everywhere'=>array('index'),
            'common'=>array('index'),
            'listings'=>array('create'),
            'media'=>array('create','photoGallery','videoGallery'),
            'community_listings'=>array('all'),
            'community_reviews'=>array('all'),
            'module_widgets'=>array('listing')
        );

        // Check if running in valid controller/actions
        if(!isset($controllerActions[$this->name])){
            return $fb;
        }

        $actions = !is_array($controllerActions[$this->name]) ? array($controllerActions[$this->name]) : $controllerActions[$this->name];

        if(in_array('all',$actions) || in_array($this->action,$actions)) {

            if(!isset($this->Access)) // View cache
            {
                S2App::import('Component','access','jreviews');

                $Access = new AccessComponent();

                if(!isset($this->Config)) {

                    $this->Config = Configure::read('JreviewsSystem.Config');
                }

                $Access->init($this->Config);
            }
            else {

                $Access = $this->Access;
            }

            $fb_appid = Sanitize::getString($this->Config,'facebook_appid');

            $fb_opengraph = Sanitize::getBool($this->Config,'facebook_opengraph',true);

            $fb_xfbml = $fb_appid && $fb_opengraph;

            $post_listings = $fb_appid &&
                                !$Access->moderateListing() &&
                                $this->Config->facebook_enable &&
                                $this->Config->facebook_listings;


            $post_reviews = $fb_appid &&
                                !$Access->moderateReview() &&
                                $this->Config->facebook_enable &&
                                $this->Config->facebook_reviews;

            if($this->name == 'listings') {

                $post = $post_listings;
            }
            elseif(in_array($this->name,array('common','com_content','community_listings','community_reviews','module_widgets'))) {

                $post = $post_reviews;
            }

            $fb = json_encode(array(
                'appid'=>$fb_appid,
                'og'=>$fb_opengraph,
                'xfbml'=>$fb_xfbml,
                'optout'=>(bool) $this->Config->facebook_optout,
                'post'=>$post
            ));
        }

        return $fb;
    }

    function headerScripts()
    {
        if(!defined('MVC_GLOBAL_JS_VARS') && !$this->ajaxRequest && $this->action != '_save') // action conditional is for new listing submission, otherwise the form hangs
        {
            // Used when view cache is enabled
            if(!isset($this->Config)) {

                $this->Config = Configure::read('JreviewsSystem.Config');
            }

            $compare_url = 'index.php?option=com_jreviews&Itemid=&url=categories/compare/id:listing_ids/';

            $compare_url_menu = 'index.php?option=com_jreviews&Itemid=%s&id=listing_ids';

            $menu_id = $this->Menu->getMenuIdByAction(103); // Comparison Catch-All menu

            if($menu_id) {

                $compare_url = sprintf($compare_url_menu,$menu_id);
            }

            $compareUrl = WWW_ROOT_REL . str_replace(WWW_ROOT,'',cmsFramework::route($compare_url));

            # Add global javascript variables
            $this->assets['head-top'][] = '<script type="text/javascript">
            /* <![CDATA[ */
            var s2AjaxUri = "'.getAjaxUri('jreviews',Sanitize::getBool($this->Config,'ajaxuri_lang_segment',true)).'",
                jreviews = jreviews || {},
                jrPublicMenu = '.(int)$this->public_menu_id.';
            jreviews.calendar_img = "'.$this->viewImages.'calendar.gif",
            jreviews.lang = jreviews.lang || {};
            jreviews.cms = 2;
            jreviews.locale = "'.cmsFramework::getLocale().'";
            jreviews.jparams = '.(Sanitize::getBool($this->Config,'url_param_joomla') ? 'true' : 'false').',
            jreviews.fb = '.$this->getFacebookSettings().';
            jreviews.comparison = {
                numberOfListingsPerPage: '.Sanitize::getInt($this->Config,'list_compare_columns',3).',
                maxNumberOfListings: 15,
                compareURL: "'.$compareUrl.'"
            };
            jreviews.mobi = '.(int)Configure::read('System.isMobile').';
            jreviews.iOS = '.(int)Configure::read('System.isiOS').';
            jreviews.isRTL = '.cmsFramework::isRTL().';
            /* ]]> */
            </script>';

            if($item_id = Sanitize::getInt($this->params,'Itemid'))
            {
                $page = Sanitize::getVar($this->viewVars,'page');

                if(!empty($page) && isset($page['menuParams'])) {

                    $menu = $page['menuParams'];
                }
                else {

                    $menu = $this->Menu->getMenuParams($item_id);
                }

                $meta_desc = Sanitize::getString($menu,'menu-meta_description');

                $meta_keys = Sanitize::getString($menu,'menu-meta_keywords');

                $meta_desc != '' and cmsFramework::meta('description',$meta_desc);

                $meta_keys != '' and cmsFramework::meta('keywords',$meta_keys);
            }

            define('MVC_GLOBAL_JS_VARS',1);
        }
    }

    function createLanguageFile()
    {
        $expiration = 86400; // 24 hours

        $filename = S2_CACHE . 'core' . DS . 'locale-'.cmsFramework::getLocale().'.js';

        if (!file_exists($filename) /*|| (time()-filemtime($filename) >= $expiration)*/) {

            if(!class_exists('JreviewsLocale')) {

                require(S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'locale.php' );
            }

            $lang = JreviewsLocale::getJS();

            $file = fopen($filename, "w+");

            if ($file) {

                fputs($file,'jreviews.lang = ' . json_encode($lang) . ';');

                fclose($file);
            }
        }
    }

    /**
    * Validates the request integrity token. The token location will vary for post/get requests
    *
    */
    function __validateToken($token)
    {
        return Sanitize::getString($this->params['form'],$token,Sanitize::getString($this->params,$token));
    }

/**********************************************************
*  Plugin callbacks
**********************************************************/
    /**
    * Plugin system initialization
    *
    * @param object $model - include for lazy loading of plugin callbacks for a particular model. This may be required when trying to trigger a callback in a model outside it's main controller
    */
    function _initPlugins($model = null)
    {
        // Load plugins
        $App = S2App::getInstance();

        $registry = &$App->jreviewsPaths;

        $plugins = array_keys($registry['Plugin']);

        if(!empty($plugins))
        {
            $plugins = str_replace('.php','',$plugins);

            // Re-arrange order of plugins. Need to do this like this until a plugin manager is
            // eventually added to JReviews

            if(in_array('paidlistings_cron_functions',$plugins)) {

                $index1 = array_search('paidlistings_cron_functions',$plugins);

                $index2 = array_search('paidlistings',$plugins);

                if($index1 !== false) {

                    unset($plugins[$index1]);

                    if($index2 !== false) unset($plugins[$index2]);

                    array_unshift($plugins,'paidlistings_cron_functions');

                    array_unshift($plugins,'paidlistings');
                }
            }

            S2App::import('Plugin',$plugins);

            $this->__initComponents($plugins);

            foreach($plugins AS $plugin)
            {
                $component_name = Inflector::camelize($plugin);

                if(isset($this->{$component_name}) && $this->{$component_name}->published)
                {
                    // Register all the plugin callbacks in the controller
                    $plugin_methods = get_class_methods($this->{$component_name});

                    foreach($plugin_methods AS $callback)
                    {
                        if(substr($callback,0,3)=='plg')
                        {
                            if(method_exists($this,'getPluginModel'))
                            {
                                if(is_null($model))
                                {
                                    $this->{$component_name}->plgModel = $this->getPluginModel();
                                }
                                else
                                {
                                    $this->{$component_name}->plgModel = & $this->{$model};
                                }

                                $plgModel = & $this->{$component_name}->plgModel;

                                if(!isset($this->{$component_name}->validObserverModels)
                                    ||
                                        (
                                            isset($this->{$component_name}->validObserverModels)
                                            && !empty($this->{$component_name}->validObserverModels)
                                            && in_array($plgModel->name,$this->{$component_name}->validObserverModels)
                                        )
                                    )
                                {
                                    $plgModel->addObserver($callback,$this->{$component_name});
                                }
                            }
                        }
                    }
                    if(method_exists($this->{$component_name},'plgBeforeRender'))
                    {
                        $this->plgBeforeRender[] = $component_name;
                    }
                }
            }
        }

        unset($App,$registry);
    }

    function createPageArray($menu_id = null) {

        $page = array('title'=>'','top_description'=>'','menuParams'=>array());

        if(!$menu_id) $menu_id = Sanitize::getInt($this->params,'Itemid');

        if(!$menu_id) return $page;

        $menuParams = $this->Menu->getMenuParams($menu_id);

        $menu_title = $this->Menu->getMenuName($menu_id);

        // Initialize values with legacy parameters for Joomla 1.5
        $page_title = $page_heading = Sanitize::getString($menuParams,'title');

        $show_page_heading = Sanitize::getBool($menuParams,'dirtitle');

        $page_heading = Sanitize::getString($menuParams,'page_heading');

        $page_title = isset($menuParams['title']) ? $page_title : Sanitize::getString($menuParams,'page_title');

        $show_page_heading = isset($menuParams['dirtitle']) ? $show_page_heading : $page_heading != '' && Sanitize::getBool($menuParams,'show_page_heading');

        $page_heading = $menuParams['page_heading'] = $show_page_heading ? Sanitize::getString($menuParams,'page_heading', $page_title) : '';

        $page['title'] = $page_heading;

        $page['title_seo'] = $page_title != '' ? $page_title : $menu_title;

        $page['show_title'] = $show_page_heading;

        if($page['show_title'] && $page['title'] == '' && $page['title_seo'] != '') {

            $page['title'] = $page['title_seo'];
        }

        $page['keywords'] = Sanitize::getString($menuParams,'menu-meta_keywords');

        $page['description'] = Sanitize::getString($menuParams,'menu-meta_description');

        $page['top_description'] = Sanitize::getString($menuParams,'custom_description');

        $page['top_description'] = str_replace('\n','',$page['top_description']);

        $page['show_description'] = $page['top_description'] != '';

        $page['menuParams'] = $menuParams;

        $robots = Sanitize::getString($menuParams,'robots');

        if($robots != '') $page['robots'] = $robots;

        return $page;
    }
}
