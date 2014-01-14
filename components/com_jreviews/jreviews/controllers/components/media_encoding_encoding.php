<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * Encoding support for encoding.com
 */
class MediaEncodingEncodingComponent extends S2Component {

	private $_ServiceType = 'passthru';

	private $_UserID;

	private $_APIKey;

	private	$_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
//		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
//		CURLOPT_SSL_VERIFYPEER => 1,
//		CURLOPT_SSL_VERIFYHOST => 2
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

	private $c;

	public $_API_Upload = 'http://manage.encoding.com/';

	/**
	 * Replacement vars: job_id, APIKey
	 * @var type
	 */
	public $_APIJobStatus = 'http://manage.encoding.com/';

	function __construct(&$controller)
	{
		parent::__construct();

		$this->c = $controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_encode_encoding_key');

		$this->_UserID = Sanitize::getString($controller->Config,'media_encode_encoding_userid');

		$this->_FRAME_SIZE = Sanitize::getString($controller->Config,'media_encode_size',$this->_FRAME_SIZE);

		$this->_BITRATE = Sanitize::getString($controller->Config,'media_encode_bitrate',$this->_BITRATE);

		$this->_HEADER_CACHE_CONTROL = 'max-age=31536000'; // 1year

		$this->_HEADER_EXPIRES = gmdate("D, d M Y H:i:s T",strtotime("+1 year"));

		$this->_TRANSFER_HOST = Sanitize::getString($controller->Config,'media_encode_transfer_host');

		$this->_TRANSFER_USERNAME = Sanitize::getString($controller->Config,'media_encode_transfer_username');

		$this->_TRANSFER_PASSWORD = Sanitize::getString($controller->Config,'media_encode_transfer_password');

		if($controller->action == '_save'
			&& $this->_TRANSFER_USERNAME != ''
			&& $this->_TRANSFER_PASSWORD
			&& $controller->ipaddress != '127.0.0.1') {

			$this->_TRANSFER_METHOD = Sanitize::getString($controller->Config,'media_encode_transfer_method',$this->_TRANSFER_METHOD);
		}

		$this->_TRANSFER_PORT = Sanitize::getString($controller->Config,'media_encode_transfer_port');

		$this->_TRANSFER_TMP_PATH = Sanitize::getString($controller->Config,'media_encode_transfer_tmp_path');
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
		switch($media['media_type'])
		{
			case 'video':

				$req = $this->createVideoXML($media);

				$media_info = array('video'=>array('mp4'=>array(),'webm'=>array()),'image'=>$options['thumb_format']);

				break;

			case 'audio':

				$req = $this->createAudioXML($media);

				$media_info = array('audio'=>array('m4a'=>array(),'oga'=>array(),'mp3'=>array()));

				break;
		}

		// Sending API request
		$ch = curl_init();

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		curl_setopt($ch, CURLOPT_URL, $this->_API_Upload);

		curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . urlencode($req->asXML()));

		curl_setopt($ch, CURLOPT_POST, 1);

		$res = curl_exec($ch);

		curl_close($ch);

		try
		{
			// Creating new object from response XML
			$response = new SimpleXMLElement($res);

			// If there are any errors, set error message
			if(isset($response->errors[0]->error[0])) {
				$error = $response->errors[0]->error[0] . '';

				return false;
			}
			elseif($response->message)
			{
				if(isset($response->message[0])) {

					$message = (string)$response->message[0];
					$job_id = (string)$response->MediaID[0];
				}
				else {

					$message = (string)$response->message;
					$job_id = (string)$response->MediaID;
				}

				if($message == 'Added')
				{
					$output = array(
						'encoding_job'=>array(
							'id'=>$job_id,
							'created'=>_CURRENT_SERVER_TIME
						),
						'state'=>'processing',
						'media_info'=>$media_info
					);

					return $output;
				}
			}

		}
		catch(Exception $e)
		{
			// If wrong XML response received
			$error = $e->getMessage();
		}

		return false;
	}

	function checkStatus($job_id)
	{
		$duration = 0;

		$media_info = array();

		$status = $thumb_url = '';

	   // Preparing XML request

		// Main fields
		$req = new SimpleXMLElement('<?xml version="1.0"?><query></query>');
		$req->addChild('userid', $this->_UserID);
		$req->addChild('userkey', $this->_APIKey);
		$req->addChild('action', 'GetStatus');
		$req->addChild('mediaid', $job_id);

		// Sending API request
		$ch = curl_init();

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		curl_setopt($ch, CURLOPT_URL, $this->_API_Upload);

		curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . urlencode($req->asXML()));

		curl_setopt($ch, CURLOPT_POST, 1);

		$res = curl_exec($ch);

		curl_close($ch);

		return $this->processNotification($res);
	}

	function getMediaInfo($media_id /* encoding.com */)
	{
		// Main fields
		$req = new SimpleXMLElement('<?xml version="1.0"?><query></query>');
		$req->addChild('userid', $this->_UserID);
		$req->addChild('userkey', $this->_APIKey);
		$req->addChild('action', 'GetMediaInfo');
		$req->addChild('mediaid', $media_id);

		// Sending API request
		$ch = curl_init();

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		curl_setopt($ch, CURLOPT_URL, $this->_API_Upload);

		curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . urlencode($req->asXML()));

		curl_setopt($ch, CURLOPT_POST, 1);

		$res = curl_exec($ch);

		curl_close($ch);

		return $res;
	}


	function processNotification($res = array())
	{
		$thumb_url = $state = '';

		$duration = 0;

		$media_info = $out = $size = array();

		if(empty($res)) {
			$res = $_POST['xml'];
			if (ini_get('magic_quotes_gpc') === '1') {
				$res = stripslashes($res);
			}
		}

		try
		{
			// Creating new object from response XML
			$out = new SimpleXMLElement($res);

			$job_id = (string) $out->mediaid;

			$remote = Sanitize::getInt($this->c->params,'remote',0);

			$status = (string)$out->status[0];

			if($remote || $status == 'Finished')
			{
				if(isset($out->id)){
					$job_id = (string) $out->id;
				}

				$res = new SimpleXMLElement($this->getMediaInfo($job_id,true));

				if(isset($res->size)) {

					$size = explode('x', (string) $res->size);
				}
				else {
					$size = explode('x',$this->_FRAME_SIZE);
				}

				$duration = (string) $res->duration;
			}

			// If there are any errors, set error message
			if(isset($out->errors))
			{
				$error = isset($out->errors[0]) ? (string) $out->errors[0]->error[0] : (string) $out->errors->error;

				$state = 'failed';

				return array(
					'job_id'=>(string)$out->mediaid,
					'media'=>array(
						'duration'=>(int)$duration
					),
					'thumb_url'=>$thumb_url,
					'state'=>$state,
					'media_info'=>array(),
					'response'=>$out
				);
			}
			else {

				if($status == 'Finished')
				{
					$state = 'finished';

					// Video
					if(!in_array((string)$out->format[0]->output,array('m4a','mp3','ogg')))
					{
						$media_info = array(
							'image'=>array(
								'format'=>'jpg',
								'width'=>$size[0],
								'height'=>$size[1]
							)
						);

						$thumb_url = (string) $out->format[2]->destination;

						$url_parts = @parse_url($thumb_url);

						// If Amazon S3 remove credentials from url....
						$thumb_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];

						$duration = (int) $duration;

						$media_info['video']['mp4'] = array(
							'format'=>'mp4',
							'width'=>$size[0],
							'height'=>$size[1]
						);

						$media_info['video']['webm'] = array(
							'format'=>'webm',
							'width'=>$size[0],
							'height'=>$size[1]
						);

					}

					// Audio
					else {

						$duration = (int) $duration;

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
				}
				else {
					$state = 'processing';
				}
			}

		}
		catch(Exception $e)
		{
			// If wrong XML response received
			$error = $e->getMessage();
		}

		return array(
			'job_id'=>(string)$job_id,
			'media'=>array(
				'duration'=>(int)$duration
			),
			'thumb_url'=>$thumb_url,
			'state'=>$state,
			'media_info'=>$media_info,
			'response'=>$out
		);

	}

	function createAudioXML($media)
	{
		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		switch($this->_TRANSFER_METHOD) {

			case 'http':
				// No need to do anything
				break;

			case 'ftp':
			case 'sftp':

//ftp://[user[:password]@]hostname[:port]/[path]/[filename][?passive=yes]

				$object_url =  $this->_TRANSFER_METHOD
									. "://"
									. urlencode($this->_TRANSFER_USERNAME) . ':' . urlencode($this->_TRANSFER_PASSWORD) . '@'
									. $this->_TRANSFER_HOST
									. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
									. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;
				break;
			default:
				break;
		}

	   // Preparing XML request

		// Main fields
		$req = new SimpleXMLElement('<?xml version="1.0"?><query></query>');
		$req->addChild('userid', $this->_UserID);
		$req->addChild('userkey', $this->_APIKey);
		$req->addChild('action', 'AddMedia');
		$req->addChild('source', $object_url);

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {
			$notify_url = 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&media_id='.$media_id.'&token='.$notify_token;
			$req->addChild('notify',WWW_ROOT . htmlspecialchars($notify_url));
		}

		$req->addChild('instant','yes');

		// Format fields oga
		$formatOgaNode = $req->addChild('format');

		$formatOgaNode->addChild('output', 'ogg');

		$destination = $base_path . $rel_path . htmlspecialchars($filename . '.oga?acl=public-read&content_type=audio/ogg');

		$formatOgaNode->addChild('destination', $destination);

		// Format fields m4a
		$formatM4aNode = $req->addChild('format');

		$formatM4aNode->addChild('output', 'm4a');

		$destination = $base_path . $rel_path . htmlspecialchars($filename . '.m4a?acl=public-read&content_type=audio/mp4');

		$formatM4aNode->addChild('destination', $destination);

		if($file_extension != 'mp3') {

			// Format fields mp3
			$formatMp3Node = $req->addChild('format');

			$formatMp3Node->addChild('output', 'mp3');

			$destination = $base_path . $rel_path . htmlspecialchars($filename . '.mp3?acl=public-read&content_type=audio/mpeg');

			$formatMp3Node->addChild('destination', $destination);
		}
		elseif($this->c->action == '_uploadUrl') {

			// Format fields mp3
			$formatMp3Node = $req->addChild('format');

			$formatMp3Node->addChild('output', 'mp3');

			$formatMp3Node->addChild('audio_codec', 'copy');

			$destination = $base_path . $rel_path . htmlspecialchars($filename . '.mp3?acl=public-read&content_type=audio/mpeg');

			$formatMp3Node->addChild('destination', $destination);
		}

		return $req;
	}


	function createVideoXML($media)
	{
		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		switch($this->_TRANSFER_METHOD) {

			case 'http':
				// No need to do anything
				break;

			case 'ftp':
			case 'sftp':

//ftp://[user[:password]@]hostname[:port]/[path]/[filename][?passive=yes]

				$object_url =  $this->_TRANSFER_METHOD
									. "://"
									. urlencode($this->_TRANSFER_USERNAME) . ':' . urlencode($this->_TRANSFER_PASSWORD) . '@'
									. $this->_TRANSFER_HOST
									. ($this->_TRANSFER_PORT != '' ? ':' . $this->_TRANSFER_PORT : '')
									. $this->_TRANSFER_TMP_PATH . "/" . $filename . '.' . $file_extension;
				break;
			default:
				break;
		}

	   // Preparing XML request

		// Main fields
		$req = new SimpleXMLElement('<?xml version="1.0"?><query></query>');
		$req->addChild('userid', $this->_UserID);
		$req->addChild('userkey', $this->_APIKey);
		$req->addChild('action', 'AddMedia');
		$req->addChild('source', $object_url);

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {
			$notify_url = 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&media_id='.$media_id.'&token='.$notify_token;
			$req->addChild('notify',WWW_ROOT . htmlspecialchars($notify_url));

		}

		$req->addChild('instant','yes');

		// Format fields MP4
		$formatMp4Node = $req->addChild('format');
		$formatMp4Node->addChild('output', 'mp4');
		$formatMp4Node->addChild('video_codec', 'libx264');
		$formatMp4Node->addChild('audio_codec', 'dolby_heaac');
		$formatMp4Node->addChild('bitrate', $this->_BITRATE.'k');
		$formatMp4Node->addChild('size', $this->_FRAME_SIZE);
		$destination = $base_path . $rel_path . htmlspecialchars($filename . '.mp4?acl=public-read&content_type=video/mp4');
		$formatMp4Node->addChild('destination', $destination);

		// Format fields Web
		$formatWebmNode = $req->addChild('format');
		$formatWebmNode->addChild('output', 'webm');
		$formatWebmNode->addChild('bitrate', $this->_BITRATE.'k');
		$formatWebmNode->addChild('size', $this->_FRAME_SIZE);
		$destination = $base_path . $rel_path . htmlspecialchars($filename . '.webm?acl=public-read&content_type=video/webm');
		$formatWebmNode->addChild('destination', $destination);

		// Format fields Thumbnail
		$frame = explode('x',$this->_FRAME_SIZE);

		$formatThumbNode = $req->addChild('format');
		$formatThumbNode->addChild('output', 'thumbnail');
		$formatThumbNode->addChild('time', '1%');
		$formatThumbNode->addChild('width', $frame[0]);
		$formatThumbNode->addChild('height', $frame[1]);
		$destination = $base_path . $rel_path . htmlspecialchars($filename . '.jpg?acl=public-read&content_type=image/jpeg');
		$formatThumbNode->addChild('destination', $destination);

		return $req;

	}

}