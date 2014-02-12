<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ConfigurationController extends MyController {

	var $helpers = array('admin/admin_settings','html','form','jreviews');

	var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index() {

	    $this->name = 'configuration';
        $themes_config = $themes_fallback = array();

        $Configure = Configure::getInstance('jreviews');
        $App = S2App::getInstance('jreviews');
        $ThemeArray = $App->jreviewsPaths['Theme'];

        foreach($ThemeArray AS $theme_name=>$files)
        {
            if(!isset($themes_config[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 0*/)
                $themes_config[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if(!isset($themes_mobile[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 1*/)
                $themes_mobile[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if(!isset($themes_fallback[$theme_name]) && isset($files['.info']) && $files['.info']['fallback'] == 1)
                $themes_fallback[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if($files['.info']['mobile'] == 1) {
                $themes_config[$theme_name] = $themes_config[$theme_name] . ' -mobile';
                $themes_mobile[$theme_name] = $themes_mobile[$theme_name] . ' -mobile';
            }

            $themes_description[$theme_name] = $files['.info']['description'];
        }

        unset($ThemeArray);
        unset($App);

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'themes_config'=>$themes_config,
                'themes_mobile'=>empty($themes_mobile) ? array(''=>'No theme available') : $themes_mobile,
                'themes_fallback'=>$themes_fallback,
                'themes_description'=>$themes_description
			)
		);

        return $this->render();

	}

	function _save()
	{
		$formValues = $this->params['form'];

		$formValues['social_sharing_detail'] = Sanitize::getVar($formValues,'social_sharing_detail',array());

		if (isset($formValues['task']) && $formValues['task'] != "access")
		{
			$formValues['rss_title'] = str_replace("'",' ',$formValues['rss_title']);
			$formValues['rss_description'] = str_replace("'",' ',$formValues['rss_description']);;
		}

		// bind it to the table
		$this->Config->bindRequest($formValues);
        //Convert array settings to comma separated list

		$keys = array_keys($formValues);

        $this->Config->security_image = Sanitize::getVar($formValues,'security_image','');

		$this->Config->store();
	}
}