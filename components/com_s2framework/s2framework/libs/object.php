<?php
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *                                1785 E. Sahara Avenue, Suite 490-204
 *                                Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 *
 * @modified by ClickFWD LLC
 */

class S2Object {

    var $cmsVersion;
    var $viewSuffix;
    var $viewTheme;
    var $app;
    var $file_prefix = false;

    function __construct()
    {
        if (method_exists($this, '__destruct'))
        {
            register_shutdown_function(array(&$this, '__destruct'));
        }

        $this->cmsVersion = getCmsVersion();
    }

    function locateThemeFile($action,$file,$ext='.thtml', $forceFrontEnd = false)
    {
        $fallback_theme = 'default';
        $file = strtolower($file);

        if(isset($this->Config))
        {
            $fallback_theme = Sanitize::getString($this->Config,'fallback_theme','default');
        }
        else
        {
            $Config = Configure::read('JreviewsSystem.Config');
            if(!empty($Config)) $fallback_theme = Sanitize::getString($Config,'fallback_theme','default');
        }

        $path = false;
        $action = strtolower($action);
        $App = S2App::getInstance();
        $suffix = strtolower($this->viewSuffix);
        if(is_string($forceFrontEnd))
        {
            $location = $forceFrontEnd;
        } else {
            $location = $forceFrontEnd ? 'Theme' : (defined('MVC_FRAMEWORK_ADMIN') ? 'AdminTheme' : 'Theme');
        }
//        echo 'app: ' . $this->app . '<br />';
//        echo 'theme: ' . $this->viewTheme. '<br />';
//        echo 'suffix: ' . $this->viewSuffix. '<br />';
//        echo $location.DS.$this->viewTheme.DS.$action.DS.$file.$this->viewSuffix.$ext.'<br />';

        if(isset($App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$suffix.$ext]))
            { // Selected theme w/ suffix
                $path =  $App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$suffix.$ext] == $file.$suffix.$ext
                        ?
                            $App->{$this->app.'Paths'}[$location][$this->viewTheme]['.info']['path']
                            . $action . DS
                            . $file.$suffix.$ext
                        :
                            $App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$suffix.$ext]
                        ;
            }
        elseif(isset($App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$suffix.$ext]))
            { // Fallback theme w/ suffix
                   $path =  $App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$suffix.$ext] == $file.$suffix.$ext
                    ?
                        $App->{$this->app.'Paths'}[$location][$fallback_theme]['.info']['path']
                        . $action . DS
                        . $file.$suffix.$ext
                    :
                        $App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$suffix.$ext]
                    ;
            }
        elseif(isset($App->{$this->app.'Paths'}[$location]['default'][$action][$file.$suffix.$ext]))
            { // Default theme w/ suffix
                   $path =  $App->{$this->app.'Paths'}[$location]['default'][$action][$file.$suffix.$ext] == $file.$suffix.$ext
                    ?
                        $App->{$this->app.'Paths'}[$location]['default']['.info']['path']
                        . $action . DS
                        . $file.$suffix.$ext
                    :
                        $App->{$this->app.'Paths'}[$location]['default'][$action][$file.$suffix.$ext]
                    ;
            }
        elseif(isset($App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$ext]))
            { // Selected theme w/o suffix
                    $path = $App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$ext] == $file.$ext
                        ?
                            $App->{$this->app.'Paths'}[$location][$this->viewTheme]['.info']['path']
                            . $action . DS
                            . $file.$ext
                        :
                            $App->{$this->app.'Paths'}[$location][$this->viewTheme][$action][$file.$ext]
                        ;
            }
        elseif(isset($App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$ext]))
            {   // Fallback theme w/o suffix
                    $path = $App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$ext] == $file.$ext
                    ?
                        $App->{$this->app.'Paths'}[$location][$fallback_theme]['.info']['path']
                        . $action . DS
                        . $file.$ext
                    :
                        $App->{$this->app.'Paths'}[$location][$fallback_theme][$action][$file.$ext]
                    ;
            }
        elseif(isset($App->{$this->app.'Paths'}[$location]['default'][$action][$file.$ext]))
            {   // Default theme w/o suffix
                    $path = $App->{$this->app.'Paths'}[$location]['default'][$action][$file.$ext] == $file.$ext
                    ?
                        $App->{$this->app.'Paths'}[$location]['default']['.info']['path']
                        . $action . DS
                        . $file.$ext
                    :
                        $App->{$this->app.'Paths'}[$location]['default'][$action][$file.$ext]
                    ;
            }

        return $path ? PATH_ROOT . $path : false;
    }

    function locateScript($file,$options = array())
    {
        $defaults = array('admin'=>false,'relative'=>false,'minified'=>false,'params'=>'');

        $options = array_insert($defaults, $options);

        extract($options);

        $url = $file_min = false;

        $parse = parse_url($file);

        $file = $parse['path'];

        if(substr($file,-3) != '.js') {

            $file_min = $file.'-ck.js';

            $file = $file.'.js';
        }

        $file = str_replace(DS,_DS,$file);

        $App = S2App::getInstance($this->app);

        if($minified && isset($App->{$this->app.'Paths'}[($admin ? 'Admin' : '').'Javascript'][$file_min])) {

            $url = ($relative ? WWW_ROOT_REL : WWW_ROOT) . $App->{$this->app.'Paths'}[($admin ? 'Admin' : '').'Javascript'][$file_min];
        }
        elseif(isset($App->{$this->app.'Paths'}[($admin ? 'Admin' : '').'Javascript'][$file]))
        {
            $url = ($relative ? WWW_ROOT_REL : WWW_ROOT) . $App->{$this->app.'Paths'}[($admin ? 'Admin' : '').'Javascript'][$file];

        }

        if(isset($parse['query']) || $params != '') {

            $url .= '?' . (isset($parse['query']) ? $parse['query'] . '&' . $params : $params);
        }

        return $url;
    }

/**
 * Calls a controller's method from any location.
 *
 * @param string $url URL in the form of Cake URL ("/controller/method/parameter")
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.
 * @return mixed Success (true/false) or contents if 'return' is set in $extra
 * @access public
 */
    function requestAction($url, $extra = array())
    {
         $app = Sanitize::getString($extra,'app','jreviews');
        unset($extra['app']);

        if (empty($url)) {
            return false;
        }
        if (!class_exists('S2Dispatcher')) {
            require S2_FRAMEWORK . DS . 'dispatcher.php';
        }
        if (in_array('return', $extra, true)) {
            $extra = array_merge($extra, array('return' => 0, 'autoRender' => 1));
        }

        $params = array_merge(array('token'=>cmsFramework::formIntegrityToken($extra,array('module','module_id','form','data'),false),'autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1), $extra);

        $disable404 = true;
        $dispatcher = new S2Dispatcher($app,null,$disable404);

        return $dispatcher->dispatch($url, $params);
     }

 /**
 * Stop execution of the current script
 *
 * @param $status see http://php.net/exit for values
 * @return void
 * @access public
 */
    function _stop($status = 0) {
        exit($status);
    }
}
