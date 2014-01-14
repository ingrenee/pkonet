<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
/*
 * Adapted from https://github.com/valums/file-uploader
 */

class MediaUploadComponent extends S2Component {

	public $_MIME_MEDIA_TYPES = array(
		'image'=>'photo',
		'video'=>'video',
		'application'=>'attachment',
		'text'=>'attachment',
		'audio'=>'audio'
	);

	public $mimeArray = array(
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
			'bmp' => 'image/bmp',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'ico' => 'image/x-icon',
			'swf' => 'application/x-shockwave-flash',
			'pdf' => 'application/pdf',
			'zip' => 'application/zip',
			'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar',
			'rar' => 'application/x-rar-compressed',
			'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2',
			'txt' => 'text/plain',
			'asc' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'css' => 'text/css',
			'js' => 'text/javascript',
			'xml' => 'text/xml',
			'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg',
			'oga' => 'audio/ogg',
			'aac' => 'audio/aac',
			'pcm' => 'audio/pcm',
			'm4a' => 'audio/mp4',
			'mp3' => 'audio/mpeg',
			'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo',
			'mp4' => 'video/mp4',
			'mpg' => 'video/mpeg',
			'mpeg'=> 'video/mpeg',
			'wmv' => 'video/x-ms-wmv',
			'mov' => 'video/quicktime',
			'flv' => 'video/x-flv',
			'php' => 'text/x-php'
		);

	public $_TRANSLATION_MEDIA_TYPES = array();

	private $allowedExtensions = array();


	private $sizeLimit = 1; // MB

	private $file;

	public $clean_filename = true;

	private $c;

	public function startup(&$controller)
	{
		$this->c = &$controller;

        # Always load JreviewsLocale class because it is used server side as well
        if(!class_exists('JreviewsLocale')) {

            require(S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'locale.php' );
        }

		$this->_TRANSLATION_MEDIA_TYPES = array(
			'photo'=>JreviewsLocale::getPHP('MEDIA_PHOTOS_LOWER'),
			'video'=>JreviewsLocale::getPHP('MEDIA_VIDEOS_LOWER'),
			'attachment'=>JreviewsLocale::getPHP('MEDIA_ATTACHMENTS_LOWER'),
			'audio'=>JreviewsLocale::getPHP('MEDIA_AUDIO_LOWER'),
			''=>JreviewsLocale::getPHP('MEDIA_GENERIC_TYPE_LOWER')
		);
	}

	public function init($from_url = false)
	{
		$photoExtensions = $this->buildExtensionArray('photo',Sanitize::getString($this->c->Config,'media_photo_extensions'));

		$videoExtensions = $this->buildExtensionArray('video',Sanitize::getString($this->c->Config,'media_video_extensions'));

		$attachmentExtensions = $this->buildExtensionArray('attachment',Sanitize::getString($this->c->Config,'media_attachment_extensions'));

		$audioExtensions = $this->buildExtensionArray('audio',Sanitize::getString($this->c->Config,'media_audio_extensions'));

		$this->allowedExtensions = array_merge($photoExtensions,$videoExtensions,$attachmentExtensions,$audioExtensions);

		$this->checkServerSettings();

		// Run only for file uploads from client
		if(!$from_url) {

			if (isset($_GET['qqfile'])) {

				$this->file = new qqUploadedFileXhr();
			}
			elseif (isset($_FILES['qqfile'])) {

				$this->file = new qqUploadedFileForm();
			}
			else {
				$this->file = false;
			}
		}
	}

	private function buildExtensionArray($media_type, $list) {

		$array = array();

		$extension = explode(',',$list);

		foreach($extension AS $ext) {
			$ext != '' and $array[$ext] = $media_type;
		}

		return $array;
	}

	public function getFileExtension()
	{
		$orig_filename = $this->file->getName();

		$pathinfo = pathinfo($orig_filename);

		return strtolower($pathinfo['extension']);
	}

	public function getFileName()
	{
		return $this->file->getName();
	}

	public function getMediaTypeFromExtension($ext)
	{
		return isset($this->allowedExtensions[$ext]) ? $this->allowedExtensions[$ext] : '';
	}

	private function checkServerSettings()
	{
		$postSize = $this->toBytes(ini_get('post_max_size'));
		$uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

		if ($postSize < $this->toBytes($this->sizeLimit.'m') || $uploadSize < $this->toBytes($this->sizeLimit.'m')){
			$size = $this->sizeLimit . 'M';
			die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
		}
	}

	public function toBytes($str)
	{
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}

	public function getMediaType($path)
	{
		$mime = $this->getMimeType($path);

		$file_type = explode('/',$mime);

		// Exception for old format flash video
		if($file_type[1] == 'x-shockwave-flash') {

			return 'video';
		}

		return $this->_MIME_MEDIA_TYPES[current($file_type)];
	}

	private function getMimeType($file_path)
	{
		$mtype = '';
		// if (function_exists('mime_content_type')){
		// 	$mtype = mime_content_type($file_path);
		// }
		// else
		if(function_exists('finfo_file') && isset($_ENV['MAGIC'])){
			$finfo = finfo_open(FILEINFO_MIME);
			$mtype = finfo_file($finfo, $file_path);
			finfo_close($finfo);
		}

		$ext = strtolower(pathInfo($file_path, PATHINFO_EXTENSION));

		// if (!empty($mtype)) {

		// 	if(in_array($ext,array('oga','m4a')) && in_array($mtype,array('video/mp4','application/ogg'))) {
		// 		return $this->mimeArray[$ext];
		// 	}
		// 	return $mtype;
		// }

		return isset($this->mimeArray[$ext]) ? $this->mimeArray[$ext] : 'application/octet-stream';
	}

	public function getMimeTypeFromExt($ext)
	{
		return Sanitize::getString($this->mimeArray,$ext);
	}

	public function setSizeLimit($media_type)
	{
		$this->sizeLimit = Sanitize::getString($this->c->Config,"media_{$media_type}_max_size");

		return $this->sizeLimit;
	}

	/**
	 * Returns array('success'=>true) or array('error'=>'error message')
	 */
	function handleUpload($uploadDirectory, $media_type, $listing = null, $replaceOldFile = false)
	{
		$response = array('success'=>false,'str'=>array());

		$orig_filename = $this->file->getName();

		if (!is_writable($uploadDirectory)){

			$response['str'][] = 'MEDIA_UPLOAD_NOT_WRITABLE';

			return $response;
		}

		if (!$this->file){

			$response['str'][] = 'MEDIA_UPLOAD_NOT_UPLOADED';

			return $response;
		}

		$size = $this->file->getSize();

		if ($size == 0) {

			$response['str'][] = array('MEDIA_UPLOAD_ZERO_SIZE',$orig_filename);

			return $response;
		}

		if ($size > $this->toBytes($this->sizeLimit.'m')) {

			$response['str'][] = array('MEDIA_UPLOAD_MAX_SIZE',$orig_filename,$this->sizeLimit);

			return $response;
		}

		$pathinfo = pathinfo($orig_filename);

		$filename = $this->clean_filename ? $this->c->MediaStorage->cleanFileName($pathinfo['filename'], $media_type, $listing) : $pathinfo['filename'];

		$extension = strtolower($pathinfo['extension']);

		$allowedExtension = array_keys($this->allowedExtensions);

		if($allowedExtension && !in_array($extension, $allowedExtension)) {

			$these = implode(', ', $allowedExtension);

			$response['str'][] = array('MEDIA_UPLOAD_INVALID_EXT_LIST',$these);
		}

		if(!$replaceOldFile) {
			/// don't overwrite previous files that were uploaded
			while (file_exists($uploadDirectory . $filename . '.' . $extension)) {
				$filename .= rand(10, 99);
			}
		}

		$uploaded_file_path = $uploadDirectory . $filename .'.'.$extension;

		if ($this->file->save($uploaded_file_path))
		{
			// Get media type
			$media_type = $this->getMediaType($uploaded_file_path);

			return array(
				'success'=>true,
				'media_type'=>$media_type,
				'filename'=>$filename,
				'filesize'=>$size,
				'file_extension'=>$extension,
				'rel_path'=>MEDIA_ORIGINAL_FOLDER . _DS . MediaStorageComponent::getFolderHash($filename,true),
				'tmp_path'=>$uploaded_file_path,
				'orig_filename'=>$orig_filename
			);
		}

		return $response;
	}
}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {
		$input = fopen("php://input", "r");
		$temp = tmpfile();
		$realSize = stream_copy_to_stream($input, $temp);
		fclose($input);

		if ($realSize != $this->getSize()){
			return false;
		}

		$target = fopen($path, "w");
		fseek($temp, 0, SEEK_SET);
		stream_copy_to_stream($temp, $target);
		fclose($target);

		return true;
	}
	function getName() {
		return $_GET['qqfile'];
	}
	function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
			return (int)$_SERVER["CONTENT_LENGTH"];
		} else {
			throw new Exception('Getting content length is not supported.');
		}
	}
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {
		if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
			return false;
		}
		return true;
	}
	function getName() {
		return $_FILES['qqfile']['name'];
	}
	function getSize() {
		return $_FILES['qqfile']['size'];
	}
}