<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( '_JEXEC') or die( 'Direct Access to this location is not allowed.' );

define('MVC_FRAMEWORK', 1);

/*********************************************************************
 * CONFIGURATION
 *********************************************************************/
if(isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
	ini_set('display_errors','On');
	error_reporting(E_ALL);
}

if(function_exists('mb_regex_encoding')) mb_regex_encoding('UTF-8');

/*********************************************************************
 * DEFINE CMS CONSTANTS
 *********************************************************************/
if(!defined('CMS_JOOMLA17')) define('CMS_JOOMLA17','CMS_JOOMLA17');
if(!defined('CMS_JOOMLA16')) define('CMS_JOOMLA16','CMS_JOOMLA16');
if(!defined('CMS_JOOMLA15')) define('CMS_JOOMLA15','CMS_JOOMLA15');

if (!defined('DS')) 			define('DS', DIRECTORY_SEPARATOR);
if (!defined('_DS')) 			define('_DS','/');
if (!defined('_PARAM_CHAR')) 	define('_PARAM_CHAR',':');

/**
 * Returns CMS version and loads cms compat library
**/
require_once (S2_ROOT . DS . 's2framework' . DS . 'libs' . DS . 'cms_compat' . DS . 'joomla.php');

if(!function_exists('getCmsVersion'))
{
	function getCmsVersion()
    {
        if(class_exists("JVersion"))
        {
            $version = new JVersion();
            switch($version->RELEASE)
            {
                case 1.5:
                    return CMS_JOOMLA15;
                break;
                case 1.6:
                case 1.7:
                case 2.5:
				default:
                    return CMS_JOOMLA16;
                break;
            }
        }
	}
}

if(!function_exists('lcfirst')) {

	function lcfirst($str) {

		$str{0} = strtolower($str{0});

		return $str;
	}
}

define( 'PATH_ROOT', JPATH_SITE . DS);

$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ?
			'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
			:
			$_SERVER['SERVER_NAME']);

$folder = dirname($_SERVER['SCRIPT_NAME']);

if(defined('MVC_FRAMEWORK_ADMIN')) {
	$folder = str_replace('/administrator','',$folder);
}

if($folder == DS) $folder = _DS; // Fixes issue on IIS

$domain .= $folder != '/' ? $folder . '/' : $folder;

define('WWW_ROOT',$domain);

define('WWW_ROOT_REL', $folder != '/' ? $folder . '/' : $folder);

if(!defined('_PLUGIN_DIR_NAME')) define('_PLUGIN_DIR_NAME','plugins');

/*********************************************************************
 * START FILE INCLUSIONS
 *********************************************************************/
# Load paths
require( dirname(__FILE__)) . DS . 'config' . DS . 'paths.php';

# Load object class. Must be 1st to load
require( S2_LIBS . 'object.php' );

# Load libraries
require( S2_LIBS . 'class_registry.php' );
require( S2_LIBS . 'folder.php' );
require( S2_LIBS . 'cache.php' );
//require( S2_LIBS . 'overloadable.php' );
require( S2_LIBS . 'configure.php' );
require( S2_LIBS . 'sanitize.php' );
require( S2_LIBS . 'string.php' );
require( S2_LIBS . 'inflector.php' );
require( S2_LIBS . 'session.php' );
require( S2_LIBS . 'router.php' );
require( S2_LIBS . 'controller' . DS . 'controller.php' );
require( S2_LIBS . 'controller' . DS . 'component.php' );
require( S2_LIBS . 'view' . DS . 'helper.php' );
require( S2_LIBS . 'view' . DS . 'view.php' );
require( S2_LIBS . 'model' . DS . 'model.php' );

require( S2_FRAMEWORK . DS . 'dispatcher.php' );

/*********************************************************************
 * DEFINE GLOBAL CONSTANTS
 *********************************************************************/
!defined('PHP5') and define ('PHP5', (phpversion() >= 5));

$now = gmdate('Y-m-d H:i',time());
$today = gmdate('Y-m-d',time());

!defined('_CURRENT_SERVER_TIME') and DEFINE('_CURRENT_SERVER_TIME', $now);

!defined('CURRENT_SERVER_TIME') and DEFINE('CURRENT_SERVER_TIME', $now);

!defined('_TODAY') and DEFINE('_TODAY', $today);

!defined('_END_OF_TODAY') and DEFINE('_END_OF_TODAY', $today . ' 23:59:59');

!defined('NULL_DATE') and DEFINE('NULL_DATE', '0000-00-00 00:00:00');

!defined('_NULL_DATE') and DEFINE('_NULL_DATE', '0000-00-00');

!defined('_CURRENT_SERVER_TIME_FORMAT') and DEFINE( '_CURRENT_SERVER_TIME_FORMAT', '%Y-%m-%d %H:%M:%S' );

/*********************************************************************
 *	GLOBAL FUNCTIONS
 *********************************************************************/

class S2Paths {

	var $__paths = array();

	static function getInstance() {

		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] = new S2Paths();
		}
		return $instance[0];
	}

	static function get($app, $key,$default = null) {

		$_this = S2Paths::getInstance();

		if(isset($_this->__paths[$app][$key])) {
			return $_this->__paths[$app][$key];
		}

		return $default;
	}

	static function set($app,$key,$value) {
		$_this = S2Paths::getInstance();
		$_this->__paths[$app][$key] = $value;
	}

}

/**
 * Returns a translated string if one is found, or the submitted message if not found.
 *
 * @param string $singular Text to translate
 * @param boolean $return Set to true to return translated string, or false to echo
 * @return mixed translated string if $return is false string will be echoed
 */

function __t($singular, $return = false, $js = false) {
	if (!$singular) {
		return;
	}

	if (!class_exists('I18n')) {
        S2App::import('Core', 'i18n');
	}

    $text = I18n::translate($singular);

    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }

    if ($return === false) {
		echo $text;
	} else {
		return $text;
	}
}

/**
 * Returns correct plural form of message identified by $singular and $plural for count $count.
 * Some languages have more than one form for plural messages dependent on the count.
 *
 * @param string $singular Singular text to translate
 * @param string $plural Plural text
 * @param integer $count Count
 * @param boolean $return true to return, false to echo
 * @return mixed plural form of translated string if $return is false string will be echoed
 */
    function __n($singular, $plural, $count, $return = false) {
        if (!$singular) {
            return;
        }
        if (!class_exists('I18n')) {
            S2App::import('Core', 'i18n');
        }

        if ($return === false) {
            echo I18n::translate($singular, $plural, null, 5, $count);
        } else {
            return I18n::translate($singular, $plural, null, 5, $count);
        }
    }

/**
 * For locale strings - date, number format
 * Returns a translated string if one is found, or the submitted message if not found.
 */
function __l($singular, $return = false, $js = false) {

	$domain = 'locale';
	if (!$singular) {
		return;
	}
	if (!class_exists('I18n')) {
		require(S2_LIBS . 'I18n.php');
	}

   $text =I18n::translate($singular, null, $domain);

    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }

    if ($return === false) {
        echo $text;
    } else {
        return $text;
    }
}

/**
 * For use in administration
 * Returns a translated string if one is found, or the submitted message if not found.
 */
function __a($singular, $return = false, $js = false) {

	$domain = 'admin';
	if (!$singular) {
		return;
	}
	if (!class_exists('I18n')) {
		require(S2_LIBS . 'I18n.php');
	}

   $text = I18n::translate($singular, null, $domain);

    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }

    if ($return === false) {
        echo $text;
    } else {
        return $text;
    }
}

/**
 * Reads/writes temporary data to cache files or session.
 *
 * @param  string $path	File path within /tmp to save the file.
 * @param  mixed  $data	The data to save to the temporary file.
 * @param  mixed  $expires A valid strtotime string when the data expires.
 * @param  string $target  The target of the cached data; either 'cache' or 'public'.
 * @return mixed  The contents of the temporary file.
 * @deprecated Please use Cache::write() instead
 */
	function cache($path, $data = null, $expires = '+1 day', $target = 'cache') {

		if (Configure::read('Cache.disable')) {
			return null;
		}

		if (!Configure::read('Cache.view')) {
			return null;
		}

		$now = time();

		if (!is_numeric($expires)) {
			$expires = strtotime($expires, $now);
		}

		switch(low($target)) {
			case 'cache':
				$filename = CACHE . $path;
			break;
			case 'public':
				$filename = WWW_ROOT . $path;
			break;
			case 'tmp':
				$filename = TMP . $path;
			break;
		}
		$timediff = $expires - $now;
		$filetime = false;

		if (file_exists($filename)) {
			$filetime = @filemtime($filename);
		}

		if ($data === null) {
			if (file_exists($filename) && $filetime !== false) {
				if ($filetime + $timediff < $now) {
					@unlink($filename);
				} else {
					$data = @file_get_contents($filename);
				}
			}

		} elseif (is_writable(dirname($filename))) {

			@file_put_contents($filename, $data);
		}
		return $data;
	}

/**
 * Used to delete files in the cache directories, or clear contents of cache directories
 *
 * @param mixed $params As String name to be searched for deletion, if name is a directory all files in directory will be deleted.
 *              If array, names to be searched for deletion.
 *              If clearCache() without params, all files in app/tmp/cache/views will be deleted
 *
 * @param string $type Directory in tmp/cache defaults to view directory
 * @param string $ext The file extension you are deleting
 * @return true if files found and deleted false otherwise
 */
	function clearCache($params = null, $type = 'views', $ext = '.php')
    {
		if (is_string($params) || $params === null)
        {
			$params = preg_replace('/\/\//', '/', $params);
			$cache = S2_CACHE . $type . DS . $params;

			if (is_file($cache . $ext) && substr(basename($cache . $ext),0,5) != 'index')
            {
				@unlink($cache . $ext);
				return true;
			}
            elseif (is_dir($cache))
            {
				$files = glob("$cache*");

				if ($files === false) {
					return false;
				}

				foreach ($files as $file)
                {
					if (is_file($file) && substr(basename($file),0,5) != 'index') {
						@unlink($file);
					}
				}

				return true;

            } else {
				$cache = array(
					S2_CACHE . $type . DS . '*' . $params . $ext,
					S2_CACHE . $type . DS . '*' . $params . '_*' . $ext
				);

				$files = array();
				while ($search = array_shift($cache))
                {
					$results = glob($search);
					if ($results !== false) {
						$files = array_merge($files, $results);
					}
				}

				if (empty($files)) {
					return false;
				}

                foreach ($files as $file) {
                    if (is_file($file) && substr(basename($file),0,5) != 'index') {
						@unlink($file);
					}
				}

				return true;
			}
		}
        elseif (is_array($params))
        {
			foreach ($params as $key => $file) {
				clearCache($file, $type, $ext);
			}
			return true;
		}
		return false;
	}

/**
 * Returns microtime for execution time checking
 *
 * @return float Microtime
 */
function S2getMicrotime() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/**
* Generate a cache key that is different for different domains/paths
**/
function S2CacheKey($base, $var = '') {

	$var = is_array($var) ? serialize($var) : $var;

    $cache_key = strtolower(str_replace('-','_',cmsFramework::locale())) . '_' . $base . '_' . md5(WWW_ROOT . PATH_ROOT . cmsFramework::getConfig('secret') . $var);

    return $cache_key;
}

function S2cacheRead($prefix,$key=array(),$config = 'default') {

    if((!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0)
        && Configure::read('Cache.enable') && Configure::read('Cache.query'))
    {
    	$key = is_array($key) ? serialize($key) : $key;

        $cacheKey = S2CacheKey($prefix,$key);

        $cache = S2Cache::read($cacheKey,$config);

        if(false !== $cache) {

            return $cache;
        }
    }

    return false;
}

function S2cacheWrite($prefix,$key,$data,$config = 'default')
{
    # Send to cache
    if((!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0)
        && Configure::read('Cache.enable') && Configure::read('Cache.query'))
    {
        $cacheKey = S2CacheKey($prefix,serialize($key));
        S2Cache::write($cacheKey,$data,$config);
    }
}

function s2DebugTrace() {

    $trace = array_reverse(debug_backtrace());

    array_pop($trace);

    foreach($trace AS $t) {

        prx(basename($t['file']).'/line:'.$t['line'].'/function '.$t['function']);
    }
}

/**
 * Gets an environment variable from available sources.
 * Used as a backup if $_SERVER/$_ENV are disabled.
 *
 * @param  string $key Environment variable name.
 * @return string Environment variable setting.
 */
function env($key) {

	if ($key == 'HTTPS') {
		if (isset($_SERVER) && !empty($_SERVER)) {
			return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		} else {
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}
	}

	if (isset($_SERVER[$key])) {
		return $_SERVER[$key];
	} elseif (isset($_ENV[$key])) {
		return $_ENV[$key];
	} elseif (getenv($key) !== false) {
		return getenv($key);
	}

	if ($key == 'SCRIPT_FILENAME' && defined('SERVER_IIS') && SERVER_IIS === true){
		return str_replace('\\\\', '\\', env('PATH_TRANSLATED') );
	}

	if ($key == 'DOCUMENT_ROOT') {
		$offset = 0;
		if (!strpos(env('SCRIPT_NAME'), '.php')) {
			$offset = 4;
		}
		return substr(env('SCRIPT_FILENAME'), 0, strlen(env('SCRIPT_FILENAME')) - (strlen(env('SCRIPT_NAME')) + $offset));
	}
	if ($key == 'PHP_SELF') {
		return r(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
	}
	return null;
}

function ex($string) {
	echo $string;
}

function prx($array) {
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}

function arrayFilter($keys,$array)
{
	$result = array();

	if(!empty($keys))
    {
        foreach($keys AS $key) {
        	if(is_string($key)) {
	            if(isset($array[$key])) {
		                $result[$key] = $array[$key];
		            } else {
		                $result[$key] = $key;
		            }
	        	}
	        }
    }
	return $result;
}

/**
 * Replacement function for array_merge_recursive.
 * If a key already exists it is replaced with the $ins key instead of creating an array
 */
function array_insert($arr,$ins) {
	// Loop through all Elements in $ins:
	if (is_array($arr) && is_array($ins))
	{
		foreach ($ins as $k => $v)
		{
			// Key exists in $arr and both Elemente are Arrays: Merge recursively.
			if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) {

				$arr[$k] = array_insert($arr[$k],$v);

			} else {

				$arr[$k] = $v;
			}
		}
	}

	// Return merged Arrays:
	return $arr;
}

function s2ampReplace( $text )
{
	$text = str_replace( '&&', '*--*', $text );
	$text = str_replace( '&#', '*-*', $text );
	$text = str_replace( '&amp;', '&', $text );
	$text = preg_replace( '|&(?![\w]+;)|', '&amp;', $text );
	$text = str_replace( '*-*', '&#', $text );
	$text = str_replace( '*--*', '&&', $text );

	return $text;
}

function br2nl($str) {
	$str = preg_replace("/(\r\n|\n|\r)/", "", $str); return preg_replace("=<br */?>=i", "\n", $str);
}

function spChars(&$value, $key) {
	if ($key[0] != '_') {
	       $value = stripslashes(htmlspecialchars($value));
	}
}

/**
 * Recursively strips slashes from all values in an array
 *
 * @param array $value Array of values to strip slashes
 * @return mixed What is returned from calling stripslashes
 */
if(!function_exists('s2_stripslashes_deep')) {
	function s2_stripslashes_deep($value) {
		if (is_array($value)) {
			$return = array_map('s2_stripslashes_deep', $value);
			return $return;
		} elseif(is_string($value)) {
			$return = stripslashes($value);
			return $return ;
		} else {
			return $value;
		}
	}
}

function s2GetIpAddress()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    // In some weird cases the ip address returned is repeated twice separated by comma
    if(strstr($ip,','))
    {
    	$ip = explode(',',$ip);

        $ip = array_shift($ip);
    }

	if($ip == '::1') $ip = '127.0.0.1';

    return $ip;
}

function cleanIntegerCommaList($var) {

	$list = !is_array($var) ? explode(',',$var) : $var;

	foreach($list AS $key=>$val) {
		if(!is_numeric($val) || $val == '') {
			unset($list[$key]);
		}
	}

	return implode(',',$list);
}

/**
 * Converts string to array and removes empty elements
 */
function stringToArray($string, $separator = "\n")
{
    if(!strstr($string,$separator)) {

        $result = json_decode($string,true);

        if(is_array($result)) return $result;
    }

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
 * Converts string to array and removes empty elements
 *
 * REMOVE THIS FUNCTION AND USE THE ONE ABOVE
 */
function cleanString2Array($string, $separator = "\n")
{
	$array = explode($separator,$string);
	foreach($array as $key => $value) {
	  if($value == "") {
	    unset($array[$key]);
	  }
	}

	return $array;
}


function s2isIE() {

	if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
        return true;
    else
        return false;
}

/**
 * Returns the request uri for ajax requests for each application
 *
 * @param string $app
 * @return ajax request uri
 */
function getAjaxUri($app='jreviews', $use_lang_segment = true)
{
	$JApp = JFactory::getApplication();

	$lang_filter = class_exists('plgSystemLanguageFilter') && method_exists($JApp, 'getLanguageFilter') && $JApp->getLanguageFilter();

	$lang = cmsFramework::getUrlLanguageCode();

	$language = Sanitize::getString($_REQUEST,'language');

	$core_sef = cmsFramework::getConfig('sef') && !function_exists('sefEncode') && !class_exists('shRouter');

	$ie = s2isIE();

	$lang_segment = $use_lang_segment && $lang_filter && $core_sef && $language != '' && $lang != '';

    if(defined('MVC_FRAMEWORK_ADMIN'))
    {
         $ajaxUri = WWW_ROOT . 'administrator' . _DS . 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&format=raw&tmpl=component';
    }
    else
    {
    	$ajaxUri = /* WWW_ROOT */ WWW_ROOT_REL . ($lang_segment ? $lang . _DS : '') . 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&format=raw&tmpl=component' . (/* for Joomfish */ $lang != '' ? '&lang='.$lang : '');
    }


    if(defined('MVC_FRAMEWORK_ADMIN')) return str_replace('&amp;','&',$ajaxUri);

    return $ajaxUri;
}

function displayAjaxUri($app='jreviews') {
    echo getAjaxUri($app);
}

/**
 * Searches include path for files
 *
 * @param array $file File to look for
 * @param array $paths Paths to look in
 * @param bool $key If set to true it will return the path array key instead of the path
 * @return Full path to file if exists, otherwise false
 */
function fileExistsInPath($file, $paths) {

	if(!isset($file['ext'])){
		$file['ext']='';
	}
	if(!isset($file['suffix'])){
		$file['suffix'] = '';
	}

	foreach ($paths as $value=>$path) {
		$fullPaths = array();
		$file['ext'] = $file['ext'] != '' ? '.'.ltrim($file['ext'],'.') : '';
		if($file['suffix']!='') {
			$fullPaths[] = rtrim($path,DS) . DS . $file['name'].$file['suffix'].$file['ext'];
		}
		$fullPaths[] = rtrim($path,DS) . DS . $file['name'].$file['ext'];

		foreach($fullPaths AS $fullPath){
			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}
	}

	return false;
}

/**
 * Convert path to url
 *
 * @param string $path
 * @return string
 */
function pathToUrl($path, $relative = false)
{
    $basePath = PATH_ROOT;

	$baseUrl = WWW_ROOT;

    // To eliminate bug where the assets urls have path info from current browser url
    // Could be a conflict with other extensions that use WWW_ROOT as well
    if($pos = strpos($baseUrl,'index.php'))
    {
        $baseUrl = substr($baseUrl,0,$pos);
    }

    if(strstr($path,$basePath))
    {
        $removeBase = substr($path,strlen($basePath));
    } else {
        $removeBase = $path;
    }

    // If relative url, get the installation folder
    if($relative) {

		$baseUrl = WWW_ROOT_REL;
    }

	return $baseUrl . ltrim(str_replace(DS,_DS,$removeBase), _DS);
}

function vendor($name) {
	require_once( S2_VENDORS . $name);
}

if(!function_exists('urlencodeParam')) {
	function urlencodeParam($url_param,$urlencode=true)
	{
		if(is_string($url_param)) {

			$param = explode(_PARAM_CHAR,$url_param);

			$param[0] = urlencode(urldecode(stripslashes($param[0])));

			if(isset($param[1])) {

				$param[1] = stripslashes($param[1]);

				if($urlencode) {
					$param[1] = urlencode(urldecode(str_replace('//','',$param[1])));
				} else {
					$param[1] = str_replace('//','',$param[1]);
				}
			}

			return implode(_PARAM_CHAR,$param);

		} else {
			return $url_param;
		}
	}
}

function arrayToParams($array) {
	$params = array();
	if(is_array($array)) {
		foreach($array AS $key=>$value) {
			if(trim($value)!='')
			$params[] = $key.':'.str_replace(',','_',$value);
		}
		return implode('/',$params);
	} else {
		return '';
	}

}

/**
 *
 * @param type $message
 * @param type $file
 * @param type $duress
 */
function appLogMessage($message, $file, $duress = false) {

	if(Configure::read('System.debug') || $duress)
	{
		if(is_array($message)) {

			$text = implode("\r\n",$message);
		}
		else {

			$text = $message;
		}

		$text .= "\r\n";

		$text = date("F j, Y, g:i a") . '----------------------------------' . "\r\n" . $text;

		$filename = S2_LOGS . $file . '.txt';
		$fd = fopen($filename,"a");
		fwrite($fd, $text);
		fclose ($fd);
	}

}

function s2_num_format($number)
{
    return number_format($number,2,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true));
}

/**
 * Case insensitive deep in_array replacement
 */
function deep_in_array($value, $array, $case_insensitive = false)
{
    foreach($array as $item)
    {
        if(is_array($item))
            $ret = deep_in_array($value, $item, $case_insensitive);
        else
            $ret = ($case_insensitive) ? strtolower($item)==strtolower($value) : $item==$value;
        if($ret)
            return $ret;
    }
    return false;
}

/**
 * Convenience method for strtolower().
 *
 * @param string $str String to lowercase
 * @return string Lowercased string
 */
function low($str) {
	return mb_strtolower($str,'utf-8');
}

class s2Messages
{
    public static function invalidToken()
    {
        return __t("There was a problem submitting the form (Invalid Token).",true);
    }

    public static function accessDenied()
    {
        return __t("You don't have enough access to perform this action.",true);
    }

    public static function submitErrorDb()
    {
        return __t("There as a problem submitting the form (Database error).",true);
    }

    public static function submitErrorGeneric()
    {
        return __t("There was a problem processing the request.",true);
    }

}

/**
 * Translates a number to a short alhanumeric version
 *
 * Translated any number up to 9007199254740992
 * to a shorter version in letters e.g.:
 * 9007199254740989 --> PpQXn7COf
 *
 * specifiying the second argument true, it will
 * translate back e.g.:
 * PpQXn7COf --> 9007199254740989
 *
 * this function is based on any2dec && dec2any by
 * fragmer[at]mail[dot]ru
 * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
 *
 * If you want the alphaID to be at least 3 letter long, use the
 * $pad_up = 3 argument
 *
 * In most cases this is better than totally random ID generators
 * because this can easily avoid duplicate ID's.
 * For example if you correlate the alpha ID to an auto incrementing ID
 * in your database, you're done.
 *
 * The reverse is done because it makes it slightly more cryptic,
 * but it also makes it easier to spread lots of IDs in different
 * directories on your filesystem. Example:
 * $part1 = substr($alpha_id,0,1);
 * $part2 = substr($alpha_id,1,1);
 * $part3 = substr($alpha_id,2,strlen($alpha_id));
 * $destindir = "/".$part1."/".$part2."/".$part3;
 * // by reversing, directories are more evenly spread out. The
 * // first 26 directories already occupy 26 main levels
 *
 * more info on limitation:
 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
 *
 * if you really need this for bigger numbers you probably have to look
 * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
 * or: http://theserverpages.com/php/manual/en/ref.gmp.php
 * but I haven't really dugg into this. If you have more info on those
 * matters feel free to leave a comment.
 *
 * @author	Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author	Simon Franz
 * @author	Deadfish
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link	  http://kevin.vanzonneveld.net/
 *
 * @param mixed   $in	  String or long input to translate
 * @param boolean $to_num  Reverses translation when true
 * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
 * @param string  $passKey Supplying a password makes it harder to calculate the original ID
 *
 * @return mixed string or long
 */
function s2alphaID($in, $to_num = false, $pad_up = false, $passKey = null)
{
	$index = "bcdfghjklmnpqrstvwxyz0123456789BCDFGHJKLMNPQRSTVWXYZ";
	if ($passKey !== null) {
		// Although this function's purpose is to just make the
		// ID short - and not so much secure,
		// with this patch by Simon Franz (http://blog.snaky.org/)
		// you can optionally supply a password to make it harder
		// to calculate the corresponding numeric ID

		for ($n = 0; $n<strlen($index); $n++) {
			$i[] = substr( $index,$n ,1);
		}

		$passhash = hash('sha256',$passKey);
		$passhash = (strlen($passhash) < strlen($index))
			? hash('sha512',$passKey)
			: $passhash;

		for ($n=0; $n < strlen($index); $n++) {
			$p[] =  substr($passhash, $n ,1);
		}

		array_multisort($p,  SORT_DESC, $i);
		$index = implode($i);
	}

	$base  = strlen($index);

	if ($to_num) {
		// Digital number  <<--  alphabet letter code
		$in  = strrev($in);
		$out = 0;
		$len = strlen($in) - 1;
		for ($t = 0; $t <= $len; $t++) {
			$bcpow = bcpow($base, $len - $t);
			$out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
		}

		if (is_numeric($pad_up)) {
			$pad_up--;
			if ($pad_up > 0) {
				$out -= pow($base, $pad_up);
			}
		}
		$out = sprintf('%F', $out);
		$out = substr($out, 0, strpos($out, '.'));
	} else {
		// Digital number  -->>  alphabet letter code
		if (is_numeric($pad_up)) {
			$pad_up--;
			if ($pad_up > 0) {
				$in += pow($base, $pad_up);
			}
		}

		$out = "";
		for ($t = floor(log($in, $base)); $t >= 0; $t--) {
			$bcp = bcpow($base, $t);
			$a   = floor($in / $bcp) % $base;
			$out = $out . substr($index, $a, 1);
			$in  = $in - ($a * $bcp);
		}
		$out = strrev($out); // reverse
	}

	return $out;
}