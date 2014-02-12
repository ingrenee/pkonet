<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );


class MediaEncodingFacebookComponent extends S2Component {

	private $_ServiceType = 'hosted';

	private $_APIKey = 'a05a9d6b671b9f0296eae11623e481e8';

	private	$_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
		CURLOPT_SSL_VERIFYPEER => 1,
		CURLOPT_SSL_VERIFYHOST => 2
	);

	private $c;

	public $_API_Upload = 'https://graph-video.facebook.com/%s/videos?title=%s&description=%s&access_token=%s';

	/**
	 * Replacement vars: job_id, APIKey
	 * @var type
	 */
	public $_APIJobStatus = 'https://app.zencoder.com/api/jobs/%sjson?api_key=%s';

	function __construct(&$controller)
	{
		parent::__construct();

		$this->c = $controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_zencoder_api_key');
	}

	public function serviceType() {
		return $this->_ServiceType;
	}

	/**
	 * @param type $input local file path
	 * @param type $target remote file name
	 * @return type remote path
	 */
	function startJob($media, $options)
	{
		extract($media);

		$page_id = trim(Sanitize::getString($this->c->Config,'facebook_appid'));

		# Need to have a way to generate the access token for the App from the admin side
		# publish_stream, manage_pages, offline_access....
		# https://developers.facebook.com/tools/explorer/131980426822970/?method=GET&path=100001249843392
		$access_token = '';

		$video_title = rawurlencode("TITLE FOR THE VIDEO");

		$video_desc = rawurlencode("DESCRIPTION-FOR-THE-VIDEO");

		// Using the page access token from above, create the POST action
		// that our form will use to upload the video.
		$post_url = sprintf($this->_API_Upload,$page_id,$video_title,$video_desc,$access_token);

		$path = realpath($tmp_path);

		$ch = curl_init();

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		$data = array('name' => 'file', 'file' => '@'.$path);// use realpath

		curl_setopt($ch, CURLOPT_URL, $post_url);

		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$res = curl_exec($ch);

		/*
		if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
			curl_setopt($ch, CURLOPT_CAINFO,
					  dirname(__FILE__) . '/src/fb_ca_chain_bundle.crt'); // path to the certificate
			$res = curl_exec($ch);
		}
		*/

		curl_close($ch);

		if( $res === false ) {
			return false;
		}

		$video = json_decode($res,true);

		if(!empty($video))
		{
			$output = array(
				'encoding_job'=>array(
					'id'=>$video['id'],
					'created'=>_CURRENT_SERVER_TIME
				),
				'state'=>'processing',
				'media_info'=>array()
			);

			return $output;
		}

	}

	function checkStatus($vid, $media_id)
	{
		$duration = 0;

		$err = false;

		$media_info = $image_attr['url'] = array();

		$thumb_url = $rel_path = '';

		$app_id = trim(Sanitize::getString($this->c->Config,'facebook_appid'));

		$secret = trim(Sanitize::getString($this->c->Config,'facebook_secret'));

		S2App::import('Vendor','facebook' . DS . 'facebook');

		$facebook = new Facebook( array(
            'appId'   => $app_id,
            'secret'  => $secret,
            'cookie'  => true
        ));

		try {
			# http://developers.facebook.com/docs/reference/fql/video/
		   $fql = "SELECT vid, length, owner, title, description, thumbnail_link, embed_html, updated_time, created_time FROM video WHERE vid =" . $vid;

			$param  =   array(
				'method'    => 'fql.query',
				'query'     => $fql,
				'callback'  => ''
			);

			$out = $facebook->api($param);
		}
		catch(Exception $o){
			$err = true;
		}

		if($err == false && !empty($out))
		{
			$out = reset($out);

			$state = 'finished';

			$duration = (int) $out['length']*1000; // Convert to ms

			$thumb_url = $out['thumbnail_link'];

			$rel_path = MEDIA_ORIGINAL_FOLDER . DS . MediaStorageComponent::getFolderHash($vid);

			$media_info_tmp = array(
				'media_type'=>'video',
				'rel_path'=>$rel_path,
				'filename'=>$vid,
				'file_extension'=>'jpg'
			);

			$image_attr = $this->c->_grabRemoteFile($media_info_tmp, $thumb_url, PATH_ROOT . 'tmp');

			$media_info = array(
				'image'=>array(
					'format'=>'jpg',
					'height'=>$image_attr['height'],
					'width'=>$image_attr['width']
				)
			);
		}
		else {
			$state = 'processing';
		}

		$url = isset($image_attr['url']) ? $image_attr['url'] : '';

		return array(
			'media'=>array(
				'filename'=>$vid,
				'rel_path'=>$rel_path,
				'embed'=>'facebook',
				'duration'=>$duration
			),
			'thumb_url'=>$url,
			'state'=>$state,
			'media_info'=>$media_info,
		);
	}

}