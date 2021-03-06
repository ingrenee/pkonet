<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageVimeoComponent extends S2Component {

	public $service = 'Vimeo.com';

	public $_API_Video = 'http://vimeo.com/api/v2/video/%s.json';

	private $_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
		CURLOPT_SSL_VERIFYPEER => 1,
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
		return 'http://player.vimeo.com/video/'.$object;
	}

	static function getVideoId($str)
	{
		$matches = array();

		// Video page
		preg_match("/vimeo\.com\/([0-9]+)/i",$str,$matches);

		if(!$matches[1]) {
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

		$filename = $video_id . '-v' . time();

		if(!empty($response))
		{
			// Build response array to return it back to the controller
			// Add media to database
			$out = array(
				'service'  =>$this->service,
				'thumb_url' =>$response[0]['thumbnail_large'],
				'Media'=>array(
					'filename' => $filename,
					'rel_path' => MEDIA_ORIGINAL_FOLDER . DS . MediaStorageComponent::getFolderHash($filename),
					'media_type'=> 'video',
					'title'=>$response[0]['title'],
					'description'=>$response[0]['description'],
					'embed'=>'vimeo',
					'duration'=>$response[0]['duration']
				)
			);

			return $out;
		}

		return false;

	}

	static function displayEmbed($video_id, $size, $options = array())
	{
		$video_id = preg_replace('/-v[0-9]+$/','',$video_id);

		$src = "//player.vimeo.com/video/$video_id?title=0&amp;byline=0&amp;portrait=0&amp;wmode=opaque";

		if(Sanitize::getBool($options,'return_attr'))
		{
			$attr = array(
				'width'=>$size[0],
				'height'=>$size[1],
				'src'=>$src,
				'frameborder'=>0,
				'webkitAllowFullScreen'=>'',
				'mozallowfullscreen'=>'',
				'allowFullscreen'=>''
			);

			return htmlspecialchars(json_encode($attr),ENT_QUOTES);
		}

		echo '<iframe src="'.$src.'" width="'.$size[0].'" height="'.$size[1].'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>'
		;
	}

}