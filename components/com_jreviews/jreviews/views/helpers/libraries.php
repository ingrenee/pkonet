<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class LibrariesHelper extends MyHelper
{
	function js()
	{
		$javascriptLibs = array();
		$javascriptLibs['jquery']               =   'jquery/jquery-1.10.2.min';
		$javascriptLibs['jq.ui']          		=   'jquery/jquery-ui-1.9.2.custom.min';
		$javascriptLibs['jq.json']			    = 	'jquery/json.min';
		$javascriptLibs['jq.jsoncookie']		= 	'jquery/jquery.extjasoncookie-0.2';
		$javascriptLibs['jq.rating']			= 	'jquery/ui.stars';
		$javascriptLibs['jq.scrollable']        =   'jquery/jquery.bxslider.min';
		$javascriptLibs['jq.fancybox']          =   'jquery/jquery.magnific-popup.min';
		$javascriptLibs['jq.treeview']			= 	'jquery/jquery.treeview';
		$javascriptLibs['jq.multiselect']		= 	'jquery/jquery.multiselect.min';
		$javascriptLibs['jq.uploader'] 			= 	'fileuploader/fileuploader';
		$javascriptLibs['jq.galleria'] 			= 	'galleria/galleria-1.2.9.min';
		$javascriptLibs['jq.galleria.classic'] 	= 	'galleria/galleria.classic';
		$javascriptLibs['jq.video']				= 	'video-js/video';
		$javascriptLibs['jq.audio']				= 	'jplayer/jquery.jplayer.min';
		$javascriptLibs['jq.audio.playlist']	= 	'jplayer/jplayer.playlist.min';
		$javascriptLibs['jreviews'] 			=	'jreviews';
		$javascriptLibs['media'] 				= 	'jreviews.media';
		$javascriptLibs['fields'] 				= 	'jreviews.fields';
		$javascriptLibs['compare'] 				= 	'jreviews.compare';
		$javascriptLibs['geomaps'] 				= 	'geomaps';

		if(!isset($this->Config) || empty($this->Config))
		{
			$this->Config = Configure::read('JreviewsSystem.Config');
		}

		// if(Sanitize::getBool($this->Config,'libraries_jquery') && !defined('MVC_FRAMEWORK_ADMIN'))
		// {
		// 	unset($javascriptLibs['jquery']);
		// }

		// if(Sanitize::getBool($this->Config,'libraries_jqueryui') && !defined('MVC_FRAMEWORK_ADMIN'))
		// {
		// 	unset($javascriptLibs['jq.ui']);
		// }

		return $javascriptLibs;
	}

	function css()
	{
		$styleSheets = array();
		$styleSheets['modules']                 =   'modules';
		$styleSheets['theme']				 	= 	'theme';
		$styleSheets['theme.list']		 		= 	'list';
		$styleSheets['theme.detail']		 	= 	'detail';
		$styleSheets['theme.form']		 		= 	'form';
		$styleSheets['jq.ui']              		=   'jquery_ui_theme/jquery-ui-1.9.2.custom';
		$styleSheets['jq.fancybox']             =   'magnific/magnific-popup';
		$styleSheets['jq.treeview'] 			= 	'treeview/jquery.treeview';
		$styleSheets['jq.galleria'] 			= 	'galleria/galleria.classic';
		$styleSheets['jq.video']				= 	'video-js/video-js.min';

		if(!isset($this->Config) || empty($this->Config))
		{
			$this->Config = Configure::read('JreviewsSystem.Config');
		}

		if(Sanitize::getBool($this->Config,'libraries_jqueryui') && !defined('MVC_FRAMEWORK_ADMIN'))
		{
			unset($styleSheets['jq.ui']);
		}

		return $styleSheets;
	}
}