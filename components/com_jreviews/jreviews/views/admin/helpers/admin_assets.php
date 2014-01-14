<?php
class AdminAssetsHelper extends MyHelper
{
    var $helpers = array('html','libraries','custom_fields','editor');

    var $assetParams = array();

    var $useJavascriptLoader = true;

    var $useMinifiedScripts = true;

    // Keep a record of js files already loaded to avoid duplicates when using Head JS Javascript Loader
    var $jsPaths = array();

    /**
    * These arrays can be set at the controller level
    * and in plugin callbacks with any extra css or js files that should be loaded
    *
    * @var mixed
    */
    var $assets = array(
        'js'=>array(
            'jquery',
            'jq.ui',
            'jq.multiselect',
            'jq.rating',
            'jq.video',
            'jq.audio',
            'jq.audio.playlist',
            'media',
            'admin/admin',
            'admin/addon_everywhere',
            'fields',
            ),
        'css'=>array(
            'admin/custom-theme/jquery-ui-1.9.2.custom',
            'admin/multiselect/jquery.multiselect',
            'admin/default',
            'admin/admin',
            'admin/form',
            'jq.video'
            ),
        'absurl'=>array()
        );

    var $inline = false;

    function load()
    {
        $assetParams = func_get_args();
        $this->assetParams = array_merge($this->assetParams,$assetParams);
        $methodAction = Inflector::camelize($this->name.'_'.$this->action);
        $methodName = Inflector::camelize($this->name);

		if(method_exists($this,$methodAction)){

            $this->{$methodAction}();
        }
        elseif(method_exists($this,$methodName)) {

            $this->{$methodName}();
        }
        elseif(!empty($this->assets)) {

            $this->send($this->assets);
        }
    }

    function send($assets,$inline=false)
    {
        # Load javascript libraries
        $this->Html->app = $this->app;

        // if(!$inline) {
        //     $inline = $this->inline;
        // }

        unset($this->viewVars);

        $joomla_version = cmsFramework::getVersion();

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

                cmsFramework::addScript($head, $inline);
            }
        }

        // Incorporate controller set assets before sending
        if(!empty($this->assets['js'])) {

            $assets['js'] = array_merge($this->assets['js'],$assets['js']);
        }

        if(!empty($this->assets['css'])) {

            $assets['css'] = array_merge($assets['css'],$this->assets['css']);
       }

        if(isset($this->Config) && Sanitize::getString($this->Config,'version')) {

            $version = explode('.',$this->Config->version);

            $build = array_pop($version);
        }
        else {
            $build = 1;
        }

        /***********************************************************
         *                      LOAD CSS                           *
        /***********************************************************/

        $assets['css'][] = 'custom_styles';

        $assets['css'] = array_unique($assets['css']);

        $this->Html->css(arrayFilter($assets['css'], $this->Libraries->css()), array('inline'=>$inline,'params'=>$build));

        /***********************************************************
         *                      LOAD JS                            *
        /***********************************************************/

        // Load locale language object
        $locale_js = class_exists('JreviewsLocale') ? WWW_ROOT_REL . 'components/com_s2framework/tmp/cache/core/admin-locale-'.cmsFramework::getLocale().'.js' : '';

         // Check is done against constants defined in those applications
        $assets['js'][] = 'jquery/i18n/jquery.ui.datepicker-' . cmsFramework::locale();

        if($joomla_version >= 3) {
            unset($assets['js']['jquery']);
        }

        $assets['js'] = array_unique($assets['js']);

        $jsPaths = array();

        if($this->useJavascriptLoader) {

            $jsFiles = arrayFilter($assets['js'], $this->Libraries->js());

            $absUrls = Sanitize::getVar($this->assets,'absurl',array());

            foreach($jsFiles AS $jsfile) {

                if(!isset($this->jsPaths[$jsfile])) {

                    $admin_file = substr($jsfile, 0,6) == 'admin/';

                    $new_jsfile = $admin_file ? str_replace('admin/','',$jsfile) : $jsfile;

                    $relative = in_array($new_jsfile,$absUrls) ? false : true;

                    $js_path = $this->locateScript($new_jsfile,array('admin'=>$admin_file,'relative'=>$relative,'minified'=>$this->useMinifiedScripts));

                    if($js_path) {

                        $this->jsPaths[$jsfile] = $js_path;

                        $jsPaths[] = $js_path;
                    }
                }
            }

            if($locale_js != '' && !isset($this->jsPaths['jreviews-locale'])) {

                array_unshift($jsPaths, $locale_js);

                $this->jsPaths['jreviews-locale'] = $locale_js;
            }

            $assets['js'] = array('head.load.min'); // Load script for async loading

        }
        else {

            $assets['js'] = array_unique($assets['js']);

            cmsFramework::addScript('<script type="text/javascript" src="'.$locale_js.'"></script>');

        }

        $this->Html->js(arrayFilter($assets['js'], $this->Libraries->js()),array('inline'=>$inline,'params'=>$build,'minified'=>$this->useMinifiedScripts));

        // Send scripts to head using Javascript Loader
        if(!empty($jsPaths)) {

            $jsPaths = "'".implode("','",$jsPaths)."'";

            cmsFramework::addScript("<script type='text/javascript'>head.js($jsPaths);</script>");
        }

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-bottom'])) {

            foreach($this->assets['head-bottom'] AS $head) {

                cmsFramework::addScript($head, $inline);
            }
        }
    }

    function AdminMediaUploadCreate() {

        $assets  = array('js'=>array('jq.uploader'));

        $this->send($assets);
    }

}