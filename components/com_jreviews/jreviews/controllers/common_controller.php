<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommonController extends MyController {

    var $uses = array('menu','captcha');

    var $helpers = array();

    var $components = array('access','config');

    var $autoRender = false; //Output is returned

    var $autoLayout = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    /**
    * Called in community plugins to initialize $Config object if Joomla cache is enabled
    * and JReviews cache has been cleared
    * Don't add anything to this method!
    *
    */
    function index() {}

    /**
    * Category auto-detect
    */
    public static function _discoverIDs(&$controller)
    {
		if($ids = Configure::read('_discoverIDs')) {
			return $ids;
		}

        // Initialize variables
        $id = Sanitize::getInt($controller->params,'id');
        $cat_id = Sanitize::getInt($controller->params,'cat');
        $option = Sanitize::getString($controller->params,'option');
        $view = Sanitize::getString($controller->params,'view');
        $task = Sanitize::getString($controller->params,'task');

        switch($option)
        {
            case 'com_jreviews':

                # Get url params for current controller/action
                $url = Sanitize::getString($controller->passedArgs,'url');

                $route['url']['url'] = $url;

                $route = S2Router::parse($route,true,'jreviews');

                isset($route['data']['action']) and $route['data']['action'] == 'search' and $route = $route['url'];

                $dir_id = Sanitize::getString($route,'dir');

                !$cat_id and $cat_id = Sanitize::getString($route,'cat');

                $criteria_id = Sanitize::getString($route,'criteria');

                if ($cat_id != '')
                {
                    $cat_id = CommonController::makeModParamsUsable($cat_id);
                }
                elseif($criteria_id != '')
                {
                    $criteria_id = CommonController::makeModParamsUsable($criteria_id);
                }
                elseif($dir_id != '')
                {
                    $dir_id = CommonController::makeModParamsUsable($dir_id);
                }
                else
                { //Discover the params from the menu_id

                    $menu_id = Sanitize::getString($controller->params,'Itemid');
                    $params = $controller->Menu->getMenuParams($menu_id);
                    $dir_id = cleanIntegerCommaList(Sanitize::getString($params,'dirid'));
                    $cat_id = cleanIntegerCommaList(Sanitize::getString($params,'catid'));
                }
                break;

            case 'com_content':

                    if ('article' == $view || 'view' == $task)
                    {
                        // If cat id was not available in url then we need to query it, otherwise it was already read above
                        if(!$cat_id)
                        {
                            $db = !isset($this) ? cmsFramework::getDB() : $this->_db;

                            $query = "
                                SELECT
                                    Listing.catid
                                FROM
                                    #__content AS Listing
                                RIGHT JOIN
                                    #__jreviews_categories AS Category ON Listing.catid = Category.id AND Category.option = 'com_content'
                                WHERE
                                    Listing.id = " . $id
                             ;
                             $db->setQuery($query);
                             $cat_id = $db->loadResult();
                        }
                    }
                    elseif ($view=="category")
                    {
                        $cat_id = $id;
                    }
                break;

            default:
                $cat_id = null; // Catid not detected because the page is neither content nor jreviews
                break;
        }

        $ids = array();
        isset($dir_id) and !empty($dir_id) and $ids['dir_id'] = $dir_id;
        isset($cat_id) and !empty($cat_id) and $ids['cat_id'] = $cat_id;
        isset($criteria_id) and !empty($criteria_id) and $ids['criteria_id'] = $criteria_id;

		Configure::write('_discoverIDs', $ids);

		return $ids;
    }

    /**
    * Used in modules
    *
    * @param mixed $param
    * @return string
    */
    public static function makeModParamsUsable($param)
    {
        if(empty($param)) return null;
        $urlSeparator = "_";
        return cleanIntegerCommaList(str_replace($urlSeparator,",",urldecode($param)));
    }

    /**
    * Returns sef urls passed as posted data via curl
    * Used to get front end sef urls from admin side
    *
    */
    function _sefUrl()
    {
        $sef_urls = array();

        $urls = Sanitize::getVar($this->data,'url');

        if(empty($urls)) return;

        foreach($urls AS $key=>$url)
        {
            $sef_urls[$key] = cmsFramework::route($url);
        }

        echo cmsFramework::jsonResponse($sef_urls);
    }

    /**
     * Adds the captcha image to forms
     * Called via ajax to save unnecessary processing and to avoid issues with cached pages
     */
    function _initForm()
    {
        $form_id = Sanitize::getString($this->data,'form_id');

        $show_captcha = Sanitize::getString($this->data,'captcha');

        $response = array();

        if (!$form_id) return;

        if($show_captcha)
        {
            $captcha = $this->Captcha->displayCode();
            $response['captcha'] = $captcha['image'];
        }

        if($this->Access->isGuest())
        {
            $user_session = UserAccountComponent::getUser();

            if(!empty($user_session))
            {
                S2App::import('Component','user_account','jreviews');

                $create_user_account = Sanitize::getBool($this->Config,'user_registration_guest');

                $response['create_account'] = $create_user_account;

                $response['name'] = $user_session['name'];

                $response['username'] = $user_session['username'];

                $response['email'] = $user_session['email'];
            }
        }

        $response['token'] = cmsFramework::getToken();

        return cmsFramework::jsonResponse($response);
    }
 }
