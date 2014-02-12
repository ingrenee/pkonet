<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageLocalComponent extends S2Component {

	public $name = 'local';

	public $_MEDIA_TYPE_FOLDERS;

	var $c;

	function __construct(&$controller)
	{
		$this->c = &$controller;

		parent::__construct();
	}


	function getBasePath($media_type)
	{
		return $this->getStorageUrl($media_type);
	}

	/**
	 * Returns the remote url for the media
	 * @param type $media_type
	 * @param type $object relative path to object
	 * @return type
	 */
	function getStorageUrl($media_type, $object = '', $options = array())
	{
		return WWW_ROOT . MEDIA_LOCAL_PATH . $this->getFolderName($media_type) . _DS . $object;
	}

	function getStoragePath($url)
	{
		$path = str_replace(_DS,DS,str_replace(WWW_ROOT,PATH_ROOT,$url));
		return $path;
	}

	function getFolderName($media_type)
	{
		return $this->_MEDIA_TYPE_FOLDERS[$media_type];
	}

	/**
	 * Deliver file to browser for download
	 * @param type $media
	 * @return type
	 */
	public function download($media)
	{
		$filepath = $media['Media']['media_path'] . '.' . $media['Media']['file_extension'];

		$filepath = str_replace(WWW_ROOT, PATH_ROOT, $filepath);

		$filename = $media['Media']['filename'] . '.' . $media['Media']['file_extension'];

		$parts = pathinfo($filename);

		$name = explode('-',$parts['filename']);

		if(count($name) > 1 && is_numeric($name[count($name)-1])) {

			array_pop($name);
		}

		$filename = implode('-',$name) . '.' . $parts['extension'];

		switch($media['Media']['file_extension']) {
			case 'pdf':
				$filetype = 'pdf';
				break;
			case 'zip':
				$filetype = 'zip';
				break;
			default:
				$filetype = 'octet-stream';
				break;
		}

		if (file_exists($filepath))
		{
			if(false !== ($file = fopen($filepath, 'r')))
			{
				if(ini_get('zlib.output_compression'))
				{
					ini_set('zlib.output_compression', 'Off');
				}

				$size = filesize($filepath);

				while (ob_get_level()) {
					 @ob_end_clean();
				}

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
				header('Content-Transfer-Encoding: chunked'); //changed to chunked
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header("Content-Length: ".$size);

				//Send the content in chunks
				if(isset($_SERVER['HTTP_RANGE']))
				{
					fseek($file, 0);
				}

				while(!feof($file) && false !== ($chunk = fread($file,4096)))
				{
					echo $chunk;
					flush();
				}

				fclose($file);

				die();
			}
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
		extract($media_info); // media_type, tmp_path, rel_path, filename, extension

		// Creates new folders on demand
		$path = PATH_ROOT . MEDIA_LOCAL_PATH . $this->getFolderName($media_type) . DS . $rel_path;

		$path = str_replace(_DS,DS,$path);

		$object = $path . $filename . '.' . $file_extension;

		$Folder = new S2Folder($path, true, 0755);

		if(!isset($thumbnailer) /* only when generating new thumbs */ && is_file($tmp_path) && $deleteInput)
		{
			rename($tmp_path, $object);

			is_file($tmp_path) and @unlink($tmp_path); // Remove temporary file
		}
		else {

			if($this->c->action != 'generateThumb' && is_file($tmp_path)) {

				copy($tmp_path, $object);
			}
			else {

				$tn_path = str_replace(MEDIA_ORIGINAL_FOLDER, MEDIA_THUMBNAIL_FOLDER, $path);

				$tn_file = $tn_path . $filename . '.' . $file_extension;

				$File = new S2File($tn_file);

				$File->write($tmp_path);

				$File->close();
			}
		}

		return $this->getStorageUrl($media_type, $rel_path . $filename . '.' . $file_extension);
	}

	public function delete($url, $media = array())
	{
		$filepath = $this->getStoragePath($url);

		if(file_exists($filepath)) {
			return !@unlink($filepath);
		}
		else {
			return false;
		}
	}

}