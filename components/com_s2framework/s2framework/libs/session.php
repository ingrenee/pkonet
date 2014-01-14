<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MvcSession extends S2Object{
	
	var $session;
	
	 function __construct() {
		if(!isset($_SESSION)) {
			session_start();
		}
						
		$this->session = &$_SESSION;				
	}
	
	function &get($name, $default = null, $namespace = 'default')
	{
		$namespace = '__'.$namespace;
		
		if (isset($this->session[$namespace][$name])) {
			return $this->session[$namespace][$name];
		}
		return $default;
	}

	function set($name, $value, $namespace = 'default')
	{
		$namespace = '__'.$namespace;

		$old = isset($this->session[$namespace][$name]) ?  $this->session[$namespace][$name] : null;

		if (null === $value) {
			unset($this->session[$namespace][$name]);
		} else {
			$this->session[$namespace][$name] = $value;
		}

		return $old;
	}	
}