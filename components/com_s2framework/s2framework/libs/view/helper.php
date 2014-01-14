<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MyHelper extends S2Object{

	var $viewTheme = 'default';
	var $viewImages;
	var $viewImagesPath;
	var $app;

	function __construct($app = 'jreviews')
    {
        parent::__construct();

		if(!empty($this->helpers))
		{
			$this->app = $app;

			S2App::import('Helper',$this->helpers,$this->app);

			foreach($this->helpers AS $helper)
			{
				$method_name = inflector::camelize($helper);
				$class_name = $method_name.'Helper';

				if (!isset($this->loaded[$method_name])) {
					$this->{$method_name} =  ClassRegistry::getClass($class_name);
					$this->loaded[$method_name] =& ${$method_name};
				}
			}
		}

	}

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		Rick Ellis
 * @copyright	Copyright (c) 2006, EllisLab, Inc.
 * @license		http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 *
 */
	function _parseAttributes($attributes)
	{
		$att = '';
		if(is_array($attributes) && !empty($attributes))
		{
			foreach ($attributes AS $key => $val)
			{
				if ($key == 'value'){
					$val = $this->form_prep($val);
				}
				$att .= ' '.$key . '="' . $val . '" ';
			}
		}

		return $att;
	}

	function output($str) {
		return $str;
	}

	function form_prep($str = '')
	{
		if ($str === ''){
			return '';
		}

		$temp = '__TEMP_AMPERSANDS__';

		// Replace entities to temporary markers so that
		// htmlspecialchars won't mess them up
		$str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);
		$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);

		$str = htmlspecialchars($str);

		// In case htmlspecialchars misses these.
		$str = str_replace(array("'", '"'), array("&#39;", "&quot;"), $str);

		// Decode the temp markers back to entities
		$str = preg_replace("/$temp(\d+);/","&#\\1;",$str);
		$str = preg_replace("/$temp(\w+);/","&\\1;",$str);

		return $str;
	}

}