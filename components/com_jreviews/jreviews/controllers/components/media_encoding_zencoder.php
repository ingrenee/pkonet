<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * Encoding support for zencoder.com
 */
class MediaEncodingZencoderComponent extends S2Component {

	private $_ServiceType = 'passthru';

	private $_APIKey;

	private $_API_CREDENTIALS;

	private	$_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
		CURLOPT_SSL_VERIFYPEER => 1,
		CURLOPT_SSL_VERIFYHOST => 2
	);

	private $_FRAME_SIZE = '854x480';

	private $_BITRATE = '1200';

	private $_HEADER_CACHE_CONTROL;

	private $_HEADER_EXPIRES;

	private $_TRANSFER_METHOD = 'http';

	private $_TRANSFER_HOST;

	private $_TRANSFER_USERNAME;

	private $_TRANSFER_PASSWORD;

	private $_TRANSFER_PORT;

	private $_TRANSFER_TMP_PATH;

	/**
	 * Replacement vars: job_id, APIKey
	 * @var type
	 */
	public $_APIJobStatus = 'https://app.zencoder.com/api/jobs/%sjson?api_key=%s';

	function __construct(&$controller)
	{
		parent::__construct();

		$this->c = $controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_encode_zencoder_key');

		$this->_API_CREDENTIALS = Sanitize::getString($controller->Config,'media_encode_zencoder_credentials');

		$this->_FRAME_SIZE = Sanitize::getString($controller->Config,'media_encode_size',$this->_FRAME_SIZE);

		$this->_BITRATE = Sanitize::getString($controller->Config,'media_encode_bitrate',$this->_BITRATE);

		$this->_HEADER_CACHE_CONTROL = 'max-age=31536000'; // 1year

		$this->_HEADER_EXPIRES = gmdate("D, d M Y H:i:s T",strtotime("+1 year"));

		$this->_TRANSFER_HOST = Sanitize::getString($controller->Config,'media_encode_transfer_host');

		$this->_TRANSFER_USERNAME = Sanitize::getString($controller->Config,'media_encode_transfer_username');

		$this->_TRANSFER_PASSWORD = Sanitize::getString($controller->Config,'media_encode_transfer_password');

		if($controller->action == '_save'
			&& (($this->_TRANSFER_USERNAME != '' && $this->_TRANSFER_PASSWORD != '') || $this->_API_CREDENTIALS != '')
			&& $controller->ipaddress != '127.0.0.1')
		{

			$this->_TRANSFER_METHOD = Sanitize::getString($controller->Config,'media_encode_transfer_method',$this->_TRANSFER_METHOD);
		}

		$this->_TRANSFER_PORT = Sanitize::getString($controller->Config,'media_encode_transfer_port');

		$this->_TRANSFER_TMP_PATH = Sanitize::getString($controller->Config,'media_encode_transfer_tmp_path');

		S2App::import('Vendor','encoding'.DS.'zencoder'.DS.'zencoder');

//		$zencoder = new Services_Zencoder($this->_APIKey);
//		prx($zencoder);
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
		// Load the encoding service API wrapper
		S2App::import('Vendor','encoding'.DS.'zencoder'.DS.'zencoder');

		switch($media['media_type'])
		{
			case 'video':

				$out = $this->encodeVideo($media,$options);

				break;

			case 'audio':

				$out = $this->encodeAudio($media,$options);

				break;
		}

		return $out;
	}

	function encodeAudio($media, $options)
	{
		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		$base_path = $this->c->MediaStorage->getStorageUrl($media_type);

		$settings = array();

		switch($this->_TRANSFER_METHOD) {

			case 'http':
				// No need to do anything
				break;

			case 'ftp':
			case 'sftp':

				if($this->_API_CREDENTIALS) {

					$settings['credentials'] = $this->_API_CREDENTIALS;

					$object_url =  $this->_TRANSFER_METHOD
										. "://"
										. $this->_TRANSFER_HOST
										. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
										. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;
				}
				else {

					$object_url =  $this->_TRANSFER_METHOD
										. "://"
										. $this->_TRANSFER_USERNAME . ':' . $this->_TRANSFER_PASSWORD . '@'
										. $this->_TRANSFER_HOST
										. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
										. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;

				}

				break;
			default:
				break;
		}

		$settings['input'] = $object_url;

		$settings['outputs'] = array();

		$settings['outputs'][0] =array(
		  'public'=>true,
		  'url'=>$base_path . $rel_path . $filename . '.oga',
		  'headers'=>array(
		  	'Content-Type'=>'audio/ogg',
			'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
			'Expires'=>$this->_HEADER_EXPIRES
		  )
		);

		$settings['outputs'][1] =array(
		  'public'=>true,
		  'url'=>$base_path . $rel_path . $filename . '.m4a',
		  'headers'=>array(
		  	'Content-Type'=>'audio/mp4',
			'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
			'Expires'=>$this->_HEADER_EXPIRES
		  )
		);

		if($file_extension != 'mp3') {

			$settings['outputs'][2] =array(
			  'public'=>true,
			  'url'=>$base_path . $rel_path . $filename . '.mp3',
			  'headers'=>array(
			  	'Content-Type'=>'audio/mpeg',
				'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
				'Expires'=>$this->_HEADER_EXPIRES
			  )
			);
		}
		elseif($this->c->action == '_uploadUrl') {

			$settings['outputs'][2] =array(
			  'public'=>true,
			  'type'=>'transfer-only',
			  'url'=>$base_path . $rel_path . $filename . '.mp3',
			  'headers'=>array(
			  	'Content-Type'=>'audio/mpeg',
				'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
				'Expires'=>$this->_HEADER_EXPIRES
			  )
			);

		}

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {
			$settings['outputs'][1]['notifications'] = array(
				array('format'=>'json','url'=>WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/_checkJobStatus&tmpl=component&format=raw&remote=1&token='.$notify_token)
			);
		}

		try {
			$zencoder = new Services_Zencoder($this->_APIKey);

			# Execute job and store it in queue till notification arrives
			$encoding_job = $zencoder->jobs->create($settings);

			if(!isset($encoding_job->errors) || (isset($encoding_job->errors) && empty($encoding_job->errors)))
			{
				$output = array(
					'encoding_job'=>array(
						'id'=>$encoding_job->id,
						'created'=>_CURRENT_SERVER_TIME
					),
					'state'=>'processing',
					'media_info'=>array('audio'=>array('m4a'=>array(),'oga'=>array(),'mp3'=>array()))
				);

				return $output;
			}
		}
		catch(Exception $e) {

			return false;
		}
	}

	function encodeVideo($media, $options)
	{
		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		$base_path = $this->c->MediaStorage->getStorageUrl($media_type);

		$settings = array();

		switch($this->_TRANSFER_METHOD) {

			case 'http':
				// No need to do anything
				break;

			case 'ftp':
			case 'sftp':

				if($this->_API_CREDENTIALS) {

					$settings['credentials'] = $this->_API_CREDENTIALS;

					$object_url =  $this->_TRANSFER_METHOD
										. "://"
										. $this->_TRANSFER_HOST
										. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
										. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;
				}
				else {

					$object_url =  $this->_TRANSFER_METHOD
										. "://"
										. $this->_TRANSFER_USERNAME . ':' . $this->_TRANSFER_PASSWORD . '@'
										. $this->_TRANSFER_HOST
										. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
										. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;

				}

				break;
			default:
				break;
		}

		$settings['input'] = $object_url;

		$settings['outputs'] = array();

//		if($file_extension != 'mp4') {
			$settings['outputs'][0] =array(
			  'public'=>true,
			  'url'=>$base_path . $rel_path . $filename . '.mp4',
			  'video_bitrate'=>$this->_BITRATE,
			  'size'=>$this->_FRAME_SIZE,
			  'aspect_mode'=>'pad',
			  'headers'=>array(
			  	'Content-Type'=>'video/mp4',
				'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
				'Expires'=>$this->_HEADER_EXPIRES
			  )
			);
//		}

//		if($file_extension != 'webm') {
			$settings['outputs'][1] =array(
			  'public'=>true,
			  'url'=>$base_path . $rel_path . $filename . '.webm',
			  'video_bitrate'=>$this->_BITRATE,
			  'size'=>$this->_FRAME_SIZE,
			  'aspect_mode'=>'pad',
			  'headers'=>array(
			  	'Content-Type'=>'video/webm',
				'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
				'Expires'=>$this->_HEADER_EXPIRES
			  ),
			  'thumbnails'=>array(
				  'base_url'=>$base_path . $rel_path,
				  'format'=>$options['thumb_format'],
				  'filename'=>$filename,
				  'public'=>true,
				  'number'=>1,
				  'size'=>$this->_FRAME_SIZE,
				  'aspect_mode'=>'pad',
				  'headers'=>array(
				  	'Content-Type'=>'image/jpeg',
					'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
					'Expires'=>$this->_HEADER_EXPIRES
				  )
			  )
			);
//		}

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {
			$settings['outputs'][1]['notifications'] = array(
				array('format'=>'json','url'=>WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&token='.$notify_token)
			);
		}

		try {

			$zencoder = new Services_Zencoder($this->_APIKey);

			# Execute job and store it in queue till notification arrives
			$encoding_job = $zencoder->jobs->create($settings);

			if(!isset($encoding_job->errors) || (isset($encoding_job->errors) && empty($encoding_job->errors)))
			{
				$output = array(
					'encoding_job'=>array(
						'id'=>$encoding_job->id,
						'created'=>_CURRENT_SERVER_TIME
					),
					'state'=>'processing',
					'media_info'=>array('video'=>array('mp4'=>array(),'webm'=>array()),'image'=>$options['thumb_format'])
				);

				return $output;
			}
		}
		catch(Exception $e) {

			return false;
		}
	}

	function checkStatus($job_id)
	{

		$media_info = array();

		// Initialize session
		$ch = curl_init(sprintf($this->_APIJobStatus,$job_id,$this->_APIKey));

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		// Execute session and store returned results
		$out = curl_exec($ch);

		$out = json_decode($out,true);

		curl_close($ch);

		return $this->processNotification($out);
	}

	function processNotification($out = array())
	{
		$duration = 0;

		$state = $thumb_url = '';

		$media_info = array();

		if(empty($out)) {
			$out = json_decode(trim(file_get_contents('php://input')), true);
		}

		if(!isset($out['output']))
		{
			// This is a localhost check because only remotely posted notifications have the output key

			if($out['job']['state'] == 'finished')
			{

				if(isset($out['job']['thumbnails'][0])) {

					$thumb_url =  $out['job']['thumbnails'][0]['url'];

					$media_info['image'] = array(
						'format'=>$out['job']['thumbnails'][0]['format'],
						'height'=>$out['job']['thumbnails'][0]['height'],
						'width'=>$out['job']['thumbnails'][0]['width']
					);

				}

				$state = 'finished';

				foreach($out['job']['output_media_files'] AS $media)
				{
					// Video
					if($media['video_codec'] != '')
					{
						$format  = $media['format'] == 'mpeg4' ? 'mp4' : $media['format'];

						$media_info['video'][$format] = array(
							'format'=>$format,
							'height'=>$media['height'],
							'width' =>$media['width']
						);
					}
					// Audio
					else {

						$media_info['audio']['m4a'] = array(
							'format'=>'m4a'
						);

						$media_info['audio']['mp3'] = array(
							'format'=>'mp3'
						);

						$media_info['audio']['oga'] = array(
							'format'=>'oga'
						);
					}

					$duration = $media['duration_in_ms'];
				}
			}
			elseif ($out['job']['state'] == 'processing' || $out['job']['state'] == 'waiting') {
				$state = 'processing';
			}
			else {
				$state = 'failed';
			}

		}
		else {

			// Remotely posted notification

			if(isset($out['output']))
			{

				if(isset($out['output']['thumbnails'][0])) {

					$thumb_url =  $out['output']['thumbnails'][0]['images'][0]['url'];

					$dimensions = explode('x',$out['output']['thumbnails'][0]['images'][0]['dimensions']);

					$media_info['image'] = array(
						'format'=>'jpg',
						'width'=>$dimensions[0],
						'height'=>$dimensions[1]
					);

				}

				if($out['output']['state'] == 'finished')
				{
					$state = 'finished';

					// Video
					if($out['output']['video_codec'] != '')
					{
						$media_info['video']['mp4'] = array(
							'format'=>'mp4',
							'height'=>$out['output']['height'],
							'width' =>$out['output']['width']
						);

						$media_info['video']['webm'] = array(
							'format'=>'webm',
							'height'=>$out['output']['height'],
							'width' =>$out['output']['width']
						);

					}
					// Audio
					else {

						$media_info['audio']['oga'] = array(
							'format'=>'oga'
						);

						$media_info['audio']['mp3'] = array(
							'format'=>'mp3'
						);

						$media_info['audio']['m4a'] = array(
							'format'=>'m4a'
						);
					}

					$duration = $out['output']['duration_in_ms'];
				}
				elseif ($out['output']['state'] == 'processing' || $out['output']['state'] == 'waiting') {
					$state = 'processing';
				}
				else {
					$state = 'failed';
				}
			}
			else {
				$state = 'processing';
			}

		}

		return array(
			'job_id'=>$out['job']['id'],
			'media'=>array(
				'duration'=>round($duration/1000,0)
			),
			'thumb_url'=>$thumb_url,
			'state'=>$state,
			'media_info'=>$media_info,
			'response'=>$out
		);
	}
}