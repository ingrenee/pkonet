<?php
/**
 * Includes framework and
 * defines all application specific paths
 */

$s2_app = 'jreviews';

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

if(!defined('S2_ROOT')) {
	define('S2_ROOT', realpath(dirname($_SERVER["SCRIPT_FILENAME"])) . DS . 'components' . DS . 'com_s2framework');
}

if (!file_exists(S2_ROOT . DS . 's2framework' . DS . 'basics.php')) {
	?>
	<div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
	The S2 Framework required to run jReviews is not installed. Please install the com_s2framework component included in the jReviews package.
	</div>
	<?php
	exit;
}

if(!defined('MVC_FRAMEWORK')) require( S2_ROOT . DS . 's2framework' . DS . 'basics.php' );

S2Paths::set($s2_app, 'S2_APP_DIR',$s2_app);
S2Paths::set($s2_app, 'S2_CMSCOMP','com_'.$s2_app);
S2Paths::set($s2_app, 'S2_APP', PATH_ROOT . 'components' . DS . 'com_'.$s2_app . DS. $s2_app . DS);
S2Paths::set($s2_app, 'S2_ADDONS', PATH_ROOT . 'components' . DS . 'com_'.$s2_app . '_addons');
S2Paths::set($s2_app, 'S2_APP_URL', WWW_ROOT_REL . 'components'. _DS . 'com_'.$s2_app . _DS . $s2_app . _DS);
S2Paths::set($s2_app, 'S2_TMP', S2_ROOT . DS . 'tmp' . DS);
S2Paths::set($s2_app, 'S2_LOGS', S2Paths::get($s2_app,'S2_TMP') . 'logs' . DS);
S2Paths::set($s2_app, 'S2_CACHE', S2Paths::get($s2_app,'S2_TMP') . 'cache' . DS);
S2Paths::set($s2_app, 'S2_APP_CONFIG', S2Paths::get($s2_app,'S2_APP') . 'config' . DS);
S2Paths::set($s2_app, 'S2_APP_LOCALE', S2Paths::get($s2_app,'S2_APP') . 'locale' . DS);

S2Paths::set($s2_app, 'S2_MODELS', S2Paths::get($s2_app,'S2_APP') . 'models' . DS);
S2Paths::set($s2_app, 'S2_CONTROLLERS', S2Paths::get($s2_app,'S2_APP') . 'controllers' . DS);
S2Paths::set($s2_app, 'S2_COMPONENTS', S2Paths::get($s2_app,'S2_CONTROLLERS') . 'components' . DS);
S2Paths::set($s2_app, 'S2_VIEWS', S2Paths::get($s2_app,'S2_APP') . 'views' . DS);
S2Paths::set($s2_app, 'S2_HELPERS', S2Paths::get($s2_app,'S2_VIEWS') . 'helpers' . DS);
S2Paths::set($s2_app, 'S2_THEMES', S2Paths::get($s2_app,'S2_VIEWS') . 'themes' . DS);
S2Paths::set($s2_app, 'S2_JS', S2Paths::get($s2_app,'S2_VIEWS') . 'js' . DS);

S2Paths::set($s2_app, 'S2_VIEWS_URL', S2Paths::get($s2_app,'S2_APP_URL') . 'views' . _DS);
S2Paths::set($s2_app, 'S2_THEMES_URL', S2Paths::get($s2_app,'S2_VIEWS_URL') . 'themes' . _DS);
S2Paths::set($s2_app, 'S2_IMAGES_URL', S2Paths::get($s2_app,'S2_VIEWS_URL') . 'images' . _DS);
S2Paths::set($s2_app, 'S2_CSS_URL', S2Paths::get($s2_app,'S2_VIEWS_URL') . 'css' . _DS);

S2Paths::set($s2_app, 'S2_CMS_ADMIN', PATH_ROOT . 'administrator' . DS . 'components' . DS . S2Paths::get($s2_app,'S2_CMSCOMP') . DS );
S2Paths::set($s2_app, 'S2_ADMIN_CONTROLLERS', S2Paths::get($s2_app,'S2_APP') . 'admin_controllers' . DS);
S2Paths::set($s2_app, 'S2_ADMIN_COMPONENTS', S2Paths::get($s2_app,'S2_ADMIN_CONTROLLERS') . 'components' . DS);
S2Paths::set($s2_app, 'S2_ADMIN_VIEWS', S2Paths::get($s2_app,'S2_APP') . 'views' . DS . 'admin' . DS);
S2Paths::set($s2_app, 'S2_ADMIN_HELPERS', S2Paths::get($s2_app,'S2_ADMIN_VIEWS') . 'helpers' . DS);

S2Paths::set($s2_app, 'S2_ADMIN_VIEWS_URL', S2Paths::get($s2_app,'S2_VIEWS_URL') . 'admin' . _DS);
S2Paths::set($s2_app, 'S2_ADMIN_THEMES_URL', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_URL') . 'themes' . _DS);
S2Paths::set($s2_app, 'S2_CSS_ADMIN_URL', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_URL') . 'css' . _DS);
S2Paths::set($s2_app, 'S2_JS_ADMIN_URL', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_URL') . 'js' . _DS);

// Whatever path you enter here, JReviews will add an additional folder called jreviews so it becomes your/path/jreviews/
// Thumbnails will be stored in your/path/jreviews/tn/ so make sure you create that folder as well

if(!defined('_JR_WWW_IMAGES')) define('_JR_WWW_IMAGES','images' . _DS);

if(!defined('_JR_PATH_IMAGES')) define('_JR_PATH_IMAGES','images' . DS);

/**
 * Definition for Override paths
 */
S2Paths::set($s2_app, 'S2_APP_OVERRIDES', PATH_ROOT . 'templates' . DS . 'jreviews_overrides' . DS);
S2Paths::set($s2_app, 'S2_APP_URL_OVERRIDES', WWW_ROOT . 'templates' . _DS . 'jreviews_overrides' . _DS);
S2Paths::set($s2_app, 'S2_VIEWS_OVERRIDES', S2Paths::get($s2_app,'S2_APP_OVERRIDES') . 'views' . DS);
S2Paths::set($s2_app, 'S2_HELPERS_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_OVERRIDES') . 'helpers' . DS);
S2Paths::set($s2_app, 'S2_THEMES_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_OVERRIDES') . 'themes' . DS);
S2Paths::set($s2_app, 'S2_JS_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_OVERRIDES') . 'js' . DS);

S2Paths::set($s2_app, 'S2_VIEWS_URL_OVERRIDES', S2Paths::get($s2_app,'S2_APP_URL_OVERRIDES') . 'views' . _DS);
S2Paths::set($s2_app, 'S2_THEMES_URL_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_URL_OVERRIDES') . 'themes' . _DS);

S2Paths::set($s2_app, 'S2_IMAGES_URL_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_URL_OVERRIDES') . 'images' . _DS);
S2Paths::set($s2_app, 'S2_CSS_URL_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_URL_OVERRIDES') . 'css' . _DS);
S2Paths::set($s2_app, 'S2_JS_URL_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_URL_OVERRIDES') . 'js' . _DS);

S2Paths::set($s2_app, 'S2_ADMIN_VIEWS_OVERRIDES', S2Paths::get($s2_app,'S2_APP_OVERRIDES') . 'views' . DS . 'admin' . DS);
S2Paths::set($s2_app, 'S2_ADMIN_HELPERS_OVERRIDES', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_OVERRIDES') . 'helpers' . DS);
S2Paths::set($s2_app, 'S2_ADMIN_VIEWS_URL_OVERRIDES', S2Paths::get($s2_app,'S2_VIEWS_URL_OVERRIDES') . 'admin' . _DS);
S2Paths::set($s2_app, 'S2_CSS_ADMIN_URL_OVERRIDES', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_URL_OVERRIDES') . 'css' . _DS);
S2Paths::set($s2_app, 'S2_JS_ADMIN_URL_OVERRIDES', S2Paths::get($s2_app,'S2_ADMIN_VIEWS_URL_OVERRIDES') . 'js' . _DS);

$db = cmsFramework::getDB();

$debug_overrides_disable = 0;

if(defined('MVC_FRAMEWORK_ADMIN'))
{
    $tables = $db->getTableList();

    $dbprefix = cmsFramework::getConfig('dbprefix');

    if(in_array($dbprefix.'jreviews_config',$tables))
    {
        # Set default theme
        $db->setQuery("SELECT value FROM #__jreviews_config WHERE id = 'fallback_theme'");

        $fallback_theme = $db->loadResult();

        S2Paths::set($s2_app, 'S2_FALLBACK_THEME', $fallback_theme != '' ? $fallback_theme : 'default');

        # Disable overrides
        $db->setQuery("SELECT value FROM #__jreviews_config WHERE id = 'debug_overrides_disable'");

        $debug_overrides_disable = $db->loadResult();
    }
}
else {

    # Disable overrides
    $db->setQuery("SELECT value FROM #__jreviews_config WHERE id = 'debug_overrides_disable'");

    $debug_overrides_disable = $db->loadResult();
}

# Generage the file registry
unset($db);

$Configure = Configure::getInstance($s2_app,$debug_overrides_disable);

$s2App = S2App::getInstance($s2_app);

require_once( dirname(__FILE__) . DS . 'config' . DS . 'core.php' );

# Set app variable in I18n class
$import = S2App::import('Lib','I18n');
if(!$import)
{
    $clear = clearCache('','core');
    if(!$clear){
        echo 'You need to delete the file registry in /components/com_s2framework/tmp/cache/core/';
        exit;
    }
    $page = $_SERVER['PHP_SELF'];
    header("Location: index.php?option=com_jreviews");
    exit;
}

$Translate = I18n::getInstance();
$Translate->app = $s2_app;

# Load app files ...
if(defined('MVC_FRAMEWORK_ADMIN')) {
	S2App::import( 'admin_controller', 'my', 'jreviews' );
} else {
	S2App::import( 'controller', 'my', 'jreviews' );
}

S2App::import( 'model', 'my_model', 'jreviews' );