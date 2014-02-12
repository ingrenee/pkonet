<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageS3Component extends S2Component {

	public $name = 's3';

	private $_APIKey;

	private $_APISecret;

	private $_BucketVideo;

	private $_BucketPhoto;

	private $_BucketAudio;

	private $_BucketAttachment;

	private $_BucketVideoCDN = false;

	private $_BucketPhotoCDN = false;

	private $_BucketAudioCDN = false;

	private $_BucketAttachmentCDN = false;

	private $_HEADER_CACHE_CONTROL;

	private $_HEADER_EXPIRES;

	function __construct($controller)
	{
		if (!class_exists('S3')) {
			S2App::import('Vendor','storage'.DS.'amazon'.DS.'s3');
		}

		$this->c = &$controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_store_amazons3_key');

		$this->_APISecret = Sanitize::getString($controller->Config,'media_store_amazons3_secret');

		$this->_BucketVideo = Sanitize::getString($controller->Config,'media_store_amazons3_video');

		$this->_BucketPhoto = Sanitize::getString($controller->Config,'media_store_amazons3_photo');

		$this->_BucketAudio = Sanitize::getString($controller->Config,'media_store_amazons3_audio');

		$this->_BucketAttachment = Sanitize::getString($controller->Config,'media_store_amazons3_attachment');

		$this->_BucketVideoCDN = Sanitize::getString($controller->Config,'media_store_amazons3_video_cdn');

		$this->_BucketPhotoCDN = Sanitize::getString($controller->Config,'media_store_amazons3_photo_cdn');

		$this->_BucketAudioCDN = Sanitize::getString($controller->Config,'media_store_amazons3_audio_cdn');

		$this->_BucketAttachmentCDN = Sanitize::getString($controller->Config,'media_store_amazons3_attachment_cdn');

		$this->_HEADER_CACHE_CONTROL = 'max-age=31536000'; // 1year

		$this->_HEADER_EXPIRES = gmdate("D, d M Y H:i:s T",strtotime("+1 year"));
	}

	function getBasePath($media_type)
	{
		return $this->getStorageUrl($media_type, '', array('credentials'=>true));
	}

	/**
	 * Returns the remote url for the media
	 * @param type $media_type
	 * @param type $object relative path to object
	 * @return type
	 */
	function getStorageUrl($media_type, $object = '', $options = array())
	{
		$api = '';

		$use_credentials = Sanitize::getBool($options,'credentials');

		$use_cdn_url = Sanitize::getBool($options,'cdn') && $this->useCdnUrl($media_type);

		if($use_credentials) {

			$api = rawurlencode($this->_APIKey).':'.urlencode($this->_APISecret).'@';
		}

		if($use_cdn_url) {

			$url = 'http://'.$this->getBucketName($media_type).'/'.$object;
		}
		else {

			$url = 'http://'.$api.$this->getBucketName($media_type).'.s3.amazonaws.com/'.$object;
		}

		return $url;
	}

	function getFolderName($media_type)
	{
		return '';
	}

	function getStoragePath($url)
	{
		$parts = @parse_url($url);
		return ltrim($parts['path'],'/');
	}

	function getBucketName($media_type)
	{
		if($media_type == 'video_embed') $media_type = 'video';

		return $this->{'_Bucket'.ucfirst($media_type)};
	}

	function useCdnUrl($media_type)
	{
		if($media_type == 'video_embed') $media_type = 'video';

		return $this->{'_Bucket'.ucfirst($media_type).'CDN'};
	}

	/**
	 * Deliver file to browser for download
	 * @param type $media
	 * @return type
	 */
	public function download($media)
	{
		extract($media['Media']);

		$filepath = $media['Media']['media_path'] . '.' . $media['Media']['file_extension'];

		$fname = $media['Media']['filename'] . '.' . $media['Media']['file_extension'];

		$parts = pathinfo($fname);

		$name = explode('-',$parts['filename']);

		array_pop($name);

		$public_name = implode('-',$name) . '.' . $parts['extension'];

		$s3_url = $this->getStorageUrl($media_type, $rel_path . $filename . '.' . $file_extension);

		$s3 = new S3($this->_APIKey,$this->_APISecret);

		if($object = $s3->getObject($this->getBucketName($media_type), $rel_path . $filename . '.' . $file_extension)) {

			header('Content-Type: application/force-download');

			header('Content-Disposition: attachment; filename='.$public_name.';');

			echo $object->body;

			exit;
		}

		return false;
	}

	/**
	 * @param type $input local file path
	 * @param type $target remote file name
	 * @return type remote path
	 */
	public function upload($media_info, $deleteInput = false)
	{
		$metaHeaders = $requestHeaders = array();

		extract($media_info); // media_type, tmp_path, rel_path, filename, file_extension

		$bucket = $this->getBucketName($media_type);

		$object = $rel_path . $filename . '.' . $file_extension;

		$s3 = new S3($this->_APIKey,$this->_APISecret);

		$mime_type = $this->c->MediaUpload->getMimeTypeFromExt($file_extension);

		if($mime_type) {
			$requestHeaders['Content-Type'] = $mime_type;
		}

		$rest = new S3Request('HEAD');

		$rest = $rest->getResponse();

		if(isset($rest->headers['server-time'])) {

			$requestHeaders['Date'] = $rest->headers['server-time'];
		}

		$requestHeaders['Cache-Control'] = $this->_HEADER_CACHE_CONTROL;

		$requestHeaders['Expires'] = $this->_HEADER_EXPIRES;

		$putMethod = is_file($tmp_path) ? 'putObjectFile' : 'putObject';

		// if(is_file($tmp_path)) {
		// 	$object = S3::inputFile($tmp_path);
		// }

		if($s3->{$putMethod}($tmp_path, $bucket, $object, S3::ACL_PUBLIC_READ, $metaHeaders, $requestHeaders))
		{
			$deleteInput and is_file($tmp_path) and @unlink($tmp_path); // Remove temporary file

			return $this->getStorageUrl($media_type, $object);
		}

		$deleteInput and is_file($tmp_path) and @unlink($tmp_path); // Remove temporary file

		return false;
	}

	public function delete($url, $media)
	{
		$media_type = $media['Media']['media_type'];

		$s3 = new S3($this->_APIKey,$this->_APISecret);

		return $s3->deleteObject($this->getBucketName($media_type), $this->getStoragePath($url));
	}

}