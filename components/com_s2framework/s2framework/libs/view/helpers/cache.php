<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
/* SVN FILE: $Id: cache.php 7296 2008-06-27 09:09:03Z gwoo $ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 1.0.0.2277
 * @version			$Revision: 7296 $
 * @modifiedby		$LastChangedBy: gwoo $
 * @lastmodified	$Date: 2008-06-27 02:09:03 -0700 (Fri, 27 Jun 2008) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class CacheHelper extends MyHelper {
/**
 * Array of strings replaced in cached views.
 * The strings are found between <s2:nocache><s2:nocache> in views
 *
 * @var array
 * @access private
 */
	var $__replace = array();
/**
 * Array of string that are replace with there var replace above.
 * The strings are any content inside <s2:nocache><s2:nocache> and includes the tags in views
 *
 * @var array
 * @access private
 */
	var $__match = array();
/**
 * holds the View object passed in final call to CacheHelper::cache()
 *
 * @var object
 * @access public
 */
	var $view;
/**
 * cache action time
 *
 * @var object
 * @access public
 */
	var $cacheAction;
/**
 * Main method used to cache a view
 *
 * @param string $file File to cache
 * @param string $out output to cache
 * @param boolean $cache
 * @return view ouput
 */
	function cache($file, $out, $cache = false, $autoRender = true) {

		$cacheTime = 0;

		$useCallbacks = false;

		if (is_array($this->cacheAction)) {
			$controller = Inflector::underscore($this->controllerName); // new
			$check = str_replace('/', '_', $this->here);
			$replace = str_replace('/', '_', $this->base);
			$match = str_replace($this->base, '', $this->here);
			$match = str_replace('//', '/', $match);
			$match = str_replace('/' . $controller . '/', '', $match); // new
			$match = str_replace('/' . $this->controllerName . '/', '', $match);
			$check = str_replace($replace, '', $check);
			$check = str_replace('_' . $controller . '_', '', $check); // new
			$check = str_replace('_' . $this->controllerName . '_', '', $check);
			$check = Inflector::slug($check);
			$check = preg_replace('/^_+/', '', $check);
			$keys = str_replace('/', '_', array_keys($this->cacheAction));
			$found = array_keys($this->cacheAction);
			$index = null;
			$count = 0;

			foreach ($keys as $key => $value) {
				if (strpos($check, $value) === 0) {
					$index = $found[$count];
					break;
				}
				$count++;
			}

			if (isset($index)) {
				$pos1 = strrpos($match, '/');
				$char = strlen($match) - 1;

				if ($pos1 == $char) {
					$match = substr($match, 0, $char);
				}

				$key = $match;
			} elseif ($this->action == 'index') {
				$index = 'index';
			}

			$options = $this->cacheAction;
			if (isset($this->cacheAction[$index])) {
				if (is_array($this->cacheAction[$index])) {
					$options = array_merge(array('duration'=> 0, 'callbacks' => false), $this->cacheAction[$index]);
				} else {
					$cacheTime = $this->cacheAction[$index];
				}
			}

			if (array_key_exists('duration', $options)) {
				$cacheTime = $options['duration'];
			}
			if (array_key_exists('callbacks', $options)) {
				$useCallbacks = $options['callbacks'];
			}

		} else {
			$cacheTime = $this->cacheAction;
		}

		if ($cacheTime != '' && $cacheTime > 0) {

			$this->__parseFile($file, $out);

			if ($cache === true) {

				$cached = $this->__parseOutput($out);

				$this->__writeFile($cached, $cacheTime, $useCallbacks, $autoRender);
			}
			return $out;
		} else {
			return $out;
		}
	}
/**
 * Parse file searching for no cache tags
 *
 * @param string $file
 * @param boolean $cache
 * @access private
 */
	function __parseFile($file, $cache) {

		if (is_file($file)) {
			$file = file_get_contents($file);
		}

		preg_match_all('/(<s2:nocache>(?<=<s2:nocache>)[\\s\\S]*?(?=<\/s2:nocache>)<\/s2:nocache>)/i', $cache, $oresult, PREG_PATTERN_ORDER);
		preg_match_all('/(?<=<s2:nocache>)([\\s\\S]*?)(?=<\/s2:nocache>)/i', $file, $result, PREG_PATTERN_ORDER);

		if (!empty($this->__replace)) {
			foreach ($oresult['0'] as $k => $element) {
				$index = array_search($element, $this->__match);
				if ($index !== false) {
					array_splice($oresult[0], $k, 1);
				}
			}
		}
		if (!empty($result['0'])) {
			$count = 0;

			foreach ($result['0'] as $block) {
				if (isset($oresult['0'][$count])) {
					$this->__replace[] = $block;
					$this->__match[] = $oresult['0'][$count];
				}
				$count++;
			}
		}
	}
/**
 * Parse the output and replace cache tags
 *
 * @param sting $cache
 * @return string with all replacements made to <s2:nocache><s2:nocache>
 * @access private
 */
	function __parseOutput($cache) {
		$count = 0;
		if (!empty($this->__match)) {

			foreach ($this->__match as $found) {
				$original = $cache;
				$length = strlen($found);
				$position = 0;

					for ($i = 1; $i <= 1; $i++) {
						$position = strpos($cache, $found, $position);

						if ($position !== false) {
							$cache = substr($original, 0, $position);
							$cache .= $this->__replace[$count];
							$cache .= substr($original, $position + $length);
						} else {
							break;
						}
					}
					$count++;
			}
			return $cache;
		}
		return $cache;
	}
/**
 * Write a cached version of the file
 *
 * @param string $file
 * @param sting $timestamp
 * @return cached view
 * @access private
 */
	function __writeFile($content, $timestamp, $useCallbacks = false, $autoRender = true)
     {
		$now = time();

		if (is_numeric($timestamp)) {
			$cacheTime = $now + $timestamp;
		} else {
			$cacheTime = strtotime($timestamp, $now);
		}

		$path = $this->here;

		if ($this->here == '/') {
			$path = 'home';
		}

		$cache = Inflector::slug($path);

		if (empty($cache)) {
			return;
		}

		$cache = $cache . '.php';

		$file = '<!--cachetime:' . $cacheTime . '--><?php';

		if(!$autoRender) {
			$file .= '
			defined(\'MVC_FRAMEWORK\') or die(\'Direct Access to this location is not allowed.\');
			';
		} elseif (empty($this->plugin)) {
			$file .= '
			defined(\'MVC_FRAMEWORK\') or die(\'Direct Access to this location is not allowed.\');
			S2App::import(\'Controller\', \'' . $this->controllerName. '\', \''. $this->app . '\');
			';
		} else { // Not used because there are no plugins in jReviews
			$file .= '
			defined(\'MVC_FRAMEWORK\') or die(\'Direct Access to this location is not allowed.\');
			S2App::import(\'Controller\', \'' . $this->plugin . '.' . $this->controllerName. '\',\''. $this->app . '\');
			';
		}

		if(!$autoRender) {

			$file .= '
					$loadedHelpers = array();
					$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);
					foreach (array_keys($loadedHelpers) as $helper) {
						$camelBackedHelper = Inflector::camelize($helper);
						${$camelBackedHelper} =& $loadedHelpers[$helper];
						$this->loaded[$helper] =& ${$camelBackedHelper};
					}
			?>';

		} else	{

			$file .= '$class_params = array(
						\'app\'=>"'.$this->app.'",
						\'name\'=>"'.$this->name.'",
						\'action\'=>"'.$this->action.'"
					  );
					';

			$file .= '$controller = new ' . inflector::camelize($this->controllerName) . 'Controller($class_params);

					$controller->helpers = $this->helpers = unserialize(base64_decode(\'' . base64_encode(serialize($this->helpers)) . '\'));
					$controller->app = $this->app = \'' . $this->app . '\';
					$controller->base = $this->base = \'' . $this->base . '\';
					$controller->layout = $this->layout = \'' . $this->layout. '\';
					$controller->listview = $this->listview = \'' . Sanitize::getString($this,'listview',''). '\';
					$controller->here = $this->here = \'' . $this->here . '\';
					$controller->page = $this->page = ' . $this->page . ';
					$controller->limit = $this->limit = ' . $this->limit . ';
					$controller->ajaxRequest = $this->ajaxRequest = \'' . $this->ajaxRequest . '\';
					$controller->viewSuffix = $this->viewSuffix = \'' . $this->viewSuffix . '\';
					$controller->viewImages = $this->viewImages = \'' . $this->viewImages . '\';
					$controller->params = $this->params = unserialize(base64_decode(\'' . base64_encode(serialize($this->params)) . '\'));
                    $controller->name = $this->name = \'' . $this->name . '\';
					$controller->action = $this->action = \'' . $this->action . '\';
					$controller->data = $this->data = unserialize(base64_decode(\'' . base64_encode(serialize($this->data)) . '\'));
                    $controller->assets = $this->assets = unserialize(base64_decode(\'' . base64_encode(serialize($this->assets)) . '\'));
					$controller->viewVars = $this->viewVars = unserialize(base64_decode(\'' . base64_encode(serialize($this->viewVars)) . '\'));
					isset($this->viewVars[\'page\']) and $page = $this->viewVars[\'page\'];
					';

			$file .= '
					$loadedHelpers = array();
					$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);
					foreach (array_keys($loadedHelpers) as $helper) {
						$camelBackedHelper = Inflector::camelize($helper);
						${$camelBackedHelper} =& $loadedHelpers[$helper];
						$this->loaded[$helper] =& ${$camelBackedHelper};
					}
			';

/*            if ($useCallbacks == true) {
                $file .= '
                    $controller->constructClasses();
                    $controller->Component->initialize($controller);
                    $controller->beforeFilter();
                    $controller->Component->startup($controller);
                    $controller->afterFilter();
                    ';
            }
*/
                $file .= '$controller->afterFilter();';

            $file .= '
            	if(isset($Paginator)) {
            		$Paginator->limit = $this->limit;
            		$Paginator->passedArgs = $this->params[\'url\'];
            		$Paginator->paginate(array(
						\'current_page\'=>$this->page,
						\'items_per_page\'=>$this->limit,
						\'items_total\'=>$this->viewVars[\'pagination\'][\'total\'],
					));
            	}
            ';

            $file .= '?>';
		}

		$content = preg_replace("/(<\\?xml)/", "<?php echo '$1';?>",$content);

		$file .= $content;

		return cache('views' . DS . $cache, $file, $timestamp);
	}
}