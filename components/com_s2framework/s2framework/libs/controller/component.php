<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Component extends S2Object{

	function __initModels($models = null, $app = 'jreviews') {

		if(!empty($models)) {

			if(!empty($models)) {

				S2App::import('Model',$models,$app);

				foreach($models AS $model) {

					$method_name = inflector::camelize($model);

					$class_name = $method_name.'Model';

					$this->{$method_name} = new $class_name();

					if(isset($this->controller_name)) $this->{$method_name}->controller_name = $this->controller_name;

					if(isset($this->controller_action)) $this->{$method_name}->controller_action = $this->controller_action;
				}
			}
		}
	}

}