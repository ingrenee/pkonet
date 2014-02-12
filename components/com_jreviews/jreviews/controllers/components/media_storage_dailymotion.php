<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageDailymotionComponent extends S2Component {

	public $service = 'Dailymotion.com';

	public $_API_Video = 'https://api.dailymotion.com/video/%s?fields=title,description,duration,thumbnail_large_url';

	private $_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
		CURLOPT_SSL_VERIFYPEER => 0, // Enabling certificate verification makes the curl call fail on some servers
		CURLOPT_SSL_VERIFYHOST => 2
	);

	private $c;

	function __construct(&$controller)
	{
		parent::__construct();
		$this->c = & $controller;
	}

	function getStorageUrl($media_type, $object = '')
	{
		return 'http://www.dailymotion.com/embed/video/'.$object;
	}

	static function getVideoId($str)
	{
		// Video page
		preg_match("/dailymotion\.com\/video\/([0-9a-z]*)_/i",$str,$matches);

		if(!isset($matches[1])){
			preg_match("/dailymotion\.com\/hub\/.*#video=([0-9a-z]*)/i",$str,$matches);
		}

		if(!isset($matches[1])) {
			return false;
		}

		return $matches[1];
	}

	function processEmbed($url, $listing = null)
	{
		if(!$video_id = trim(self::getVideoId($url))) {
			return false;
		}

		$url = sprintf($this->_API_Video, $video_id);

		// Initialize session
		$ch = curl_init($url);

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		// Execute session and store returned results
		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response,true);

		/*
		 * some available keys in $response['data']
		 * tags array
		 * category string
		 * duration int - seconds
		 */

		$filename = $video_id . '-v' . time();

		if(!isset($response['error']))
		{
			// Build response array to return it back to the controller
			// Add media to database
			$out = array(
				'service'  =>$this->service,
				'thumb_url' =>$response['thumbnail_large_url'],
				'Media'=>array(
					'filename' => $filename,
					'rel_path' => MEDIA_ORIGINAL_FOLDER . DS . MediaStorageComponent::getFolderHash($filename),
					'media_type'=> 'video',
					'title'=>$response['title'],
					'description'=>$response['description'],
					'embed'=>'dailymotion',
					'duration'=>$response['duration']
				)
			);

			return $out;
		}

		return false;

	}

	static function displayEmbed($video_id, $size, $options = array())
	{
		$video_id = preg_replace('/-v[0-9]+$/','',$video_id);

		$src = "//www.dailymotion.com/embed/video/$video_id?logo=0&amp;hideInfos=1&amp;wmode=opaque";

		if(Sanitize::getBool($options,'return_attr'))
		{
			$attr = array(
				'width'=>$size[0],
				'height'=>$size[1],
				'src'=>$src,
				'frameborder'=>0
			);

			return htmlspecialchars(json_encode($attr),ENT_QUOTES);
		}

		echo '<iframe src="'.$src.'" width="'.$size[0].'" height="'.$size[1].'" frameborder="0"></iframe>';

	}
}