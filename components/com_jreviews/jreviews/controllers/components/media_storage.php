<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageComponent extends S2Component {

	private $_STORAGE_CLASSES = array();

	public $_STORAGE = array();

	/**
	 *
	 * @var type Used for LOCAL storage
	 */
	public $_MEDIA_TYPE_FOLDERS;

	function startup(&$controller)
	{
		$this->_STORAGE = array(
			'photo'=>Sanitize::getString($controller->Config,'media_store_photo','local'),
			'video'=>Sanitize::getString($controller->Config,'media_store_video','s3'),
			'video_embed'=>Sanitize::getString($controller->Config,'media_store_video_embed','local'),
			'audio'=>Sanitize::getString($controller->Config,'media_store_audio','local'),
			'attachment'=>Sanitize::getString($controller->Config,'media_store_attachment','local'),
			'youtube'=>'youtube',
			'vimeo'=>'vimeo',
			'dailymotion'=>'dailymotion'
//			,'facebook'=>'facebook'
		);

		$this->_MEDIA_TYPE_FOLDERS = array(
			'photo'=>Sanitize::getString($controller->Config,'media_store_local_photo','photos'),
			'video'=>Sanitize::getString($controller->Config,'media_store_local_video','videos'),
			'video_embed'=>Sanitize::getString($controller->Config,'media_store_local_video','videos'),
			'application'=>Sanitize::getString($controller->Config,'media_store_local_attachment','attachments'),
			'attachment'=>Sanitize::getString($controller->Config,'media_store_local_attachment','attachments'),
			'audio'=>Sanitize::getString($controller->Config,'media_store_local_audio','audio'),
		);

		$storage_services = array_unique(array_values($this->_STORAGE));

		foreach($storage_services AS $storage_service)
		{
			S2App::import('Component',array('media_storage_'.$storage_service),'jreviews');

			$class_name = inflector::camelize('media_storage_'.$storage_service.'Component');

			$this->_STORAGE_CLASSES[$storage_service] = new $class_name($controller);

			$this->_STORAGE_CLASSES[$storage_service]->_MEDIA_TYPE_FOLDERS = $this->_MEDIA_TYPE_FOLDERS;
		}

		Configure::write('Media.storage',$this);
	}

	function getBasePath($media_type)
	{
		$storage_service = $this->_STORAGE[$media_type];

		return $this->_STORAGE_CLASSES[$storage_service]->getBasePath($media_type);
	}

	function getService($media_type)
	{
		return $this->_STORAGE[$media_type];
	}

	function getStorageUrl($media_type, $object_url = '', $options = array())
	{
		// $object_url = str_replace('\\','/',$object_url);

		$storage_service = $this->_STORAGE[$media_type];

		if(isset($this->_STORAGE_CLASSES[$storage_service]) && method_exists($this->_STORAGE_CLASSES[$storage_service], 'getStorageUrl')) {

			return $this->_STORAGE_CLASSES[$storage_service]->getStorageUrl($media_type, $object_url, $options);
		}

		return false;
	}

	/**
	 * Clean name and append timestamp
	 * @param type $filename
	 */
	public function cleanFileName($filename, $media_type, $listing, $modifier = null)
	{
		$Config = Configure::read('JreviewsSystem.Config');

		$FILENAME_TITLE = array(
			'photo'=>Sanitize::getBool($Config,'media_photo_filename_title'),
			'video'=>Sanitize::getBool($Config,'media_video_filename_title'),
			'application'=>Sanitize::getBool($Config,'media_attachment_filename_title'),
			'attachment'=>Sanitize::getBool($Config,'media_attachment_filename_title'),
			'audio'=>Sanitize::getBool($Config,'media_audio_filename_title'),
		);

		$FILENAME_LISTINGID = array(
			'photo'=>Sanitize::getBool($Config,'media_photo_filename_listingid'),
			'video'=>Sanitize::getBool($Config,'media_video_filename_listingid'),
			'application'=>Sanitize::getBool($Config,'media_attachment_filename_listingid'),
			'attachment'=>Sanitize::getBool($Config,'media_attachment_filename_listingid'),
			'audio'=>Sanitize::getBool($Config,'media_audio_filename_listingid'),
		);

		if($FILENAME_TITLE[$media_type] && !empty($listing)) {

			if(isset($listing['Listing']['alias'])) {

				$filename = $listing['Listing']['alias'];
			}
			else {

				$filename = $listing['Listing']['title'];
			}

            $filename = S2Router::sefUrlEncode($filename);

            if(trim(str_replace('-','',$filename)) == '') {

                $filename  = date("Y-m-d-H-i-s");
            }
		}
		else {

			// Leave only valid characters
			$filename = preg_replace(array('/\s+/','/\_+/','/[^A-Za-z0-9\-]/','/\-+/'), array('-','-','','-'), $filename);
		}

		// Random number used to avoid duplicate filenames when multiple files are uploaded together
		// And file name is the same or the listing alias/title is used

		$filename .= '-'.mt_rand(1,100);

		$filename .= "-" . (!$modifier ? time() : $modifier);

		// Prepend listing id to filename
		if($FILENAME_LISTINGID[$media_type] && !empty($listing)) {

			$listing_id = Sanitize::getInt($listing['Listing'],'listing_id');

			$listing_id > 0 and $filename = $listing_id . '-' . $filename;
		}

		return $filename;
	}

	public function getHashedPath($filename, $suffix = '')
	{
		$pathinfo = pathinfo($filename);
		$folder_hash = $this->getFolderHash($pathinfo['filename']);
		return $folder_hash . $pathinfo['filename'] . $suffix . '.' . $pathinfo['extension'];
	}

	public static function getFolderHash($filename, $forUrl = false)
	{
		$hash = md5($filename);

		$DS = $forUrl ? _DS : DS;

		// Create three levels of 32 combinations = 32 x 32 x 32
		$chunks = str_split($hash, 2);
		return $chunks[0] . $DS . $chunks[1] . $DS . $chunks[2] . $DS;
	}

	function download($media)
	{
		$media_type = $this->getMediaType($media);
		$storage_service = $this->_STORAGE[$media_type];
		return $this->_STORAGE_CLASSES[$storage_service]->download($media);
	}

	function upload($media_info, $delete = false)
	{
		if(isset($media_info['media_type']))
		{
			$media_type = $this->getMediaType($media_info);
			$storage_service = $this->_STORAGE[$media_type];
			return $this->_STORAGE_CLASSES[$storage_service]->upload($media_info, $delete);
		}

		return false;
	}

	function delete($data)
	{
		$deleted = array();

		foreach($data AS $media_id=>$media)
		{
			$media_info = $media['Media']['media_info'];

			$media_type = $this->getMediaType($media);

			$storage_service = $this->_STORAGE[$media_type];

			// Delete original image
			if(isset($media_info['image'])) {
				if($this->_STORAGE_CLASSES[$storage_service]->delete($media_info['image']['url'], $media)) {
					$errors[$media_id][] = $media_info['image']['url'];
				}
			}

			if(isset($media_info['thumbnail'])) {
				foreach($media_info['thumbnail'] AS $thumbnail) {
					if($this->_STORAGE_CLASSES[$storage_service]->delete($thumbnail['url'], $media)) {
						$errors[$media_id][] = $thumbnail['url'];
					}
				}
			}

			if(isset($media_info['video'])) {
				foreach($media_info['video'] AS $video) {
					if($this->_STORAGE_CLASSES[$storage_service]->delete($video['url'], $media)) {
						$errors[$media_id][] = $video['url'];
					}
				}
			}

		}

		return $deleted;
	}

	function deleteThumb($media, $size) {

		$deleted = false;

		$media_id = $media['Media']['media_id'];

		$media_info = $media['Media']['media_info'];

		$media_type = $this->getMediaType($media);

		$storage_service = $this->_STORAGE[$media_type];

		if(isset($media_info['thumbnail']) && isset($media_info['thumbnail'][$size])) {

			if(!$this->_STORAGE_CLASSES[$storage_service]->delete($media_info['thumbnail'][$size]['url'], $media)) {

				$errors[$media_id][] = $media_info['thumbnail'][$size]['url'];
			}
			else {

				$deleted = true;
			}
		}

		return $deleted;
	}

	function getMediaType($media) {

		$media_type = isset($media['Media']) ? Sanitize::getString($media['Media'],'media_type') : Sanitize::getString($media,'media_type');

		$embed = isset($media['Media']) ? Sanitize::getString($media['Media'],'embed') : Sanitize::getString($media,'embed');

		if($media_type == 'video' && $embed != '') {
			$media_type = 'video_embed';
		}

		return $media_type;
	}

}