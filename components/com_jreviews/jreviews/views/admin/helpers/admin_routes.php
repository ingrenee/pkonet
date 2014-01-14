<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminRoutesHelper extends MyHelper
{
	var $helpers = array('html');

	var $routes = array(
        'user'=>'index.php?option=com_users&amp;task=user.edit&amp;id=%s'
	);

	function user($title,$user_id,$attributes)
    {
        if($user_id == 0) {
            return '"'.$title.'"';
        }

		$route = $this->routes['user'];

		$url = sprintf($route,$user_id);

        $attributes['sef']=false;

        return $this->Html->link($title,$url,$attributes);

    }

	/**
	 * MEDIA ROUTES HERE
	 */
	function download($media)
	{
		extract($media);

		$m = s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));

		$session_token = cmsFramework::getToken();

		$integrity_token = cmsFramework::getCustomToken($media_id, $media_type, $filename, $created);

		return "jreviews.media.download('{$m}','{$integrity_token}','{$session_token}');return false;";
	}
}