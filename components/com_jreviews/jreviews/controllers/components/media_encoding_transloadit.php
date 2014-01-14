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
 * Encoding support for zencoder.com
 */
class MediaEncodingTransloaditComponent extends S2Component {

	private $_ServiceType = 'passthru'; // Uploads video from the server

	private $_APIKey;

	private $_APISecret;

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

	/**
	 * Replacement vars: job_id, APIKey
	 * @var type
	 */
	public $_APIJobStatus = 'https://app.zencoder.com/api/jobs/%sjson?api_key=%s';

	function __construct(&$controller)
	{
		parent::__construct();

		$this->c = &$controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_encode_transloadit_key');

		$this->_APISecret = Sanitize::getString($controller->Config,'media_encode_transloadit_secret');

		$this->_FRAME_SIZE = Sanitize::getString($controller->Config,'media_encode_size',$this->_FRAME_SIZE);

		$this->_BITRATE = Sanitize::getString($controller->Config,'media_encode_bitrate',$this->_BITRATE);

		$this->_HEADER_CACHE_CONTROL = 'max-age=31536000'; // 1year

		$this->_HEADER_EXPIRES = gmdate("D, d M Y H:i:s T",strtotime("+1 year"));
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
		S2App::import('Vendor','encoding'.DS.'transloadit'.DS.'transloadit');

		$transloadit = new Transloadit(
			array(
			  'key' => $this->_APIKey,
			  'secret' => $this->_APISecret
			)
		);

		switch($media['media_type'])
		{
			case 'video':

				$assembly = $this->createVideoAssemblyS3($media);

				$media_info = array('video'=>array('mp4'=>array(),'webm'=>array()),'image'=>$options['thumb_format']);

				break;

			case 'audio':

				$assembly = $this->createAudioAssemblyS3($media);

				$media_info = array('audio'=>array('m4a'=>array(),'oga'=>array(),'mp3'=>array()));

				break;
		}

		$encoding_job = $transloadit->createAssembly($assembly);

		if(isset($encoding_job->data['ok']) && $encoding_job->data['ok'] == 'ASSEMBLY_EXECUTING')
		{
			$output = array(
				'encoding_job'=>array(
					'id'=>$encoding_job->data['assembly_url'],
					'created'=>_CURRENT_SERVER_TIME
				),
				'state'=>'processing',
				'media_info'=>$media_info
			);

			return $output;
		}

		return false;
	}

	function checkStatus($job_id)
	{
		$duration = 0;

		$media_info = array();

		$thumb_url = '';

		// Initialize session
		$ch = curl_init($job_id);

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
		$state = $thumb_url = '';

		$media_info = array();

		$duration = 0;

		if(empty($out) && isset($_POST['transloadit']))
		{
			$out = $_POST['transloadit'];
			if (ini_get('magic_quotes_gpc') === '1') {
				$out = stripslashes($out);
			}
			$out = json_decode($out,true);
		}

		$status = Sanitize::getString($out,'ok');

		$job_id = Sanitize::getString($out,'assembly_url');

		if($status == 'ASSEMBLY_COMPLETED')
		{
			$state = 'finished';

			// Video
			if(isset($out['results']['webm_encoding']))
			{

				$duration = $out['results']['webm_encoding'][0]['meta']['duration']; // In seconds

				$thumb_url = $out['results']['extracted_thumbs'][0]['url'];

				$frame = explode('x',$this->_FRAME_SIZE);

				$media_info = array(
					'image'=>array(
						'format'=>$out['results']['extracted_thumbs'][0]['ext'],
						'height'=>$out['results']['extracted_thumbs'][0]['meta']['height'],
						'width'=>$out['results']['extracted_thumbs'][0]['meta']['width']
					)
				);

				$media_info['video']['webm'] = array(
					'format'=>'webm',
					'width' =>$frame[0],
					'height'=>$frame[1]
				);


				$media_info['video']['mp4'] = array(
					'format'=>'mp4',
					'width' =>$frame[0],
					'height'=>$frame[1]
				);
			}

			// Audio
			elseif(isset($out['results']['oga_encoding'])) {

				$duration = $out['results']['oga_encoding'][0]['meta']['duration']; // In seconds

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
			else {

				$state = 'failed';
			}
		}
		elseif($status == 'ASSEMBLY_EXECUTING') {

			$state = 'processing';
		}
		else {

			$state = 'failed';
		}

		return array(
			'job_id'=>$job_id,
			'media'=>array(
				'duration'=>(int)$duration
			),
			'thumb_url'=>$thumb_url,
			'state'=>$state,
			'media_info'=>$media_info,
			'response'=>$out
		);
	}

	function createAudioAssemblyS3($media)
	{
		$S3_APIKey = Sanitize::getString($this->c->Config,'media_store_amazons3_key');

		$S3_APISecret = Sanitize::getString($this->c->Config,'media_store_amazons3_secret');

		$S3_Bucket = Sanitize::getString($this->c->Config,'media_store_amazons3_audio');

		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		$assembly = array(
//			'files' => array($input),
			'params' => array(
				'steps' => array(
					'import' => array(
						'robot' => '/http/import',
						'url' => $object_url
					),
					'oga_encoding' => array(
						'use'=>'import',
						'robot' => '/audio/encode',
						"ffmpeg"=> array(
						  "acodec"=> "libvorbis",
						  "f"=> "ogg"
						)
					),
					'store_oga' => array(
						'robot' => '/s3/store',
						'use' => 'oga_encoding',
						'path' => $rel_path . $filename . '.oga', //'.${file.ext}',
						'key' => $S3_APIKey,
						'secret' => $S3_APISecret,
						'bucket' => $S3_Bucket,
						'headers'=>array(
							'Content-Type'=>'audio/ogg',
							'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
							'Expires'=>$this->_HEADER_EXPIRES
							)
					),
					// 'mp3_encoding' => array(
					// 	'use'=>'import',
					// 	'robot' => '/audio/encode',
					// 	'preset'=> 'mp3'
					// ),
					// 'store_mp3' => array(
					// 	'robot' => '/s3/store',
					// 	'use' => 'mp3_encoding',
					// 	'path' => $rel_path . $filename . '.mp3', //'.${file.ext}',
					// 	'key' => $S3_APIKey,
					// 	'secret' => $S3_APISecret,
					// 	'bucket' => $S3_Bucket,
					// 	'headers'=>array(
					// 		'Content-Type'=>'audio/mpeg'
					// 	)
					// ),
					'm4a_encoding' => array(
						'use'=>'import',
						'robot' => '/audio/encode',
						"ffmpeg"=> array(
						  "acodec"=> "libfaac",
						  "f"=> "ipod"
						)
					),
					'store_m4a' => array(
						'robot' => '/s3/store',
						'use' => 'm4a_encoding',
						'path' => $rel_path . $filename . '.m4a', //'.${file.ext}',
						'key' => $S3_APIKey,
						'secret' => $S3_APISecret,
						'bucket' => $S3_Bucket,
						'headers'=>array(
							'Content-Type'=>'audio/mp4',
							'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
							'Expires'=>$this->_HEADER_EXPIRES
						)
					)
				),
				'notify_url'=>WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&token='.$notify_token
			)
		);

		if($file_extension != 'mp3') {

			$assembly['params']['steps']['mp3_encoding'] = array(
				'use'=>'import',
				'robot' => '/audio/encode',
				'preset'=> 'mp3'
			);

			$assembly['params']['steps']['store_mp3'] = array(
				'robot' => '/s3/store',
				'use' => 'mp3_encoding',
				'path' => $rel_path . $filename . '.mp3', //'.${file.ext}',
				'key' => $S3_APIKey,
				'secret' => $S3_APISecret,
				'bucket' => $S3_Bucket,
				'headers'=>array(
					'Content-Type'=>'audio/mpeg',
					'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
					'Expires'=>$this->_HEADER_EXPIRES
				)
			);
		}
		elseif($this->c->action == '_uploadUrl') {

			$assembly['params']['steps']['store_mp3'] = array(
				'robot' => '/s3/store',
				'use' => 'import',
				'path' => $rel_path . $filename . '.mp3', //'.${file.ext}',
				'key' => $S3_APIKey,
				'secret' => $S3_APISecret,
				'bucket' => $S3_Bucket,
				'headers'=>array(
					'Content-Type'=>'audio/mpeg',
					'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
					'Expires'=>$this->_HEADER_EXPIRES
				)
			);
		}

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {

			$assembly['params']['notify_url'] = WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&media_id='.$media_id.'&token='.$notify_token;
		}

		return $assembly;
	}

	function createVideoAssemblyS3($media)
	{
		$S3_APIKey = Sanitize::getString($this->c->Config,'media_store_amazons3_key');

		$S3_APISecret = Sanitize::getString($this->c->Config,'media_store_amazons3_secret');

		$S3_Bucket = Sanitize::getString($this->c->Config,'media_store_amazons3_video');

		extract($media);

		$notify_token = cmsFramework::getCustomToken($media_id);

		$frame = explode('x',$this->_FRAME_SIZE);
		$frame[0] = (int)$frame[0];
		$frame[1] = (int)$frame[1];

		$assembly = array(
//			'files' => array($input),
			'params' => array(
				'steps' => array(
					'import' => array(
						'robot' => '/http/import',
						'url' => $object_url
					),
					'mp4_encoding' => array(
						'use'=>'import',
						'robot' => '/video/encode',
						'preset' => 'ipad-high',
						'ffmpeg' => array('b'=>$this->_BITRATE.'k'),
						'width' => $frame[0],
						'height' => $frame[1],
						'realtime' => true
					),
					'store_mp4' => array(
						'robot' => '/s3/store',
						'use' => 'mp4_encoding',
						'path' => $rel_path . $filename . '.${file.ext}',
						'key' => $S3_APIKey,
						'secret' => $S3_APISecret,
						'bucket' => $S3_Bucket,
						'headers'=>array(
							'Content-Type'=>'video/mp4',
							'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
							'Expires'=>$this->_HEADER_EXPIRES
							)
					),
					'webm_encoding' => array(
						'use'=>'import',
						'robot' => '/video/encode',
						'preset' => 'webm',
						'ffmpeg' => array('b'=>$this->_BITRATE.'k'),
						'width' => $frame[0],
						'height' => $frame[1],
						'realtime' => true
					),
					'store_webm' => array(
						'robot' => '/s3/store',
						'use' => 'webm_encoding',
						'path' => $rel_path . $filename . '.${file.ext}',
						'key' => $S3_APIKey,
						'secret' => $S3_APISecret,
						'bucket' => $S3_Bucket,
						'headers'=>array(
							'Content-Type'=>'video/webm',
							'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
							'Expires'=>$this->_HEADER_EXPIRES
							)
					),
					'extracted_thumbs' => array(
						'use'=> 'mp4_encoding',
						'robot' => '/video/thumbs',
						'count' => 1,
						'format' => 'jpeg',
						'width' => $frame[0],
						'height' => $frame[1],
					),
					'store_thumbs' => array(
						'robot' => '/s3/store',
						'use' => 'extracted_thumbs',
						'path' => $rel_path . $filename . '.${file.ext}',
						'key' => $S3_APIKey,
						'secret' => $S3_APISecret,
						'bucket' => $S3_Bucket,
						// 'resize_strategy'=>'fit',
						'headers'=>array(
							'Content-Type'=>'image/jpeg',
							'Cache-Control'=>$this->_HEADER_CACHE_CONTROL,
							'Expires'=>$this->_HEADER_EXPIRES
							)
					)
				),
				'notify_url'=>WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&token='.$notify_token
			)
		);

		// Set notify url
		if($this->c->ipaddress != '127.0.0.1') {
			$assembly['params']['notify_url'] = WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/checkJobStatus&tmpl=component&format=raw&remote=1&media_id='.$media_id.'&token='.$notify_token;
		}

		return $assembly;
	}
}