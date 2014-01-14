<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined('MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/*********************************************************************
 * DEFINE PATHS	
 *********************************************************************/
define('MVC_ADMIN', 'admin');

// Define app paths for backwards compatibility with earlier jReviews versions
define('S2_APP_DIR','jreviews');
define('S2_CMSCOMP','com_jreviews');
define('S2_APP', S2_ROOT . DS . S2_APP_DIR . DS);
define('S2_CMS_ADMIN', PATH_ROOT . 'administrator' . DS . 'components' . DS . S2_CMSCOMP . DS );
define('S2_APP_URL', WWW_ROOT_REL . 'components'. _DS . S2_CMSCOMP . _DS . S2_APP_DIR . _DS);
define('S2_TMP', S2_ROOT . DS . 'tmp' . DS);
define('S2_LOGS', S2_TMP . 'logs' . DS);
define('S2_CACHE', S2_TMP . 'cache' . DS);
define('S2_CONFIG', S2_APP . 'config' . DS);
define('S2_MODELS', S2_APP . 'models' . DS);
define('S2_CONTROLLERS', S2_APP . 'controllers' . DS );
define('S2_COMPONENTS', S2_CONTROLLERS . 'components' . DS);
define('S2_VIEWS', S2_APP . 'views' . DS);
define('S2_HELPERS', S2_VIEWS . 'helpers' . DS);
define('S2_THEMES', S2_VIEWS . 'themes' . DS);
define('S2_ADMIN_CONTROLLERS', S2_APP. 'admin_controllers' . DS);
define('S2_ADMIN_COMPONENTS', S2_ADMIN_CONTROLLERS . DS . 'components' . DS);
define('S2_ADMIN_VIEWS', S2_APP . 'views' . DS . 'admin');
define('S2_ADMIN_HELPERS', S2_ADMIN_VIEWS . DS . 'helpers' . DS);
define('S2_VIEWS_URL', S2_APP_URL . 'views' . _DS);
define('S2_ADMIN_VIEWS_URL', S2_VIEWS_URL . 'admin' . _DS);
define('S2_THEMES_URL', S2_VIEWS_URL . 'themes' . _DS);
define('S2_IMAGES_URL', S2_VIEWS_URL . 'images' . _DS);
define('S2_CSS_URL', S2_VIEWS_URL . 'css' . _DS);
define('S2_JS_URL', S2_VIEWS_URL . 'js' . _DS);
define('S2_CSS_ADMIN_URL', S2_ADMIN_VIEWS_URL . _DS . 'css' . _DS);
define('S2_JS_ADMIN_URL', S2_ADMIN_VIEWS_URL . 'js' . _DS);

// Define framework paths common to all applications
define('S2_FRAMEWORK', S2_ROOT . DS . 's2framework');
define('S2_URL', WWW_ROOT . 'components'. _DS . 'com_s2framework' . _DS);
define('S2_LIBS', S2_FRAMEWORK . DS . 'libs' . DS);
define('S2_VENDORS', S2_ROOT . DS . 'vendors' . DS);
define('S2_CMS_CACHE', PATH_ROOT . 'cache' . DS);
define('S2_VENDORS_URL', S2_URL . 'vendors' . _DS);
define('S2_CMS_CACHE_URL', WWW_ROOT . 'cache' . _DS);

// Define CMS paths
define('CMS_LISTING_IMAGES', PATH_ROOT . 'images'. DS .'stories'. DS .'jreviews'. DS);
define('CMS_LISTING_IMAGES_URL', WWW_ROOT . 'images'. _DS .'stories'. _DS .'jreviews'. _DS);
define('CMS_LISTING_THUMBNAILS', PATH_ROOT . 'images'. DS .'stories'. DS .'jreviews'. DS .'tn'. DS);
define('CMS_LISTING_THUMBNAILS_URL', WWW_ROOT . 'images'. _DS .'stories'. _DS .'jreviews'. _DS .'tn'. _DS);

define('LIBS', S2_LIBS);
define('VENDORS',S2_VENDORS);

# Cake compatibility definitions
define('APP_DIR', S2_APP_DIR);
define('WEBROOT_DIR', WWW_ROOT);
define('APP_PATH', S2_APP);
define('CACHE',S2_CACHE);
define('MODELS',S2_MODELS);
define('BEHAVIORS',S2_MODELS . 'behaviors');
define('CONTROLLERS',S2_CONTROLLERS);
define('COMPONENTS',S2_COMPONENTS);
define('VIEWS',S2_VIEWS);
define('HELPERS',S2_HELPERS);
define('APP',S2_APP);
define('TMP',S2_TMP);