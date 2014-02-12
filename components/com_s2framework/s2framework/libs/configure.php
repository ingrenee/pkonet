<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/* SVN FILE: $Id: configure.php 7945 2008-12-19 02:16:01Z gwoo $ */
/**
 * Short description for file.
 *
 * Long description for filec
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.0.0.2363
 * @version       $Revision: 7945 $
 * @modifiedby    $LastChangedBy: gwoo $
 * @lastmodified  $Date: 2008-12-18 18:16:01 -0800 (Thu, 18 Dec 2008) $
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Configuration class (singleton). Used for managing runtime configuration information.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/42/The-Configuration-Class
 */
class Configure extends S2Object {

    var $basePaths = array();

    var $__items = array();

/**
 * List of additional path(s) where model files reside.
 *
 * @var array
 * @access public
 */
    var $modelPaths = array();
/**
 * List of additional path(s) where behavior files reside.
 *
 * @var array
 * @access public
 */
    var $behaviorPaths = array();
/**
 * List of additional path(s) where controller files reside.
 *
 * @var array
 * @access public
 */
    var $controllerPaths = array();
/**
 * List of additional path(s) where component files reside.
 *
 * @var array
 * @access public
 */
    var $componentPaths = array();
/**
 * List of additional path(s) where view files reside.
 *
 * @var array
 * @access public
 */
    var $viewPaths = array();
/**
 * List of additional path(s) where helper files reside.
 *
 * @var array
 * @access public
 */
    var $helperPaths = array();
/**
 * List of additional path(s) where plugins reside.
 *
 * @var array
 * @access public
 */
    var $pluginPaths = array();
/**
 * List of additional path(s) where vendor packages reside.
 *
 * @var array
 * @access public
 */
    var $vendorPaths = array();
/**
 * List of additional path(s) where locale files reside.
 *
 * @var array
 * @access public
 */
    var $localePaths = array();
/**
 * List of additional path(s) where console shell files reside.
 *
 * @var array
 * @access public
 */
    var $shellPaths = array();
/**
 * Current debug level.
 *
 * @link          http://book.cakephp.org/view/44/CakePHP-Core-Configuration-Variables
 * @var integer
 * @access public
 */
    var $debug = null;
/**
 * Determines if $__S2Objects cache should be written.
 *
 * @var boolean
 * @access private
 */
    var $__cache = false;
/**
 * Holds and key => value array of S2Objects' types.
 *
 * @var array
 * @access private
 */
    var $__S2Objects = array();
/**
 * Returns a singleton instance of the Configure class.
 *
 * @return Configure instance
 * @access public
 */
    static function getInstance($app = false, $disable_overrides = false) {

        static $instance = array();

        if (!$instance) {
            $instance[0] = new Configure();
            $instance[0]->__loadBootstrap($app, $disable_overrides);
        }

        return $instance[0];
    }
/**
 * Returns an index of S2Objects of the given type, with the physical path to each S2Object.
 *
 * @param string    $type Type of S2Object, i.e. 'model', 'controller', 'helper', or 'plugin'
 * @param mixed        $path Optional
 * @return Configure instance
 * @access public
 */
    function listS2Objects($type, $path = null, $cache = true) {
        $S2Objects = array();
        $extension = false;
        $name = $type;

        if ($type === 'file' && !$path) {
            return false;
        } elseif ($type === 'file') {
            $extension = true;
            $name = $type . str_replace(DS, '', $path);
        }
        $_this = Configure::getInstance();

        if (empty($_this->__S2Objects) && $cache === true) {
            $_this->__S2Objects = S2Cache::read('S2Object_map', '_s2framework_core_');
        }

        if (empty($_this->__S2Objects) || !isset($_this->__S2Objects[$type]) || $cache !== true) {
            $types = array(
                'model' => array('suffix' => '.php', 'base' => 'AppModel', 'core' => false),
                'behavior' => array('suffix' => '.php', 'base' => 'ModelBehavior'),
                'controller' => array('suffix' => '_controller.php', 'base' => 'AppController'),
                'component' => array('suffix' => '.php', 'base' => null),
                'view' => array('suffix' => '.php', 'base' => null),
                'helper' => array('suffix' => '.php', 'base' => 'AppHelper'),
                'plugin' => array('suffix' => '', 'base' => null),
                'vendor' => array('suffix' => '', 'base' => null),
                'class' => array('suffix' => '.php', 'base' => null),
                'file' => array('suffix' => '.php', 'base' => null)
            );

            if (!isset($types[$type])) {
                return false;
            }
            $S2Objects = array();

            if (empty($path)) {
                $path = $_this->{$type . 'Paths'};
                if (isset($types[$type]['core']) && $types[$type]['core'] === false) {
                    array_pop($path);
                }
            }
            $items = array();

            foreach ((array)$path as $dir) {
                if ($type === 'file' || $type === 'class' || strpos($dir, $type) !== false) {
                    $items = $_this->__list($dir, $types[$type]['suffix'], $extension);
                    $S2Objects = array_merge($items, array_diff($S2Objects, $items));
                }
            }

            if ($type !== 'file') {
                foreach ($S2Objects as $key => $value) {
                    $S2Objects[$key] = Inflector::camelize($value);
                }
            }
            if ($cache === true && !empty($S2Objects)) {
                $_this->__S2Objects[$name] = $S2Objects;
                $_this->__cache = true;
            } else {
                return $S2Objects;
            }
        }
        return $_this->__S2Objects[$name];
    }
/**
 * Returns an array of filenames of PHP files in the given directory.
 *
 * @param  string $path Path to scan for files
 * @param  string $suffix if false, return only directories. if string, match and return files
 * @return array  List of directories or files in directory
 */
    function __list($path, $suffix = false, $extension = false) {
        if (!class_exists('S2Folder')) {
            require LIBS . 'folder.php';
        }
        $items = array();
        $Folder = new S2Folder($path);
        $contents = $Folder->read(false, true);

        if (is_array($contents)) {
            if (!$suffix) {
                return $contents[0];
            } else {
                foreach ($contents[1] as $item) {
                    if (substr($item, - strlen($suffix)) === $suffix) {
                        if ($extension) {
                            $items[] = $item;
                        } else {
                            $items[] = substr($item, 0, strlen($item) - strlen($suffix));
                        }
                    }
                }
            }
        }
        return $items;
    }
/**
 * Used to store a dynamic variable in the Configure instance.
 *
 * Usage
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(array('One.key1' => 'value of the Configure::One[key1]'));
 * Configure::write('One', array(
 *     'key1' => 'value of the Configure::One[key1]',
 *     'key2' => 'value of the Configure::One[key2]'
 * );
 * Configure::write(array(
 *     'One.key1' => 'value of the Configure::One[key1]',
 *     'One.key2' => 'value of the Configure::One[key2]'
 * ));
 *
 * @link          http://book.cakephp.org/view/412/write
 * @param array $config Name of var to write
 * @param mixed $value Value to set for var
 * @return void
 * @access public
 */
    static function write($config, $value = null)
    {
        $_this = Configure::getInstance();

        if (!is_array($config)) {
            $config = array($config => $value);
        }

        foreach ($config as $names => $value)
        {
            $name = $_this->__configVarNames($names);
            switch (count($name)) {
                case 3:
                    $_this->{$name[0]}[$name[1]][$name[2]] = $value;
                break;
                case 2:
                    $_this->{$name[0]}[$name[1]] = $value;
                break;
                case 1:
                    $_this->{$name[0]} = $value;
                break;
            }
        }
    }
/**
 * Used to read information stored in the Configure instance.
 *
 * Usage
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 *
 * @link          http://book.cakephp.org/view/413/read
 * @param string $var Variable to obtain
 * @return string value of Configure::$var
 * @access public
 */
    static function read($var = 'debug', $default = null) {

        $_this = Configure::getInstance();

        if ($var === 'debug') {
            if (!isset($_this->debug)) {
                if (defined('DEBUG')) {
                    $_this->debug = DEBUG;
                } else {
                    $_this->debug = 0;
                }
            }
            return $_this->debug;
        }
        $name = $_this->__configVarNames($var);

        switch (count($name)) {
            case 3:
                if (isset($_this->{$name[0]}[$name[1]][$name[2]])) {
                    return $_this->{$name[0]}[$name[1]][$name[2]];
                }
            break;
            case 2:
                if (isset($_this->{$name[0]}[$name[1]])) {
                    return $_this->{$name[0]}[$name[1]];
                }
            break;
            case 1:
                if (isset($_this->{$name[0]})) {
                    return $_this->{$name[0]};
                }
            break;
        }
        return $default;
    }
/**
 * Used to delete a variable from the Configure instance.
 *
 * Usage:
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 *
 * @link          http://book.cakephp.org/view/414/delete
 * @param string $var the var to be deleted
 * @return void
 * @access public
 */
    function delete($var = null) {
        $_this = Configure::getInstance();
        $name = $_this->__configVarNames($var);

        if (count($name) > 1) {
            unset($_this->{$name[0]}[$name[1]]);
        } else {
            unset($_this->{$name[0]});
        }
    }
/**
 * Loads a file from app/config/configure_file.php.
 * Config file variables should be formated like:
 *  $config['name'] = 'value';
 * These will be used to create dynamic Configure vars.
 *
 * Usage Configure::load('configure_file');
 *
 * @link          http://book.cakephp.org/view/415/load
 * @param string $fileName name of file to load, extension must be .php and only the name
 *                         should be used, not the extenstion
 * @return mixed false if file not found, void if load successful
 * @access public
 */
    function load($fileName) {
        $found = false;

        if (file_exists(CONFIGS . $fileName . '.php')) {
            include(CONFIGS . $fileName . '.php');
            $found = true;
        } elseif (file_exists(CACHE . 'persistent' . DS . $fileName . '.php')) {
            include(CACHE . 'persistent' . DS . $fileName . '.php');
            $found = true;
        } else {
            foreach (Configure::corePaths('s2framework') as $key => $path) {
                if (file_exists($path . DS . 'config' . DS . $fileName . '.php')) {
                    include($path . DS . 'config' . DS . $fileName . '.php');
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            return false;
        }

        if (!isset($config)) {
            $error = __t("Configure::load() - no variable \$config found in %s.php", true);
            trigger_error(sprintf($error, $fileName), E_USER_WARNING);
            return false;
        }
        return Configure::write($config);
    }
/**
 * Used to determine the current version of CakePHP.
 *
 * Usage Configure::version();
 *
 * @link          http://book.cakephp.org/view/416/version
 * @return string Current version of CakePHP
 * @access public
 */
    function version() {
        $_this = Configure::getInstance();

        if (!isset($_this->Cake['version'])) {
            require(CORE_PATH . 'cake' . DS . 'config' . DS . 'config.php');
            $_this->write($config);
        }
        return $_this->Cake['version'];
    }
/**
 * Used to write a config file to disk.
 *
 * Configure::store('Model', 'class.paths', array('Users' => array(
 *      'path' => 'users', 'plugin' => true
 * )));
 *
 * @param string $type Type of config file to write, ex: Models, Controllers, Helpers, Components
 * @param string $name file name.
 * @param array $data array of values to store.
 * @return void
 * @access public
 */
    function store($type, $name, $data = array()) {
        $write = true;
        $content = '';

        foreach ($data as $key => $value) {
            $content .= "\$config['$type']['$key']";

            if (is_array($value)) {
                $content .= " = array(";

                foreach ($value as $key1 => $value2) {
                    $value2 = addslashes($value2);
                    $content .= "'$key1' => '$value2', ";
                }
                $content .= ");\n";
            } else {
                $value = addslashes($value);
                $content .= " = '$value';\n";
            }
        }
        if (is_null($type)) {
            $write = false;
        }
        Configure::__writeConfig($content, $name, $write);
    }
/**
 * Returns a key/value list of all paths where core libs are found.
 * Passing $type only returns the values for a given value of $key.
 *
 * @param string $type valid values are: 'model', 'behavior', 'controller', 'component',
 *                      'view', 'helper', 'datasource', 'libs', and 'cake'
 * @return array numeric keyed array of core lib paths
 * @access public
 */
    function corePaths($type = null) {
        $paths = S2Cache::read('core_paths', '_s2framework_core_');

        if (!$paths) {
            $path = S2_ROOT;
            $s2framework = $path .  DS . 's2framework' . DS;
            $libs = $s2framework . 'libs' . DS;
            $paths['libs'][] = $libs;
            $paths['model'][] = $libs . 'model' . DS;
            $paths['behavior'][] = $libs . 'model' . DS . 'behaviors' . DS;
            $paths['controller'][] = $libs . 'controller' . DS;
            $paths['component'][] = $libs . 'controller' . DS . 'components' . DS;
            $paths['view'][] = $libs . 'view' . DS;
            $paths['helper'][] = $libs . 'view' . DS . 'helpers' . DS;
            $paths['_s2framework_core_'][] = $s2framework;
            $paths['vendor'][] = $path . DS . 'vendors' . DS;
            $paths['shell'][] = $s2framework . 'console' . DS . 'libs' . DS;

            S2Cache::write('core_paths', array_filter($paths), '_s2framework_core_');
        }

        if ($type && isset($paths[$type])) {
            return $paths[$type];
        }

        return $paths;
    }
/**
 * Creates a cached version of a configuration file.
 * Appends values passed from Configure::store() to the cached file
 *
 * @param string $content Content to write on file
 * @param string $name Name to use for cache file
 * @param boolean $write true if content should be written, false otherwise
 * @return void
 * @access private
 */
    function __writeConfig($content, $name, $write = true) {
        $file = CACHE . 'persistent' . DS . $name . '.php';

        if (Configure::read() > 0) {
            $expires = "+10 seconds";
        } else {
            $expires = "+999 days";
        }
        $cache = cache('persistent' . DS . $name . '.php', null, $expires);

        if ($cache === null) {
            cache('persistent' . DS . $name . '.php', "<?php\n\$config = array();\n", $expires);
        }

        if ($write === true) {
            if (!class_exists('S2File')) {
                require LIBS . 'file.php';
            }
            $fileClass = new S2File($file);

            if ($fileClass->writable()) {
                $fileClass->append($content);
            }
        }
    }
/**
 * Checks $name for dot notation to create dynamic Configure::$var as an array when needed.
 *
 * @param mixed $name Name to split
 * @return array Name separated in items through dot notation
 * @access private
 */
    function __configVarNames($name)
    {
        if (is_string($name))
        {
            if (strpos($name, "."))
            {
                return explode(".", $name);
            }
            return array($name);
        }
        return $name;
    }
/**
 * Build path references. Merges the supplied $paths
 * with the base paths and the default core paths.
 *
 * @param array $paths paths defines in config/bootstrap.php
 * @return void
 * @access public
 */
     function buildPaths($paths,$recursive=false,$tempType=null)
     {
        foreach($paths AS $type=>$relPath)
        {
            $type = !is_null($tempType) ? $tempType : $type;
            if(is_array($relPath)){
                $this->buildPaths($relPath,$recursive,$type);
            } else {
                foreach($this->basePaths AS $basePath){
                    $this->__listFiles($basePath,$relPath,$recursive,$type);
                }
            }
        }
        return $this->__items;
    }

   function __listFiles($basePath,$relPath,$recursive,$type) {

        $Folder = new S2Folder($basePath.$relPath);
        $contents = $Folder->read(false, array('.htaccess','index.html','index.php','php.ini'));
        if (is_array($contents)) {
            if($recursive && !empty($contents[0])){
                foreach($contents[0] AS $tmpPath){
                    $this->__listFiles($basePath.$relPath.DS,$tmpPath,$recursive,$type);
                }
            }

            $path_root_replace = PATH_ROOT != DS ? PATH_ROOT : '';

            foreach ($contents[1] as $item) {

                if($item{0} == '.') continue;

                if($recursive){

                    $this->__items[$type][$relPath][strtolower($item)] = ltrim(str_replace($path_root_replace,'',rtrim($relPath,DS)).DS.$item,DS);
                }
                else {

                    $this->__items[$type][strtolower($item)] = ltrim(str_replace($path_root_replace,'',rtrim($basePath.$relPath,DS)).DS.$item,DS);
                }
            }
        }
    }


/**
 * Loads app/config/bootstrap.php.
 * If the alternative paths are set in this file
 * they will be added to the paths vars.
 *
 * @param boolean $boot Load application bootstrap (if true)
 * @return void
 * @access private
 */
    function __loadBootstrap($app, $disable_overrides = false)
    {
        S2Cache::config('_s2framework_core_', array('duration'=>86400,'engine' => 'File','path'=>S2Paths::get($app,'S2_CACHE') . 'core'));

        $fallback_theme = S2Paths::get($app,'S2_FALLBACK_THEME','default');

        $path_root_replace = PATH_ROOT != DS ? PATH_ROOT : '';

        $cache_key = S2CacheKey($app.'_paths');

        if(!$fileArray = S2Cache::read($cache_key,'_s2framework_core_'))
        {
            $configPath = S2Paths::get($app,'S2_APP_CONFIG');

            if (@!include($configPath . 'paths.php')) {
                trigger_error(sprintf(__t("Can't find application paths file. Please create %spaths.php, and make sure it is readable by PHP.", true), $configPath), E_USER_ERROR);
            }

            if($disable_overrides) unset($basePaths['overrides']);

            $this->basePaths = $basePaths;

            $fileArray = $this->buildPaths($relativePaths);

            $Folder = new S2Folder();

            $items = array();

            // Build the Theme file array
            foreach($basePaths AS $basePath)
            {
                if(false === strstr($basePath,'s2framework')) // Ignore the s2framework folder
                {
                    // Front end theme folder
                    $Folder->path = $basePath.$themePath;
                    $baseRelPath = ltrim(str_replace($path_root_replace,'',$basePath),DS);
                    list($dirs) = $Folder->read(true);

                    if(!empty($dirs))
                    {
                        foreach($dirs AS $theme)
                        {
                            $tree = $Folder->tree($basePath.$themePath.DS.$theme);

                            if(is_array($tree) && !empty($tree))
                            {
                                if(strstr($basePath,'com_jreviews_addons')) {
                                    $items['Theme'][$theme]['.info']['location'] = 'addon';
                                 }
                                 elseif(strstr($basePath,'com_jreviews')) {
                                    $items['Theme'][$theme]['.info']['location'] = 'jreviews';
                                 }
                                 elseif(strstr($basePath,'jreviews_overrides')) {
                                    $items['Theme'][$theme]['.info']['location'] = 'overrides';
                                 }

                                $themeRelPath = $baseRelPath.$themePath.DS.$theme.DS;
                                $items['Theme'][$theme]['.info']['path'] = $themeRelPath;
                                // Read theme info xml file
                                $infoFile = $basePath.$themePath.DS.$theme.DS.'themeInfo.xml';
                                if(file_exists($infoFile)) {
                                    $xml = simplexml_load_file($infoFile);
                                    $items['Theme'][$theme]['.info']['name'] = Sanitize::getString($xml->settings,'name');
                                    $items['Theme'][$theme]['.info']['title'] = Sanitize::getString($xml->settings,'title');
                                    $items['Theme'][$theme]['.info']['configuration'] = Sanitize::getString($xml->settings,'configuration',1);
                                    $items['Theme'][$theme]['.info']['fallback'] = Sanitize::getString($xml->settings,'fallback',0);
                                    $items['Theme'][$theme]['.info']['addon'] = Sanitize::getString($xml->settings,'addon',0);
                                    $items['Theme'][$theme]['.info']['mobile'] = Sanitize::getString($xml->settings,'mobile',0);
                                    $items['Theme'][$theme]['.info']['description'] = Sanitize::getString($xml,'description');
                                }
                                else {
                                    // Support for themes without xml file
                                    $items['Theme'][$theme]['.info']['name'] = $theme;
                                    $items['Theme'][$theme]['.info']['title'] = $theme;
                                    $items['Theme'][$theme]['.info']['configuration'] = '1';
                                    $items['Theme'][$theme]['.info']['fallback'] = '0';
                                    $items['Theme'][$theme]['.info']['addon'] = $items['Theme'][$theme]['.info']['location'] == (string) 'addon' ? 1 : 0;
                                    $items['Theme'][$theme]['.info']['mobile'] = '0';
                                    $items['Theme'][$theme]['.info']['description'] = 'themeInfo.xml file is missing for this theme.';
                                }

                                foreach($tree[1] AS $file)
                                {
                                    // Get the theme name and folder to build an array
                                    $path = strtolower(str_replace($basePath.$themePath.DS,'',$file));

                                    if(count(explode(DS,$path))>2)
                                    {
                                        # Adds file to theme folder array
                                        extract(pathinfo($path));
                                        $theme_folder = str_replace($theme.DS,'',$dirname);
                                        $theme_file_path = ltrim(str_replace($path_root_replace,'',$file),DS);
                                        $theme_folder_parts = explode(DS,$theme_folder);
                                        $theme_folder = array_shift($theme_folder_parts);

                                        if($theme_folder == 'theme_images' && !empty($theme_folder_parts)) {
                                            // Adds support for theme images in sub-folders
                                            $basename = implode(DS, $theme_folder_parts) . DS . $basename;
                                        }
                                        elseif(!empty($theme_folder_parts))
                                        {
                                            $basename = array_shift($theme_folder_parts) . DS . $basename;
                                        }

                                        // Exclude images from theme_css folder
                                        if(strstr($theme_folder,'theme_css') && $extension != 'css') continue;
                                        $items['Theme'][$theme][$theme_folder][$basename] = $basename;  // $theme_file_path;
                                        # Add any extra files to the default theme.
                                        # This makes the system think files from different themes are in the default folder without forcing users to move files around

                                        // In Component folder we use full relative path because we don't know which theme has been selected
                                        if($items['Theme'][$theme]['.info']['location'] == "jreviews")
                                        {
                                            $items['Theme'][$theme][$theme_folder][$basename] = $theme_file_path;
                                        }

                                        // For any non-default folder, add missing files to default folder with full relative path
                                        if(
                                                $items['Theme'][$theme]['.info']['location'] != "jreviews" // Not in component folder
                                                &&
                                                !isset($items['Theme'][$fallback_theme][$theme_folder][$basename]) // Not in default folder
                                        ) {
                                            $items['Theme'][$fallback_theme][$theme_folder][$basename] = $theme_file_path;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Admin theme folder
                    $Folder->path = $basePath.$themePathAdmin;
                    list($dirs) = $Folder->read(true);

                    if(!empty($dirs))
                    {
                        foreach($dirs AS $theme)
                        {
                            # Begin theme info
                            if(!isset($items['AdminTheme'][$theme]['.info']))
                            {
                                $themeRelPath = $baseRelPath.$themePathAdmin.DS.$theme.DS;
                                $items['AdminTheme'][$theme]['.info']['path'] = $themeRelPath;
                                $items['AdminTheme'][$theme]['.info']['name'] = $theme;
                                $items['AdminTheme'][$theme]['.info']['title'] = $theme;
                            }

                            $tree = $Folder->tree($basePath.$themePathAdmin.DS.$theme);
                            if(is_array($tree) && !empty($tree))
                            {
                                foreach($tree[1] AS $file)
                                {
                                    // Get the theme name and folder to build an array
                                    $path = strtolower(str_replace($basePath.$themePathAdmin.DS,'',$file));

                                    if(count(explode(DS,$path))>2)
                                    {
                                        # Adds file to theme folder array
                                        extract(pathinfo($path));
                                        $theme_folder = str_replace($theme.DS,'',$dirname);
                                        $theme_file_path = ltrim(str_replace($path_root_replace,'',$file),DS);
                                        $theme_folder_parts = explode(DS,$theme_folder);
                                        $theme_folder = array_shift($theme_folder_parts);
                                        !empty($theme_folder_parts) and $basename = array_shift($theme_folder_parts) . DS . $basename;

                                        // Exclude images from theme_css folder
                                        if(strstr($theme_folder,'theme_css') && $extension != 'css') continue;

                                        $items['AdminTheme'][$theme][$theme_folder][$basename] = $basename; //$theme_file_path;
                                        if(
                                            !strstr($theme_file_path,'components'.DS.'com_jreviews'.DS)
        //                                    &&
        //                                    !isset($items['AdminTheme']['default'][$theme_subdir][$theme_file])
                                        )
                                        {
                                            $items['AdminTheme']['default'][$theme_folder][$basename] = $theme_file_path;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Front end js folder
                    $tree =$Folder->tree($basePath.$jsPath);
                    if(is_array($tree)){
                        foreach($tree[1] AS $file){
                            if(strstr($file,'.js')){
                                // Get the theme name and folder to build an array
                                $path = str_replace(array($basePath.$jsPath.DS,DS),array('',_DS),$file);
                                $items['Javascript'][$path] = ltrim(str_replace(array($path_root_replace,DS),array('',_DS),$file),_DS);
                            }
                        }
                    }

                    // Admin js folder
                    $tree =$Folder->tree($basePath.$jsPathAdmin);
                    if(is_array($tree)){
                        foreach($tree[1] AS $file){
                            if(strstr($file,'.js')){
                                // Get the theme name and folder to build an array
                                $path = str_replace(array($basePath.$jsPathAdmin.DS,DS),array('',_DS),$file);
                                $items['AdminJavascript'][$path] = ltrim(str_replace(array($path_root_replace,DS),array('',_DS),$file),_DS);
                            }
                        }
                    }
                }
            }
            $fileArray = array_merge($fileArray,$items);
//prx($items['AdminTheme']);exit;
//prx($items['Theme']);exit;
// prx($fileArray);exit;
            S2Cache::write($cache_key,$fileArray,'_s2framework_core_');
            unset($Folder,$tree);
        }

        $App = S2App::getInstance();
        $App->{$app.'Paths'} = $fileArray;
        unset($this->__items,$items);
    }

/**
 * Caches the S2Object map when the instance of the Configure class is destroyed
 *
 * @access public
 */
    function __destruct() {
        if ($this->__cache) {
            S2Cache::write('S2Object_map', array_filter($this->__S2Objects), '_s2framework_core_');
        }
    }
}
/**
 * Class and file loader.
 *
 * @link          http://book.cakephp.org/view/499/The-App-Class
 * @since         CakePHP(tm) v 1.2.0.6001
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class S2App extends S2Object {
/**
 * Paths to search for files.
 *
 * @var array
 * @access public
 */
    var $search = array();
/**
 * Whether or not to return the file that is loaded.
 *
 * @var boolean
 * @access public
 */
    var $return = false;
/**
 * Determines if $__maps and $__paths cache should be written.
 *
 * @var boolean
 * @access private
 */
    var $__cache = false;
/**
 * Holds key/value pairs of $type => file path.
 *
 * @var array
 * @access private
 */
    var $__map = array();
/**
 * Holds paths for deep searching of files.
 *
 * @var array
 * @access private
 */
    var $__paths = array();
/**
 * Holds loaded files.
 *
 * @var array
 * @access private
 */
    var $__loaded = array();
/**
 * Finds classes based on $name or specific file(s) to search.
 *
 * @link          http://book.cakephp.org/view/529/Using-App-import
 * @param mixed $type The type of Class if passed as a string, or all params can be passed as
 *                    an single array to $type,
 * @param string $name Name of the Class or a unique name for the file
 * @param mixed $parent boolean true if Class Parent should be searched, accepts key => value
 *              array('parent' => $parent ,'file' => $file, 'search' => $search, 'ext' => '$ext');
 *              $ext allows setting the extension of the file name
 *              based on Inflector::underscore($name) . ".$ext";
 * @param array $search paths to search for files, array('path 1', 'path 2', 'path 3');
 * @param string $file full name of the file to search for including extension
 * @param boolean $return, return the loaded file, the file must have a return
 *                         statement in it to work: return $variable;
 * @return boolean true if Class is already in memory or if file is found and loaded, false if not
 * @access public
 */
    static function import($type = null, $name = null, $app = 'jreviews')
    {
        $nameCopy = $name;
        if(is_array($name)){
            foreach($name AS $n){
                S2App::import($type,$n,$app);
            }
        }
         else
        {
            if(substr($name,0,6)=='admin/'){
                $type = 'Admin'.$type;
                $name = str_replace('admin/','',$name);
            }

            $name = Inflector::underscore($name);
            $type = Inflector::camelize($type);

            switch($type) {
                case 'Controller':
                    $name .= '_controller.php';
                break;
                case 'AdminController':
                    $name .= '_controller.php';
                    break;
                default:
                    $name .= '.php';
                    $nameCopy .= '.php';
                break;
            }

            $_this = S2App::getInstance(); // Load the file registry
//            prx($_this->{$app.'Paths'});exit;

            if(isset($_this->{$app.'Paths'}[$type][$name])){
                $file = PATH_ROOT.$_this->{$app.'Paths'}[$type][$name];
                if($_this->__load($file)) {
                    return true;
                }
            } else {
                if($type == 'Vendor'){
                    if($_this->__load(VENDORS . $nameCopy)) {
                        return true;
                    }
                }
            }
            // Called files not in filesystem:
//            echo $type . '---' .$name .'<br />';
//            prx($_this->{$app.'Paths'});
            return false;
        }
    }
/**
 * Returns a single instance of App.
 *
 * @return object
 * @access public
 */
    static function getInstance() {
        static $instance = array();
        if (!$instance) {
            $instance[0] = new S2App();
            $instance[0]->__map = S2Cache::read('file_map', '_s2framework_core_');
        }
        return $instance[0];
    }
/**
 * Locates the $file in $__paths, searches recursively.
 *
 * @param string $file full file name
 * @param boolean $recursive search $__paths recursively
 * @return mixed boolean on fail, $file directory path on success
 * @access private
 */
    function __find($file, $recursive = true) {
        if (empty($this->search)) {
            return null;
        } elseif (is_string($this->search)) {
            $this->search = array($this->search);
        }

        if (empty($this->__paths)) {
            $this->__paths = S2Cache::read('dir_map', '_s2framework_core_');
        }

        foreach ($this->search as $path) {

            $path = rtrim($path, DS);

            if ($path === rtrim(APP, DS)) {
                $recursive = false;
            }
            if ($recursive === false) {

                if ($this->__load($path . DS . $file)) {
                    return $path . DS;
                }
                continue;
            }
            if (!isset($this->__paths[$path])) {
                if (!class_exists('S2Folder')) {
                    require LIBS . 'folder.php';
                }
                $Folder = new S2Folder();
                $directories = $Folder->tree($path, false, 'dir');
                $this->__paths[$path] = $directories;
            }

            foreach ($this->__paths[$path] as $directory) {
                if ($this->__load($directory . DS . $file)) {
                    return $directory . DS;
                }
            }
        }
        return null;
    }
/**
 * Attempts to load $file.
 *
 * @param string $file full path to file including file name
 * @return boolean
 * @access private
 */
    function __load($file)
    {
        if (empty($file)) {
            return false;
        }
        if (!$this->return && isset($this->__loaded[$file])) {
            return true;
        }

//        if (file_exists($file)) { /* the check is already done via file registry in the import method */
            if (!$this->return) {
                require($file);
                $this->__loaded[$file] = true;
//            }
            return true;
        }
        return false;
    }
/**
 * Maps the $name to the $file.
 *
 * @param string $file full path to file
 * @param string $name unique name for this map
 * @param string $type type S2Object being mapped
 * @param string $plugin if S2Object is from a plugin, the name of the plugin
 * @access private
 */
    function __map($file, $name, $type, $plugin) {
        if ($plugin) {
            $plugin = Inflector::camelize($plugin);
            $this->__map['Plugin'][$plugin][$type][$name] = $file;
        } else {
            $this->__map[$type][$name] = $file;
        }
    }
/**
 * Returns a file's complete path.
 *
 * @param string $name unique name
 * @param string $type type object
 * @param string $plugin if S2Object is from a plugin, the name of the plugin
 * @return mixed, file path if found, false otherwise
 * @access private
 */
    function __mapped($name, $type, $plugin) {
        if ($plugin) {
            $plugin = Inflector::camelize($plugin);

            if (isset($this->__map['Plugin'][$plugin][$type]) && isset($this->__map['Plugin'][$plugin][$type][$name])) {
                return $this->__map['Plugin'][$plugin][$type][$name];
            }
            return false;
        }

        if (isset($this->__map[$type]) && isset($this->__map[$type][$name])) {
            return $this->__map[$type][$name];
        }
        return false;
    }
/**
 * Used to overload S2Objects as needed.
 *
 * @param string $type Model or Helper
 * @param string $name Class name to overload
 * @access private
 */
    function __overload($type, $name) {
        if (($type === 'Model' || $type === 'Helper') && strtolower($name) != 'schema') {
            Overloadable::overload($name);
        }
    }
/**
 * Loads parent classes based on $type.
 * Returns a prefix or suffix needed for loading files.
 *
 * @param string $type type of object
 * @param string $plugin name of plugin
 * @param boolean $parent false will not attempt to load parent
 * @return array
 * @access private
 */
    function __settings($type, $plugin, $parent) {
        if (!$parent) {
            return null;
        }

        if ($plugin) {
            $plugin = Inflector::underscore($plugin);
            $name = Inflector::camelize($plugin);
        }
        $path = null;
        $load = strtolower($type);

        switch ($load) {
            case 'model':
//                if (!class_exists('S2Model')) {
//                    S2App::import('Core', 'Model', S2Paths::get($app,'S2_APP'), false, Configure::corePaths('model'));
//                }
//                if (!class_exists('AppModel')) {
//                    S2App::import($type, 'AppModel', false, Configure::read('modelPaths'));
//                }
                if ($plugin) {
                    if (!class_exists($name . 'AppModel')) {
                        S2App::import($type, $plugin . '.' . $name . 'AppModel', S2Paths::get($app,'S2_APP'), false, array(), $plugin . DS . $plugin . '_app_model.php');
                    }
                    $path = $plugin . DS . 'models' . DS;
                }
                return array('class' => $type, 'suffix' => null, 'path' => $path);
            break;
            case 'behavior':
                if ($plugin) {
                    $path = $plugin . DS . 'models' . DS . 'behaviors' . DS;
                }
                return array('class' => $type, 'suffix' => null, 'path' => $path);
            break;
            case 'controller':
//                S2App::import($type, 'AppController', false);
                if ($plugin) {
                    S2App::import($type, $plugin . '.' . $name . 'AppController', S2Paths::get($app,'S2_APP'), false, array(), $plugin . DS . $plugin . '_app_controller.php');
                    $path = $plugin . DS . 'controllers' . DS;
                }
                return array('class' => $type, 'suffix' => $type, 'path' => $path);
            break;
            case 'component':
                if ($plugin) {
                    $path = $plugin . DS . 'controllers' . DS . 'components' . DS;
                }
                return array('class' => $type, 'suffix' => null, 'path' => $path);
            break;
            case 'view':
                if ($plugin) {
                    $path = $plugin . DS . 'views' . DS;
                }
                return array('class' => $type, 'suffix' => null, 'path' => $path);
            break;
            case 'helper':
//                if (!class_exists('AppHelper')) {
//                    S2App::import($type, 'AppHelper', false);
//                }
                if ($plugin) {
                    $path = $plugin . DS . 'views' . DS . 'helpers' . DS;
                }
                return array('class' => $type, 'suffix' => null, 'path' => $path);
            break;
            case 'vendor':
                if ($plugin) {
                    $path = $plugin . DS . 'vendors' . DS;
                }
                return array('class' => null, 'suffix' => null, 'path' => $path);
            break;
            default:
                $type = $suffix = $path = null;
            break;
        }
        return array('class' => null, 'suffix' => null, 'path' => null);
    }
/**
 * Returns default search paths.
 *
 * @param string $type type of S2Object to be searched
 * @return array list of paths
 * @access private
 */
    function __paths($type, $name, $app) {
        $type = strtolower($type);

        $app_path = S2Paths::get($app,'S2_APP');
        $overrides = S2Paths::get($app,'S2_APP_OVERRIDES');

        if ($type === 'core') {
            $path = Configure::corePaths();
            $paths = array();

            foreach ($path as $key => $value) {
                $count = count($key);
                for ($i = 0; $i < $count; $i++) {
                    $paths[] = $path[$key][$i];
                }
            }
            return $paths;
        }

        if(false!==strpos($name,'admin/')) {
            $type = 'admin_'.$type;
        }

         $paths = array();

        if ($paths = Configure::read($type . 'Paths')) {
            return $paths;
        }

        switch ($type) {
            case 'plugin':
             $paths = array(
                 $overrides . 'plugins' . DS,
                 $app_path . 'plugins' . DS
             );
            break;
            case 'vendor':
             $paths = array(
                 $overrides . 'vendors',
                 $app_path . 'vendors' . DS,
                 VENDORS
                 );
            break;
            case 'controller':
             $paths = array(
                 $overrides . 'controllers' . DS,
                 $app_path . 'controllers' . DS,
                 $app_path);
            break;
            case 'admin_controller':
             $paths = array(
                 $overrides . 'admin_controllers' . DS,
                 $app_path . 'admin_controllers' . DS,
                 $app_path);
            break;
            case 'admin_component':
             $paths = array(
                 $overrides . 'admin_controllers' . DS . 'components' . DS,
                 $app_path . 'admin_controllers' . DS . 'components' . DS,
                 $app_path);
            break;
            case 'component': case 'components':
             $paths = array(
                 $overrides . 'controllers' . DS . 'components' . DS,
                 $app_path . 'controllers' . DS . 'components' . DS,
                 $app_path);
            break;
            case 'model':
             $paths = array(
                 $overrides . 'models' . DS,
                 $overrides . 'controllers' . DS . 'components',
                 $app_path . 'models' . DS,
                 $app_path . 'controllers' . DS . 'components',
                 $app_path);
            break;
            case 'view':
             $paths = array(
                 $overrides . 'views' . DS,
                 $app_path . 'views' . DS);
            break;
            case 'admin_view':
             $paths = array(
                 $overrides . 'views' . DS . 'admin' . DS,
                 $app_path . 'views' . DS . 'admin' . DS);
            break;
            case 'helper': case 'helpers':
             $paths = array(
                 $overrides . 'views' . DS . 'helpers' . DS,
                 $app_path . 'views' . DS . 'helpers' . DS);
            break;
            case 'admin_helper':
             $paths = array(
                 $overrides . 'views' . DS . 'admin' . DS . 'helpers' . DS,
                 $app_path . 'views' . DS . 'admin' . DS . 'helpers' . DS);
            break;
        }

        return $paths;
    }
/**
 * Removes file location from map if the file has been deleted.
 *
 * @param string $name name of object
 * @param string $type type of object
 * @param string $plugin name of plugin
 * @return void
 * @access private
 */
    function __remove($name, $type, $plugin) {
        if ($plugin) {
            $plugin = Inflector::camelize($plugin);
            unset($this->__map['Plugin'][$plugin][$type][$name]);
        } else {
            unset($this->__map[$type][$name]);
        }
    }
/**
 * S2Object destructor.
 *
 * Writes cache file if changes have been made to the $__map or $__paths
 *
 * @return void
 * @access private
 */
    function __destruct() {
        if ($this->__cache) {
            $core = Configure::corePaths('s2framework');
            unset($this->__paths[rtrim($core[0], DS)]);
            S2Cache::write('dir_map', array_filter($this->__paths), '_s2framework_core_');
            S2Cache::write('file_map', array_filter($this->__map), '_s2framework_core_');
        }
    }
}
