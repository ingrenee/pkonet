<?php
defined('MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * Methods for displaying presentation data in the view.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 *
 * @modified by ClickFWD LLC
 */

class MyView extends S2Object{

/**
 * Path parts for creating links in views.
 *
 * @var string Base URL
 * @access public
 */
	var $base = null;
/**
 * Stores the current URL (for links etc.)
 *
 * @var string Current URL
 */
	var $here = null;

	var $autoLayout	= true;
	var $auto = true;
	var $ext = '.thtml';
	var $layout = 'default';
	var $listview;
	var $loaded = array();
	var $helpers = array();
	var $viewVars = array();
	var $viewVarsAssets = array();
	var $viewTheme = 'default';
	var $hasRendered = false;
	var $app = 'jreviews';
    var $inAdmin;

    // For error catching
    var $bufferedErrors = array();

	# remove unused keys
	var $__passedVars = array('app','assets','viewVars', 'viewVarsAssets','view', 'Access', 'action', 'autoLayout', 'autoRender', 'base', 'cacheAction', 'Config', 'webroot', 'helpers', 'here', 'limit', 'layout', 'modelNames', 'module_limit', 'module_page', 'name', 'offset', 'page', 'pageTitle', 'listview', 'viewSuffix', 'viewTheme', 'viewPath', 'params', 'data', 'webservices', 'plugin', 'passedArgs', 'rawData', 'ajaxRequest');

	# These are passed to the Helper Object for use in helpers
	var $__helperVars = array('Access','action','app','assets','name','data','page','params','passedArgs','Config','limit','module_limit','module_page','listview','viewSuffix','viewTheme','viewImages','viewImagesPath','ajaxRequest');

	function __construct(&$controller, $register = true){

		if (is_object($controller)) {

			$count = count($this->__passedVars);

			for ($j = 0; $j < $count; $j++) {

				if (isset($this->__passedVars[$j]) && isset($controller->{$this->__passedVars[$j]}))
				{
					$var = $this->__passedVars[$j];
					$this->{$var} = $controller->{$var};
				}
			}
		}

//		$inAdmin = $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN') ? true : false;

		$theme = Configure::read('Theme.name','default');
        if(!isset($this->viewImages))
        {
            $this->viewImagesPath = S2Object::locateThemeFile('.info','path','').'theme_images'.DS;
            $this->viewImages = pathToUrl($this->viewImagesPath,true);
		}

		parent::__construct();

/*		if ($register) {
			ClassRegistry::addObject('view', $this);
		}		*/

	}

	function render($action = null, $file = null, $layout = null)
    {
		if ($this->hasRendered) {
			return true;
		}

		$out = false;

		if($action === null) {
			$action = strtolower($this->name);
		} else {
			$action = strtolower($action);
		}


		if($file === null) {
			$file = strtolower($this->action);
		} else {
			$this->view = $file;
			$file = strtolower($file);
		}

    	// Finds the view file
		$viewPath = S2Object::locateThemeFile($action,$file,$this->ext);

        $out = $this->_render($viewPath, $this->viewVars);

		# Set layout file
		if ($layout === null) {

			$layout = $this->layout;

		}

		if ($out !== false) {

			if ($layout && $this->autoLayout)
            {
				$out = $this->renderLayout($out, $layout);

				if (isset($this->loaded['Cache']) && Configure::read('Cache.view') === true) {

					$replace = array('<s2:nocache>', '</s2:nocache>');

					$out = str_replace($replace, '', $out);
				}
			}

			$this->hasRendered = true;

			if($this->autoRender) {

				print trim($out);
			}
			else {

				return trim($out);
			}

		} else {

			$out = $this->_render($viewFileName, $this->viewVars);

			$msg = __("Error in view %s, got: <blockquote>%s</blockquote>", true);

			trigger_error(sprintf($msg, $viewFileName, $out), E_USER_ERROR);
		}

		return true;
	}

	function renderLayout($content_for_layout, $layout = null) {

		$layout_fn = $this->_getLayoutFileName($layout);

		$data_for_layout = array_merge($this->viewVars,
			array(
				'content_for_layout' => $content_for_layout
			)
		);

		if (empty($this->loaded) && !empty($this->helpers))
		{
			$loadHelpers = true;

		} else {
			$loadHelpers = false;
			$data_for_layout = array_merge($data_for_layout, $this->loaded);
		}

		if (substr($layout_fn, -3) === 'ctp' || substr($layout_fn, -5) === 'thtml') {
			$this->output = MyView::_render($layout_fn, $data_for_layout, $loadHelpers, true);
		} else {
			$this->output = $this->_render($layout_fn, $data_for_layout, $loadHelpers);
		}
//		$out = $this->_render($layout_fn, $data_for_layout, $loadHelpers);

		if ($this->output === false)
		{
			$this->output = $this->_render($layout_fn, $data_for_layout);
			trigger_error(sprintf(__("Error in layout %s, got: <blockquote>%s</blockquote>", true), $layout_fn, $this->output), E_USER_ERROR);
			return false;
		}

/*		if (!empty($this->loaded)) {
			$helpers = array_keys($this->loaded);
			foreach ($helpers as $helperName) {
				$helper =& $this->loaded[$helperName];
				if (is_object($helper)) {
					if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
						$helper->afterLayout();
					}
				}
			}
		}		*/

		return $this->output;
	}

	function _getLayoutFileName($name = null) {

		if ($name === null) {
			$name = $this->layout;
		}

		// Finds the view file
		$layoutPath = S2Object::locateThemeFile('theme_layouts',$name,$this->ext);

		return $layoutPath;
	}

	function renderElement($name, $params = array(), $loadHelpers = false) {
		return $this->element($name, $params, $loadHelpers);
	}

	function element($name, $params = array(), $loadHelpers = false)
    {
		// Finds the view file
		$elementPath = S2Object::locateThemeFile('elements',$name,$this->ext);
		if ($elementPath)
        {
			$params = array_merge_recursive($params, $this->loaded);
			return $this->_render($elementPath, array_merge($this->viewVars, $params), $loadHelpers);
		}
	}

	function renderControllerView($controller, $name, $params = array(), $loadHelpers = false)
    {
		// Finds the view file
		$viewPath = S2Object::locateThemeFile($controller,$name,$this->ext);

		if ($viewPath) {

			$params = array_merge_recursive($params, $this->loaded);

			return $this->_render($viewPath, array_merge($this->viewVars, $params), $loadHelpers);

		}
	}

	function _render($___viewFn, $dataForView, $loadHelpers = true, $cached = false) {

		// Load and initialize helper classes
		if ($loadHelpers && !(empty($this->helpers))) {

			S2App::import('Helper',$this->helpers,$this->app);

			foreach($this->helpers AS $helper)
			{
				$helper = str_replace(MVC_ADMIN._DS,'',$helper);

				$method_name = inflector::camelize($helper);

				$class_name = $method_name.'Helper';

				if (!isset($this->loaded[$method_name])) {

                    if(in_array($class_name,array('RoutesHelper','PaginatorHelper','JreviewsHelper')))  {

                    	/*creates problem with $this->requestAction method - pagination urls are affected for component */

                        ${$method_name} = new $class_name($this->app);
                    }
                    else {

                        ${$method_name} = ClassRegistry::getClass($class_name,$this->app);
                    }

					$this->loaded[$method_name] = & ${$method_name};

					# Pass View vars to Helper Object
					foreach($this->__helperVars AS $helperVar) {

						if(isset($this->$helperVar)) {

							${$method_name}->$helperVar = $this->$helperVar;
						}
					}

                    if(method_exists(${$method_name},'startup')) ${$method_name}->startup();
                }
			}
		}

		if (!empty($dataForView)) {
			extract($dataForView, EXTR_SKIP);
		}

		if(!file_exists($___viewFn)) {
			return '<br />The template file ' . $___viewFn . ' is missing.';
		}

		ob_start();

		if (Configure::read() > 0) {
			include ($___viewFn);
		} else {
			@include ($___viewFn);
		}

		$out = ob_get_clean();

		$cacheConf = Configure::read('Cache');

		$cache_enabled = $cacheConf['view'] || Sanitize::getBool($cacheConf,Inflector::camelize($this->name));

		if(isset($this->loaded['Cache']) && (($this->cacheAction != false)) && $cache_enabled) {

			Configure::write('Cache.view',true);

			if (is_a($this->loaded['Cache'], 'CacheHelper')) {

				$viewVars = array_intersect_key($this->viewVars, array_flip($this->viewVarsAssets));

				if(isset($this->viewVars['page'])) $viewVars['page'] = $this->viewVars['page'];

				if(isset($this->viewVars['pagination'])) $viewVars['pagination'] = $this->viewVars['pagination'];

				$cache =& $this->loaded['Cache'];
				$cache->base = $this->base;
				$cache->here = $this->here;
				$cache->helpers = $this->helpers;
				$cache->action = $this->action;
				$cache->controllerName = $this->name;
				$cache->layout	= $this->layout;
				$cache->cacheAction = $this->cacheAction;
				$cache->viewVars = $viewVars;
				$cache->cache($___viewFn, $out, $cached);
			}
		}

		// Remove the nocache from all pages for xhtml compliance
		if (Configure::read('Cache.disable') || !Configure::read('Cache.view')) {

			$replace = array('<s2:nocache>', '</s2:nocache>');

			$out = str_replace($replace, '', $out);
		}

		if(Sanitize::getBool($this->viewVars,'themeDebug')) {

			$themePath = '<div class="jrThemeDebug">'
				.'<span class="jrStatusLabel jrDebug">'.$this->name.'_controller.php</span>'
				.'<br />'
				.'<span class="jrStatusLabel jrDebug">function '.$this->action.'</span>'
				.'<br />'
				.str_replace(array(PATH_ROOT,$this->viewTheme),array('','<span class="jrStatusLabel jrDebugHighlight">'.$this->viewTheme.'</span>'),$___viewFn)
				.'</div>';

			return '<div class="jrThemeDebugDiv jrClearfix">'.$themePath.$out.'</div>';
		}

		return $out;

	}

function &_loadHelpers(&$loaded, $helpers, $parent = null) {

	S2App::import('Helper',$this->helpers,$this->app);

	foreach($helpers AS $helper)
	{
		$helper = str_replace(MVC_ADMIN._DS,'',$helper);

		$method_name = inflector::camelize($helper);

		$class_name = $method_name.'Helper';

		if (!isset($this->loaded[$method_name])) {

			${$method_name} = ClassRegistry::getClass($class_name);
			$loaded[$method_name] = & ${$method_name};

			# Pass View vars to Helper Object
			foreach($this->__helperVars AS $helperVar) {
				if(isset($this->$helperVar)) {
					$loaded[$method_name]->$helperVar = $this->$helperVar;
				}
			}
		}
	}

	return $loaded;

}

/**
 * Render cached view
 *
 * @param string $filename the cache file to include
 * @param string $timeStart the page render start time
 */
	function renderCache($filename, $timeStart)
    {
		ob_start();

		include ($filename);

		if (Configure::read() > 0 && $this->layout != 'xml') {
			echo "<!-- Cached Render Time: " . round(S2getMicrotime() - $timeStart, 4) . "s -->";
		}

		$out = ob_get_clean();

		if (preg_match('/^<!--cachetime:(\\d+)-->/', $out, $match)) {

			if (time() >= $match['1']) {
				@unlink($filename);
				unset ($out);
				return false;
			} else {
				if ($this->layout === 'xml') {
					header('Content-type: text/xml');
				}
				$out = str_replace('<!--cachetime:'.$match['1'].'-->', '', $out);
				// Remove the nocache from all pages for xhtml compliance
				$replace = array('<s2:nocache>','</s2:nocache>');
				$out = str_replace($replace,'', $out);
				return $out;
			}
		}
	}

}