<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
 *
 * This is the default display for custom fields
 **/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

jimport('joomla.html.editor');

class EditorHelper extends MyHelper
{
	function load($inline = false)
    {
		$config = JFactory::getConfig();

		$editor = $config->get('config.editor') != '' ? $config->get('config.editor') : $config->get('editor');

		if($editor == 'jckeditor') $editor = 'tinymce';

		if (in_array(strtolower($editor), array('tinymce', 'jce'))) {

    		$method = new ReflectionMethod( 'JEditor::getInstance' );

    		if($method->isStatic()) {

    			JEditorJReviews::getInstance($editor)->_loadEditor();
    		}
    		else {

				$JEditor = new JEditorJReviews();

				$JEditor->getInstance($editor)->_loadEditor();
    		}
		}
	}
}

try {
    $method = new ReflectionMethod( 'JEditor::getInstance' );

	if ($method->isStatic())
    {
		// J2.5
		class JEditorJReviews extends JEditor {

			public static function getInstance($editor = 'none')
			{
				static $instances;

				if (!isset ($instances)) {
					$instances = array ();
				}

				$signature = serialize($editor);

				if (empty ($instances[$signature])) {
					$instances[$signature] = new JEditorJReviews($editor);
				}

				return $instances[$signature];
			}

			public function _loadEditor($config = array()) {

				return parent::_loadEditor($config);
			}

		}
	}

	else {

		// J1.5
		class JEditorJReviews extends JEditor {

			public function getInstance($editor = 'none')
			{

				if (!isset ($instances)) {
					$instances = array ();
				}

				$signature = serialize($editor);

				if (empty ($instances[$signature])) {
					$instances[$signature] = new JEditorJReviews($editor);
				}

				return $instances[$signature];
			}

			public function _loadEditor($config = array()) {
				return parent::_loadEditor($config);
			}

		}

	}
}
catch ( ReflectionException $e )
{
    //  method does not exist
//    echo $e->getMessage();
}

