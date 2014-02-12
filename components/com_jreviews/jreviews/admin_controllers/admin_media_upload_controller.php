<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminMediaUploadController extends MyController
{
    var $uses = array('menu','media','review','user','media_encoding','media_like');

    var $helpers = array('libraries','html','assets','admin/paginator','form','routes','time','community','media');

    var $components = array('config','access','everywhere','media_upload','media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    var $denyAccess = false;

    var $tmpFolder;

	/*
	 * General options
	 */
	var $video_thumb_format = 'jpg';

	 /* Video encoding service
	 */

	var $_EncodingService; //transloadit | zencoder | encoding | facebook

	private $Encoding;
	/**
	 * Useful for localhost testing
	 * @var boolean
	 *
	 */
	protected $__TransferToCloudBeforeEncoding = false;

	private $formTokenKeysEdit = array('media_id'=>'media_id');

	private $formTokenKeysNew = array('listing_id'=>'listing_id','review_id'=>'review_id','extension'=>'extension','session_id'=>'session_id');

	/*
	 * Pass page related params from one controller action to another one.
	 */
	private $page_params = array();

	function beforeFilter()
    {
		$this->Access->init($this->Config);

		$this->_EncodingService = Sanitize::getString($this->Config,'media_encode_service');

        parent::beforeFilter();

		if($this->_EncodingService != '')
		{
			S2App::import('Component',array('media_encoding_'.$this->_EncodingService),'jreviews');

			$class_name = inflector::camelize('media_encoding_'.$this->_EncodingService.'Component');

			$this->Encoding = new $class_name($this);

        }

		$this->tmpFolder = cmsFramework::getConfig('tmp_path') . DS;
	}

    function getEverywhereModel() {
        return $this->Media;
    }

    function getNotifyModel() {
        return $this->Media;
    }

	/**
	 *	Displays the media upload form in a standalone page
	 * @return type
	 */
    function create()
    {
		$this->EverywhereAfterFind = true; // Get Listing info for different extensions

        $this->Review->runProcessRatings = false;

		$review_id = $listing_id = 0;

		$extension = '';

		$review = $listing = array();

		$id = explode(':',base64_decode(urldecode(Sanitize::getString($this->params,'id'))));

		switch(count($id)) {
			case 2: // Listing
				$listing_id = (int) array_shift($id);
				break;
			case 3: // Review
				$listing_id = (int) array_shift($id);
				$review_id = (int) array_shift($id);
				break;
		}

		$extension = array_shift($id);

		# Access Validation
		$overrides = array(); // Need to get override access settings for the corresponding listing type

		$this->Listing->addStopAfterFindModel(array('Media','Favorite','Field'));

		$row = array('Media'=>array('listing_id'=>$listing_id,'review_id'=>$review_id,'extension'=>$extension));

		$listingArray = $this->Everywhere->plgAfterFind($this->Media,array($row));

		$listing = array_shift($listingArray);

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

		if($review_id)
		{
			$review = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

			if(!($allowed_types = $this->Access->canAddAnyReviewMedia($review['User']['user_id'], $listing['ListingType']['config'], $review_id)))
			{
				$has_access = false;
			}
		}
		elseif ($listing_id && $extension == 'com_content') {

			$owner = $this->Listing->getListingOwner($listing_id);

			if(!($allowed_types = $this->Access->canAddAnyListingMedia($owner['user_id'], $listing['ListingType']['config'], $listing_id)))
			{
				$has_access = false;
			}
		}
		else {
			$has_access = false;
		}

		// Get the listing data

		$this->Listing->addStopAfterFindModel(array('Media','Favorite','Field'));

		$user_media_counts = $this->Media->getUserUploads($this->_user->id, $extension, $listing_id, $review_id);

		$this->set(array(
			'User'=>$this->_user,
			'listing'=>$listing,
			'review'=>$review,
			'listing_id'=>$listing_id,
			'review_id'=>$review_id,
			'extension'=>$extension,
			'upload_object'=>$review_id > 0 ? 'review' : 'listing',
			'user_media_counts'=>$user_media_counts,
			'session_id'=>$this->_user->id,
			'formTokenKeys'=>$this->formTokenKeysNew,
			'allowed_types'=>$allowed_types

		));

        return $this->render('media_upload','create');;
	}

	private function _setDefaultMediaModelValues(&$data, $options = array())
	{
		$review_id = Sanitize::getInt($data['Media'],'review_id');

		$media_type = Sanitize::getString($options,'media_type');

		$data['Media']['user_id'] = $this->_user->id;
		$data['Media']['published'] = 1;
		$data['Media']['approved'] = (int) !$this->Access->moderateMedia($options['media_type']);
		$data['Media']['created'] = _CURRENT_SERVER_TIME;

		$data['Media']['ipaddress'] = ip2long(s2GetIpAddress());

		// Set default media access level based on media type and object type (listing or review)
		$data['Media']['access'] = $review_id ?

				Sanitize::getInt($this->Config,'media_access_view_'.$media_type.'_review')

				:

				Sanitize::getInt($this->Config,'media_access_view_'.$media_type.'_listing');

		if(!empty($options)) {
			$data['Media'] = array_merge($data['Media'],$options);
		}
	}

	function _save()
	{
		$response = array('success'=>false,'str'=>array());

		if (isset($_SERVER['HTTP_USER_AGENT']) &&
		    (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {

			$json_options = array('encoding'=>'text/html');
		}
		else {
			$json_options = array('encoding'=>'application/json');
		}

		$is_curl_call = false;

		// Use cron secret to bypass form token check for incoming CURL calls

		$cron_secret = Sanitize::getString($this->Config,'cron_secret');

		// Load session for passed user id - used primarily for CURL calls to retrieve the submitter' user session
		$session_id = Sanitize::getInt($this->data,'session_id');

		if($cron_secret != '' && !isset($this->params['form'][cmsFramework::getCustomToken($cron_secret)])) {

			# Done here so it only loads on save and not for all controlller actions.
			$this->components = array('security'/*,'notifications'*/);

			$this->__initComponents();
		}
		else {

			$this->_user = cmsFramework::getUser($session_id);

			if(isset($this->data['clean_filename'])) {
				$this->MediaUpload->clean_filename = Sanitize::getBool($this->data,'clean_filename');
			}
		}

		# Validate form token
		if($this->invalidToken) {

			$response['str'][] = 'INVALID_TOKEN';

			return cmsFramework::jsonResponse($response,$json_options);
        }

		$listing_id = Sanitize::getInt($this->data['Media'],'listing_id');

		$review_id = Sanitize::getInt($this->data['Media'],'review_id');

		$extension = Sanitize::getString($this->data['Media'],'extension');

		# Stop form data tampering
		$tokenVars = array(
			'listing_id'=>$listing_id,
			'review_id'=>$review_id,
			'extension'=>$extension,
			'session_id'=>$session_id
		);

		$formToken = cmsFramework::formIntegrityToken($tokenVars,$this->formTokenKeysNew,false);

		if (!$this->__validateToken($formToken)) {

			$response['str'][] = 'ACCESS_DENIED';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		// Id validation checks
		if($listing_id == 0)
		{
			$response['str'][] = 'PROCESS_REQUEST_ERROR';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . (int) $listing_id)), array());

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

		$this->MediaUpload->init();

		$filename = $this->MediaUpload->getFileName();

		$fileext = $this->MediaUpload->getFileExtension();

		$media_type = $this->MediaUpload->getMediaTypeFromExtension($fileext);

		$media_type_string = $this->MediaUpload->_TRANSLATION_MEDIA_TYPES[$media_type];

		// Validate upload count limits

		$video_upload_types = Sanitize::getString($this->Config,'media_video_upload_methods');

		if($media_type == 'video' && $video_upload_types == 'link') {

			$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$user_uploads = $this->Media->getUserUploads($this->_user->id, $extension, $listing_id, $review_id, $media_type);

		$validation_msg = array(
			''			=>		array('MEDIA_UPLOAD_INVALID_EXT',$filename),
			'photo'		=>		'MEDIA_UPLOAD_PHOTO_LIMIT',
			'video'		=>		'MEDIA_UPLOAD_VIDEO_LIMIT',
			'attachment'=>		'MEDIA_UPLOAD_ATTACHMENT_LIMIT',
			'audio'		=>		'MEDIA_UPLOAD_AUDIO_LIMIT'
		);

		$max_uploads_listing = Sanitize::getVar($this->Config,'media_'.$media_type.'_max_uploads_listing');

		$max_uploads_review = Sanitize::getVar($this->Config,'media_'.$media_type.'_max_uploads_review');

		$max_uploads = $review_id > 0 ? $max_uploads_review : $max_uploads_listing;

		if(in_array($media_type,array('audio','video')) && $max_uploads !== 0

			&& ($this->Config->media_encode_service == '' || $this->Config->media_store_amazons3_key == '')) {

				$media_type == 'video' and $response['str'][] = 'MEDIA_UPLOAD_VIDEO_INCOMPLETE_SETUP';

				$media_type == 'audio' and $response['str'][] = 'MEDIA_UPLOAD_AUDIO_INCOMPLETE_SETUP';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		if($user_uploads != '' && $max_uploads !== '' && (int) $user_uploads >= $max_uploads)
		{
			$response['str'][] = array($validation_msg[$media_type],$max_uploads);

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$this->MediaUpload->setSizeLimit($media_type);

		# Review upload - validate access
		if($review_id) {

			// Get the review data for reviewer id checks
			$review = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

			if(!$this->Access->canAddReviewMedia($media_type, $review['User']['user_id'], array(), $review_id))
			{
				$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

				return cmsFramework::jsonResponse($response,$json_options);
			}
		}
		# Listing upload - validate access
		elseif ($listing_id)
		{
			if($session_id > 0)
			{

				$owner_id = $session_id;
			}
			else {

				$owner = $this->Listing->getListingOwner($listing_id);

				$owner_id = $owner['user_id'];
			}

			if(!$this->Access->canAddlistingMedia($media_type, $owner_id))
			{
				$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

				return cmsFramework::jsonResponse($response,$json_options);
			}
		}

		// Media is first stored in tmp location on the server
		$uploaded_media_info = $this->MediaUpload->handleUpload($this->tmpFolder, $media_type, $listing);
		/*
		 * media_type => mime type check is perfomed to figure out the media type
		 * filename => new filename that JReviews will use for the uploaded media, without the file extension
		 * rel_path => md5 hash generated folder structure
		 * tmp_path => temporary file location on server
		 */

		// On upload error
		if(!$uploaded_media_info['success']) {
			return cmsFramework::jsonResponse($uploaded_media_info,$json_options);
		}

		// Media uploaded to tmp folder, so we keep processing
		if($uploaded_media_info)
		{
			$tmp_title = '';

			if(in_array($media_type,array('attachment','audio'))) {

				$tmp_title = $uploaded_media_info['filename'];
			}

			$media_type = $uploaded_media_info['media_type'];

			$ordering = $this->Media->getNewOrdering($listing_id, $media_type);

			$mediaData = array(
				'filename' => $uploaded_media_info['filename'],
				'file_extension'=> $media_type != 'video' ? $uploaded_media_info['file_extension'] : $this->video_thumb_format,
				'filesize'=>$uploaded_media_info['filesize'],
				'rel_path' => $uploaded_media_info['rel_path'],
				'media_type'=> $media_type,
				'title'=>$tmp_title,
				'ordering'=>$ordering
			);

			if(in_array($media_type,array('video','audio'))) {
				$mediaData['published'] = 2; // Awaiting for encoding to finish, then it is changed to 1
			}

			// Override user id if this is a CURL upload
			if($session_id > 0 && $user_id = Sanitize::getInt($this->data['Media'],'user_id')) {
				$mediaData['user_id'] = $user_id;
			}

			// We save the record in the database, with pending status
			// Then we process it. Encoding for videos, thumbnailing for photos
			$this->_setDefaultMediaModelValues($this->data, $mediaData);

			// Store the media record in the DB
			// Don't run callbacks to update the media counts
			// because we run them only when the encoding job is done
			$this->Media->store($this->data,false,array('beforeSave','plgBeforeSave','plgAfterSave'));

			$media_id = $this->data['Media']['media_id'];

			$uploaded_media_info['media_id'] = $media_id;

			// Process media
			switch($media_type)
			{
				case 'photo':
					$processResults = $this->_processPhoto($uploaded_media_info);
					break;

				case 'audio':
				case 'video':
					$processResults = $this->_processEncoding($uploaded_media_info);
					break;

				case 'attachment':
					$processResults = $this->_processAttachment($uploaded_media_info);
					break;

				default:
					// None of the media types
					break;
			}

			unset($this->data['insertid']);

			if($processResults && Sanitize::getBool($processResults,'success'))
			{
				$this->data['Media']['media_info'] = json_encode($processResults['media_info']);

				// Update media record with thumbnail
				if(in_array($media_type, array('photo','attachment'))) {
					$this->data['finished'] = true;
					$this->Media->store($this->data);
				}
				else {
					// Don't run any callbacks because we run them when the encoding job is done
					$this->Media->store($this->data,false,array());
				}

				$formToken = cmsFramework::formIntegrityToken($this->data['Media'],$this->formTokenKeysEdit,false);

				// Prepare json response for browser
				// Need media_id, media_type, thumbnail url
				$response = array(
					'success'=>$processResults['success'],
					'state'=>$processResults['state'],
					'media_id'=>$media_id,
					'media_type'=>$media_type,
					'approved'=>$this->data['Media']['approved'],
					'thumb_url'=>Sanitize::getString($processResults,'thumb_url'),
					'encoding_job'=>Sanitize::getVar($processResults,'encoding_job'),
					'token' => $formToken,
					'str'=>array()
				);

				echo cmsFramework::jsonResponse($response,$json_options);
			}

			else {

				$response['str'][] = 'PROCESS_REQUEST_ERROR';

				echo cmsFramework::jsonResponse($response,$json_options);
			}
		}
	}

	function _uploadUrl()
	{
		$response = array('success'=>false,'str'=>array());

		$json_options = array('encoding'=>'application/json');

		# Done here so it only loads on save and not for all controlller actions.
		$this->components = array('security'/*,'notifications'*/);

		$this->__initComponents();

		# Validate form token
		if($this->invalidToken) {

			$response['str'][] = 'INVALID_TOKEN';

			return cmsFramework::jsonResponse($response,$json_options);
        }

		$listing_id = Sanitize::getInt($this->data['Media'],'listing_id');

		$review_id = Sanitize::getInt($this->data['Media'],'review_id');

		$extension = Sanitize::getString($this->data['Media'],'extension');

		# Stop form data tampering
		$tokenVars = array(
			'listing_id'=>$listing_id,
			'review_id'=>$review_id,
			'extension'=>$extension
		);

		$formToken = cmsFramework::formIntegrityToken($tokenVars,$this->formTokenKeysNew,false);

		// Id validation checks
		if($listing_id == 0)
		{
			$response['str'][] = 'PROCESS_REQUEST_ERROR';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . (int) $listing_id)), array());

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

		if(!$this->Access->canAddMediaFromUrl($review_id ? 'review' : 'listing') || !$this->__validateToken($formToken)) {

			$response['str'][] = 'ACCESS_DENIED';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$this->MediaUpload->init();

		$upload_url = Sanitize::getString($this->data,'upload_url');

		$upload_path = str_replace(array(WWW_ROOT,_DS),array(PATH_ROOT,DS),$upload_url);

        $pathinfo = pathinfo($upload_url);

		$filename = Sanitize::getString($pathinfo,'basename');

		$fileext = strtolower(Sanitize::getString($pathinfo,'extension'));

		if($fileext == '' || substr($upload_url,0,4) != 'http') {

			$response['str'][] = 'MEDIA_UPLOAD_URL_INVALID';

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$media_type = $this->MediaUpload->getMediaTypeFromExtension($fileext);

		$media_type_string = $this->MediaUpload->_TRANSLATION_MEDIA_TYPES[$media_type];

		// Validate upload count limits
		$video_upload_types = Sanitize::getString($this->Config,'media_video_upload_methods');

		if($media_type == 'video' && $video_upload_types == 'link') {

			$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$user_uploads = $this->Media->getUserUploads($this->_user->id, $extension, $listing_id, $review_id, $media_type);

		$validation_msg = array(
			''			=>		array('MEDIA_UPLOAD_INVALID_EXT',$filename),
			'photo'		=>		'MEDIA_UPLOAD_PHOTO_LIMIT',
			'video'		=>		'MEDIA_UPLOAD_VIDEO_LIMIT',
			'attachment'=>		'MEDIA_UPLOAD_ATTACHMENT_LIMIT',
			'audio'		=>		'MEDIA_UPLOAD_AUDIO_LIMIT'
		);

		$max_uploads_listing = Sanitize::getVar($this->Config,'media_'.$media_type.'_max_uploads_listing');

		$max_uploads_review = Sanitize::getVar($this->Config,'media_'.$media_type.'_max_uploads_review');

		$max_uploads = $review_id > 0 ? $max_uploads_review : $max_uploads_listing;

		if(in_array($media_type,array('audio','video')) && $max_uploads !== 0

			&& ($this->Config->media_encode_service == '' || $this->Config->media_store_amazons3_key == '')) {

				$media_type == 'video' and $response['str'][] = 'MEDIA_UPLOAD_VIDEO_INCOMPLETE_SETUP';

				$media_type == 'audio' and $response['str'][] = 'MEDIA_UPLOAD_AUDIO_INCOMPLETE_SETUP';

				return cmsFramework::jsonResponse($response,$json_options);
		}

		if($user_uploads != '' && $max_uploads !== '' && (int) $user_uploads >= $max_uploads)
		{
			$response['str'][] = array($validation_msg[$media_type],$max_uploads);

			return cmsFramework::jsonResponse($response,$json_options);
		}

		# Review upload - validate access
		if($review_id) {

			// Get the review data for reviewer id checks
			$review = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

			if(!$this->Access->canAddReviewMedia($media_type, $review['User']['user_id'], array(), $review_id))
			{
				$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

				return cmsFramework::jsonResponse($response,$json_options);
			}
		}
		# Listing upload - validate access
		elseif ($listing_id)
		{
			$owner = $this->Listing->getListingOwner($listing_id);

			$owner_id = $owner['user_id'];

			if(!$this->Access->canAddlistingMedia($media_type, $owner_id, array(), $listing_id))
			{
				$response['str'][] = array('MEDIA_UPLOAD_DISALLOWED',$filename,$media_type_string);

				return cmsFramework::jsonResponse($response,$json_options);
			}
		}

		// Bring remote file local for photos and attachments
		if(in_array($media_type,array('photo','attachment'))) {

			if(!$upload_path = $this->_grabRemoteFile(array('filename'=>$pathinfo['filename'],'file_extension'=>$fileext), $upload_url, $this->tmpFolder, true /*skipUpload*/)) {

				$response['str'][] = 'MEDIA_UPLOAD_URL_NOLINKING';

				return cmsFramework::jsonResponse($response,$json_options);
			}


			$upload_url = pathToUrl($upload_path);
		}

		if(strstr($upload_url,WWW_ROOT)) {

			$filesize = filesize($upload_path);
		}
		else {

			// *********** ADD AS A METHOD getRemoteFileSize in UploadComponent
			$ch = curl_init($upload_url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

			curl_setopt($ch, CURLOPT_HEADER, TRUE);

			curl_setopt($ch, CURLOPT_NOBODY, TRUE);

			$data = curl_exec($ch);

			$filesize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

			curl_close($ch);
		}

		// Check file size limit
		$size_limit = $this->MediaUpload->setSizeLimit($media_type);

		if ($filesize > $this->MediaUpload->toBytes($size_limit.'m')) {

			$response['str'][] = array('MEDIA_UPLOAD_MAX_SIZE',$filename,$size_limit);

			return cmsFramework::jsonResponse($response,$json_options);
		}

		$clean_filename = $this->MediaUpload->clean_filename ? $this->MediaStorage->cleanFileName($pathinfo['filename'], $media_type, $listing) : $pathinfo['filename'];

		// Media is first stored in tmp location on the server
		$uploaded_media_info = array(
				'success'=>true,
				'media_type'=>$media_type,
				'filename'=>$clean_filename,
				'filesize'=>$filesize,
				'file_extension'=>$fileext,
				'rel_path'=>MEDIA_ORIGINAL_FOLDER . _DS . MediaStorageComponent::getFolderHash($clean_filename,true),
				'tmp_path'=>$upload_path,
				'orig_filename'=>$pathinfo['filename'],
				'del_original'=>0
			);

		/*
		 * media_type => mime type check is perfomed to figure out the media type
		 * filename => new filename that JReviews will use for the uploaded media, without the file extension
		 * rel_path => md5 hash generated folder structure
		 * tmp_path => temporary file location on server
		 */

		// Media uploaded to tmp folder, so we keep processing
		if($uploaded_media_info)
		{
			$tmp_title = '';

			if(in_array($media_type,array('attachment','audio'))) {

				$tmp_title = $uploaded_media_info['filename'];
			}

			$media_type = $uploaded_media_info['media_type'];

			$ordering = $this->Media->getNewOrdering($listing_id, $media_type);

			$mediaData = array(
				'filename' => $uploaded_media_info['filename'],
				'file_extension'=> $media_type != 'video' ? $uploaded_media_info['file_extension'] : $this->video_thumb_format,
				'filesize'=>$uploaded_media_info['filesize'],
				'rel_path' => $uploaded_media_info['rel_path'],
				'media_type'=> $media_type,
				'title'=>$tmp_title,
				'ordering'=>$ordering
			);

			if(in_array($media_type,array('video','audio'))) {
				$mediaData['published'] = 2; // Awaiting for encoding to finish, then it is changed to 1
			}

			// We save the record in the database, with pending status
			// Then we process it. Encoding for videos, thumbnailing for photos
			$this->_setDefaultMediaModelValues($this->data, $mediaData);

			// Store the media record in the DB
			// Don't run callbacks to update the media counts
			// because we run them only when the encoding job is done
			$this->Media->store($this->data,false,array('beforeSave','plgBeforeSave','plgAfterSave'));

			$media_id = $this->data['Media']['media_id'];

			$uploaded_media_info['media_id'] = $media_id;

			// Process media
			switch($media_type)
			{
				case 'photo':
					$processResults = $this->_processPhoto($uploaded_media_info);
					break;

				case 'audio':
				case 'video':
					$processResults = $this->_processEncoding($uploaded_media_info);
					break;

				case 'attachment':
					$processResults = $this->_processAttachment($uploaded_media_info);
					break;

				default:
					// None of the media types
					break;
			}

			unset($this->data['insertid']);

			if($processResults)
			{
				$this->data['Media']['media_info'] = json_encode($processResults['media_info']);

				// Update media record with thumbnail
				if(in_array($media_type, array('photo','attachment'))) {

					$this->data['finished'] = true;

					$this->Media->store($this->data);
				}
				else {

					// Don't run any callbacks because we run them when the encoding job is done
					$this->Media->store($this->data,false,array());
				}

				$formToken = cmsFramework::formIntegrityToken($this->data['Media'],$this->formTokenKeysEdit,false);

				// Prepare json response for browser
				// Need media_id, media_type, thumbnail url
				$response = array(
					'success'=>$processResults['success'],
					'state'=>$processResults['state'],
					'media_id'=>$media_id,
					'media_type'=>$media_type,
					'approved'=>$this->data['Media']['approved'],
					'thumb_url'=>Sanitize::getString($processResults,'thumb_url'),
					'encoding_job'=>Sanitize::getVar($processResults,'encoding_job'),
					'filename'=>$filename,
					'token' => $formToken,
					'upload_url'=>Sanitize::getString($this->data,'upload_url') != '',
					'str'=>array()
				);

				echo cmsFramework::jsonResponse($response,$json_options);
			}
		}
	}

	/**
	 *	Uploads attachment to defined storage
	 * @param type $result
	 * @return type
	 */
	private function _processAttachment($media)
	{
		$result = array();

		$del_original = Sanitize::getBool($media,'del_original');

		if(!$object_url = $this->MediaStorage->upload($media,$del_original)) {
			// Error
			return false;
		}

		switch($media['file_extension']) {
			case 'pdf':
					$file = 'pdf.png';
				break;
			case 'zip':
					$file = 'zip.png';
				break;
			default:
					$file = 'attachment.png';
				break;
		}

		$result = array(
			'success'=>true,
			'state'=>'finished',
			'media_info'=>array('attachment'=>array(
				'format'=>$media['file_extension'],
			)),
			'thumb_url'=>$this->viewImages . $file
		);

		return $result;
	}

	/**
	 *	Uploads image to defined storage
	 * @param type $result
	 * @return type
	 */
	private function _processPhoto($media)
	{
		$result = array();

		$del_original = Sanitize::getBool($media,'del_original');

		// Get size before moving it to final location
		$orig_size = getimagesize($media['tmp_path']);

		$size = array('width'=>$orig_size[0],'height'=>$orig_size[1]);

		$resize_string = Sanitize::getString($this->Config,'media_photo_resize');

		if($resize_string == '') {

			$new_size = array($orig_size[0],$orig_size[1]);
		}
		else {

			$new_size = explode('x',low($resize_string));
		}

		if(!isset($new_size[1])) $new_size[1] = $new_size[0];

		$quality = Sanitize::getInt($this->Config,'media_photo_resize_quality',90);

		if(!class_exists('PhpThumbFactory')) {
			S2App::import('Vendor', 'phpthumb' . DS . 'ThumbLib.inc');
		}

		$img_path = $media['tmp_path'];

		ob_start();

		$Thumb = PhpThumbFactory::create($img_path,array(
			'jpegQuality'=>$quality,
			'resizeUp'=>false
			));

		$degrees = 0;

		try {
			// http://php.net/manual/en/function.exif-read-data.php

			$ext = pathinfo($img_path, PATHINFO_EXTENSION);

			getimagesize($img_path, $imageinfo);

			if($ext == 'jpg' && function_exists('exif_read_data') && substr($imageinfo['APP1'], 0, 4) == 'Exif') {

				$exif = exif_read_data($img_path);

				$orientation = Sanitize::getInt($exif,'Orientation');

				switch($orientation) {

			        case 1: // nothing
			        	break;

			        case 2: // horizontal flip
			        	break;

        			case 3: // 180 rotate left
						$degrees = 180;
					break;

					case 4: // vertical flip
					break;

					case 5: // vertical flip + 90 rotate right
					break;

        			case 6: // 90 rotate right
						$degrees = -90;
					break;

        			case 7: // horizontal flip + 90 rotate right
        			break;

        			case 8:    // 90 rotate left
						$degrees = 90;
					break;

					default:
					break;
				}
			}
		}
		catch(Exception $e) {
			//
		}

		if($ext == 'jpg') {

			$image = $Thumb->resize($new_size[0],$new_size[1])->rotateImageNDegrees($degrees)->save($img_path);
		}
		else {

			$image = $Thumb->resize($new_size[0],$new_size[1])->save($img_path);
		}

		ob_end_clean();

		if($Thumb->getHasError()) {
			return false;
		}

		$size = $Thumb->getCurrentDimensions();

		if(!$object_url = $this->MediaStorage->upload($media,$del_original)) {
			// Error
			return false;
		}

		$result = array(
			'success'=>true,
			'state'=>'finished',
			'media_info'=>array('image'=>array(
				'format'=>$media['file_extension'],
				'width'=>$size['width'],
				'height'=>$size['width']
			)),
			'thumb_url'=>$object_url
		);

		return $result;
	}

	/**
	 * Uploads video and audio to defined storage and starts encoding process for videos
	 * @param type $result
	 * @return boolean
	 */
	private function _processEncoding($media)
	{
		$object_url = '';

		if(!$this->Encoding) return false; // No encoding service selected

		$del_original = !in_array($media['file_extension'],array('mp4','webm')) && !Sanitize::getBool($media,'del_original');

		if(!is_file($media['tmp_path'])) {

			// The source file is in a remote location so we let the encoding service access it directly
			$this->__TransferToCloudBeforeEncoding = false;
		}

		# Transfer to Cloud before encoding job starts - only if it's not an upload from URL
		if($this->action == '_save' && ($this->__TransferToCloudBeforeEncoding || $this->ipaddress == '127.0.0.1') && $this->Encoding->serviceType() == 'passthru' && $this->MediaStorage->getService($media['media_type']) != 'local')
		{
			// Only delete original if it's not in one of the formats we need
			if(!$object_url = $this->MediaStorage->upload($media,$del_original)) {
				// Error
				return false;
			}
		}
		else {

			$object_url = $this->action == '_save' ? pathToUrl($media['tmp_path']) : $media['tmp_path'];
		}

		$media['object_url'] = $object_url;

		$media['base_path'] = $this->MediaStorage->getBasePath($media['media_type']);

		# Start encoding job
		if(!$encoding_result = $this->Encoding->startJob($media, array('thumb_format'=>$this->video_thumb_format))) {

			// Error - delete tmp file and media row
			if(is_file($media['tmp_path'])) @unlink($media['tmp_path']);

			$this->Media->del($media['media_id']);

			return array(
				'success'=>false,
				'state'=>'failed',
				'media_info'=>array()
			);
		}

		// Insert new encoding job in DB
		$encoding_data = array();
		$encoding_data['insert'] = true;
		$encoding_data['MediaEncoding']['job_id'] = $encoding_result['encoding_job']['id'];
		$encoding_data['MediaEncoding']['media_id'] = $media['media_id'];
		$encoding_data['MediaEncoding']['created'] = _CURRENT_SERVER_TIME;

		$this->MediaEncoding->store($encoding_data);

		// Remove local tmp file - commented because if the file is large it may still be in the process of getting transffered at this point
		// if($del_original && file_exists($media['tmp_path'])) {

		// 	@unlink($media['tmp_path']);
		// }

		$result = array(
			'success'=>true,
			'state'=>$encoding_result['state'],
			'thumb_url'=>WWW_ROOT . Sanitize::getString($this->Config,'media_general_default_video_path'),
			'media_info'=>$encoding_result['media_info'],
			'encoding_job'=>array('id'=>$encoding_data['insertid'])
		);

		return $result;
	}

	/**
	 * API call to encoding service to check encoding status
	 * @return type
	 */
	function checkJobStatus()
	{
		$job_id = null;

		$media_id = Sanitize::getString($this->params,'media_id');

		$remote = Sanitize::getInt($this->params,'remote',0);

		if($remote == 1) {

			$encoding = $this->Encoding->processNotification();

			$job_id = Sanitize::getString($encoding,'job_id');
		}
		else {

			$id = Sanitize::getString($this->params,'job_id');

			$job = $this->MediaEncoding->findRow(array(
				'conditions'=>array('MediaEncoding.id = ' . $id)),array());

			$job_id = $job['MediaEncoding']['job_id'];

		}

		if($job_id)
		{
			if(!$remote) {

				$encoding = $this->Encoding->checkStatus($job_id);
			}

			$encoding['media_id'] = $media_id;

			$this->MediaEncoding->updateJobStatus($job_id, $encoding);

			switch($encoding['state'])
			{
				case 'finished':

					// Trigger notifications only on finished encoding jobs
					$this->components = array('notifications');

					$this->__initComponents();

					$data = $this->MediaEncoding->findRow(array(
						'conditions'=>array('MediaEncoding.job_id = ' . $this->Quote($job_id)
					)));

					// Update media counts
					if(empty($data)) {
						return '0';
					}

					// $data = $this->Media->data;

					$media_type = Sanitize::getString($data['Media'],'media_type');

					// Re-writes media columns as necessary
					if(isset($encoding['media'])) {
						$data['Media'] = array_insert($data['Media'], $encoding['media']);
					}

					if(is_array(Sanitize::getVar($data['Media'],'media_info'))) {
						$media_info = array_merge($data['Media']['media_info'], $encoding['media_info']);
					}
					else {
						$media_info = $encoding['media_info'];
					}

					$data['Media']['media_info'] = json_encode($media_info);

					$data['Media']['published'] = 1;

					$data['Media']['approved'] = (int) !$this->Access->moderateMedia($media_type);

					if(isset($data['Media']) && $media_type != null)
					{
						$data['finished'] = true; // Can be used in plugins to know when it's the final update

						$this->Media->store($data);
					}

					$this->Media->updateMediaCount($job_id);

					// Delete original media
					/**************************/

					// Get the filename without extension from the thumbnail url, then delete all files starting with this name from the tmp folder
					$query = "SELECT filename FROM #__jreviews_media WHERE media_id = " . $media_id;

					$filename = $this->Media->query($query,'loadResult');

					if($filename != '') {

				    	$filesToDelete = glob($this->tmpFolder . $filename . '.*');

				    	if(!empty($filesToDelete)) {

					    	foreach($filesToDelete AS $file) {

								is_file($file) and @unlink($file);
					    	}
				    	}

					}

					break;
			}

			if($remote == 1) {

				return json_encode($media_info);
			}

			$out = array(
				'state'=>$encoding['state'],
				'thumb_url'=>$encoding['thumb_url']
			);

			if(isset($data)) {

				$out['media_id'] = $media_id;
				$out['media_type'] = $media_type;
				$out['token'] = cmsFramework::getCustomToken($media_id);
			}

			return cmsFramework::jsonResponse($out);
		}
	}

	/**
	 * Grabs video from 3rd party service to embed on the site
	 */
	function _embedVideo()
	{
		$response = array('success'=>false,'str'=>array());

		$media_type = 'video';

		$listing_id = Sanitize::getInt($this->data['Media'],'listing_id');

		$review_id = Sanitize::getInt($this->data['Media'],'review_id');

		$extension = Sanitize::getString($this->data['Media'],'extension');

		$video_upload_types = Sanitize::getString($this->Config,'media_video_upload_methods');

		$video_link_sites = Sanitize::getVar($this->Config,'media_video_link_sites');

		if(!in_array($video_upload_types,array('all','link'))) {

			$response['str'][] = 'MEDIA_EMBED_DISALLOWED';

			return cmsFramework::jsonResponse($response);
		}

		$user_uploads = $this->Media->getUserUploads($this->_user->id, $extension, $listing_id, $review_id, $media_type);

		$this->EverywhereAfterFind = true; // Get Listing info for different extensions

		// Get listing type override settings
		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)),array());

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

		$max_uploads_listing = Sanitize::getString($this->Config,'media_'.$media_type.'_max_uploads_listing');

		$max_uploads_review = Sanitize::getString($this->Config,'media_'.$media_type.'_max_uploads_review');

		$max_uploads = $review_id > 0 ? $max_uploads_review : $max_uploads_listing;

		if($user_uploads != '' && $max_uploads !== '' && (int) $user_uploads >= $max_uploads)
		{
			$response['str'][] = array('MEDIA_UPLOAD_VIDEO_LIMIT',$max_uploads);

			return cmsFramework::jsonResponse($response);
		}

		if($this->Config->media_store_video_embed == 's3' && ($this->Config->media_encode_service == '' || $this->Config->media_store_amazons3_key == '')) {

			$response['str'][] = 'MEDIA_UPLOAD_VIDEO_INCOMPLETE_SETUP';

			return cmsFramework::jsonResponse($response);
		}

		// Figure out which video service is used and instantiate the correct component for it
		$embed_url = Sanitize::getString($this->data,'embed_url');

		$hostname = @parse_url($embed_url,PHP_URL_HOST);

		if($hostname)
		{
			$hostname = explode('.',$hostname);

			array_pop($hostname);

			$video_site = array_pop($hostname);

			// Deal with shortened urls
			if($video_site == 'youtu') {
				$video_site = 'youtube';
			}

			if(!in_array($video_site,$video_link_sites)) {

				$response['str'][] = 'MEDIA_EMBED_VIDEO_NOT_FOUND';

				return cmsFramework::jsonResponse($response);
			}

			if(S2App::import('Component','media_storage_'.$video_site,'jreviews'))
			{
				$class_name = inflector::camelize('media_storage_'.$video_site).'Component';

				$VideoService = new $class_name($this);

				if($out = $VideoService->processEmbed($embed_url, $listing))
				{
					// Add media to database
					$service = $out['service']; // Used in output response

					unset($out['service']);

					if($out['thumb_url'] != '') {

						$parsed_url = @parse_url($out['thumb_url']);

						$pathinfo = pathinfo($parsed_url['path']);

						$out['Media']['file_extension'] = $pathinfo['extension'];

						// Grab external frame and store locally
						if(!$tninfo = $this->_grabRemoteFile($out['Media'], $out['thumb_url'], $this->tmpFolder))
						{
							return cmsFramework::jsonResponse(array('success'=>false));
						}

						// For local storage better to use file stream to avoid server restrictions
						$out['Media']['media_info'] = json_encode(array(
							'image'=>array(
								'format'=>$pathinfo['extension'],
								'width'=>$tninfo['width'],
								'height'=>$tninfo['height']
							)
						));
					}

					$this->_setDefaultMediaModelValues($this->data, $out['Media']);

					$this->data['finished'] = true; // Can be used in plugins to know when it's the final update

					if($this->Media->store($this->data))
					{
						$media_id = $this->data['Media']['media_id'];

						unset($this->data['insertid']);

						$response['success'] = true;
						$response['media_type'] = 'video';
						$response['media_id'] = $media_id;
						$response['service'] = $service;
						$response['embed'] = true;
						$response['approved'] = $this->data['Media']['approved'];
						$response['state'] = 'finished';
						$response['thumb_url'] = $tninfo['url'];
						$response['title'] = $out['Media']['title'];
						$response['description'] = $out['Media']['description'];

						return cmsFramework::jsonResponse($response);
					}
				}
			}
		}

		$response['str'][] = 'MEDIA_EMBED_VIDEO_NOT_FOUND';

		return cmsFramework::jsonResponse($response);
	}

	/**
	 *	Grabs image from remote site and uploads it to the pre-defined storage location
	 * @param type $media_type to determine where to store the grabbed image
	 * @param type $remote	url of remote image
	 * @param type $name	name to use for storing the image
	 * @param type $tmp_dir temporary directory to store the grabbed image before upload to storage location
	 * @return type url of stored images
	 */
	public function _grabRemoteFile($media_info, $remote, $tmp_dir /*path*/, $skipUpload = false)
	{
		$filename = $media_info['filename'];

		$file_extension = $media_info['file_extension'];

		$target = $tmp_dir . $filename . '.'. $file_extension;

		$options = array(
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
			CURLOPT_AUTOREFERER => true,
			// CURLOPT_COOKIEFILE => '',
			// CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER=> 0,
			CURLOPT_BINARYTRANSFER=>true,
			CURLOPT_SSL_VERIFYPEER=>0,
			CURLOPT_SSL_VERIFYHOST=>0
		);

		$ch = curl_init($remote);

		$fp = fopen($target, 'wb');

		curl_setopt_array($ch, $options);

		curl_setopt($ch, CURLOPT_FILE, $fp);

		curl_exec($ch);

		$curl_info = curl_getinfo($ch);

		curl_close($ch);

		fclose($fp);

		if($curl_info['http_code'] == 200)
		{
			if($skipUpload) return $target;

			$media_info['tmp_path'] = $target;

			if($file_url = $this->MediaStorage->upload($media_info,true)) {

				$size = $media_info['media_type'] == 'photo' ? getimagesize($target) : array(0=>'',1=>'');

				return array(
					'url'=>$file_url,
					'path'=>$target,
					'width'=>$size[0],
					'height'=>$size[1]
				);
			}
		}

		// If upload fails delete the tmp file
		is_file($target) and @unlink($target);

		return false;
	}

	function response() {

		$id = Sanitize::getInt($this->params,'id');

		if($id) {

			$mediaEncoding = $this->MediaEncoding->findRow(array(
				'conditions'=>array('MediaEncoding.id = ' . $id)
				));

			prx($mediaEncoding['MediaEncoding']['response']);

			exit;
		}
	}

}