<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaModel extends MyModel {

    var $name = 'Media';

    var $useTable = '#__jreviews_media AS Media';

    var $primaryKey = 'Media.media_id';

    var $realKey = 'media_id';

    var $fields = array(
        'Media.media_id AS `Media.media_id`',
        'Media.media_type AS `Media.media_type`',
        'Media.listing_id AS `Media.listing_id`',
        'Media.extension AS `Media.extension`',
        'Media.review_id AS `Media.review_id`',
        'Media.name AS `Media.name`',
        'Media.filename AS `Media.filename`',
        'Media.file_extension AS `Media.file_extension`',
        'Media.rel_path AS `Media.rel_path`',
        'Media.media_info AS `Media.media_info`',
        'Media.metadata AS `Media.metadata`',
        'Media.embed AS `Media.embed`',
        'Media.title AS `Media.title`',
        'Media.description AS `Media.description`',
        'Media.duration AS `Media.duration`',
        'Media.filesize AS `Media.filesize`',
        'Media.created AS `Media.created`',
        'Media.modified AS `Media.modified`',
		'Media.approved AS `Media.approved`',
		'Media.published AS `Media.published`',
		'Media.access AS `Media.access`',
		'INET_NTOA(Media.ipaddress) AS `Media.ipaddress`',
        'Media.views AS `Media.views`',
        'Media.main_media AS `Media.main_media`',
        'Media.likes_up AS `Media.likes_up`',
        'Media.likes_total AS `Media.likes_total`',
        'Media.likes_rank AS `Media.likes_rank`',
		'Media.ordering AS `Media.ordering`',
		'User.id AS `Media.user_id`',
		'User.id AS `User.user_id`',
		'User.name AS `User.name`',
		'User.username AS `User.username`',
		'User.email AS `User.email`',
    );

    var $joins = array(
        'user'=>'LEFT JOIN #__users AS User ON Media.user_id = User.id'
    );

    var $conditions = array();

	var $__update_counts = array();

	/**
	 * Runs in video pages
	 * @param type $results
	 * @return type
	 */
	function afterFind($results)
	{
		if(empty($results)) return $results;

		if(isset($results['Media'])) {

			$results['Media']['media_info'] = json_decode($results['Media']['media_info'],true);

			$results['Media']['metadata'] = json_decode($results['Media']['metadata'],true);

			return $results;
		}

		$MediaStorage = Configure::read('Media.storage');

		$Menu = ClassRegistry::getClass('MenuModel');

		$menu_id_catchall = $Menu->getMenuIdByAction(101); // Media Catch-All menu

        $Config = Configure::read('JreviewsSystem.Config');

		$use_catch_all = Sanitize::getBool($Config,'media_url_catchall');

		foreach($results AS $media_id=>$media)
		{
			if($use_catch_all && $menu_id_catchall) {
				 $results[$media_id]['Media']['menu_id'] = $menu_id_catchall;
			}
			elseif(isset($media['Category']['menu_id'])) {
				$results[$media_id]['Media']['menu_id'] = $media['Category']['menu_id'];
			}
			else {
				$results[$media_id]['Media']['menu_id'] = '';
			}

			// For guest submissions, replace the user's name with the guest's name
			if((int) $media['Media']['user_id'] == 0) {

				$results[$media_id]['User']['name'] = $results[$media_id]['User']['username'] = $media['Media']['name'];
			}

			if(isset($media['Media']['metadata']))
			{
				$results[$media_id]['Media']['metadata'] = json_decode($media['Media']['metadata'],true);
			}

			if(isset($media['Media']['media_info']))
			{
				$media_info = $results[$media_id]['Media']['media_info'] = json_decode($media['Media']['media_info'],true);

				if($media['Media']['media_type'] == 'video' && $media['Media']['embed'] != '') {
					$media_type = 'video_embed';
				}
				else {
					$media_type = $media['Media']['media_type'];
				}

				$filename_url = $MediaStorage->getStorageUrl($media_type, $media['Media']['rel_path'] . $media['Media']['filename'], array('cdn'=>true));

				$media_info = $this->completeMediaUrls($media['Media'], $filename_url);

				/*
				 * Add Embed video urls for 3rd party video services
				 */
				if($media['Media']['media_type'] == 'video' && $media['Media']['embed'] != '') {

					$filename_url = $MediaStorage->getStorageUrl($media['Media']['embed'], $media['Media']['filename']);

					$media_info_embed = $this->completeEmbedUrls($media['Media'], $filename_url);

					$media_info = array_insert($media_info, $media_info_embed);
				}

				$results[$media_id]['Media']['media_path'] = $filename_url;

				$results[$media_id]['Media']['media_info'] = $media_info['media_info'];
			}
		}

        if(!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) {
            # Add Community info to results array
            if(class_exists('CommunityModel')) {
                $Community = ClassRegistry::getClass('CommunityModel');
                $results = $Community->addProfileInfo($results, 'Media', 'user_id');
            }
        }

		return $results;
	}

	/**
	 * Query callback run before insert/update methods
	 */
	function beforeSave(&$media)
	{
		// It's a new media, so we check if there's already another media for the same listing/extension set as main_media
		// or we set this one as default if it's not a review media
		// Audio and attachments are never set as main media

		# A possible enhancement would be to get the listing owner id and only set media uploaded by listing owner to main by default.

		if(!isset($media['Media']['media_id']) && in_array($media['Media']['media_type'],array('video','photo')))
		{
			$listing_id = Sanitize::getInt($media['Media'],'listing_id');

			$review_id = Sanitize::getInt($media['Media'],'review_id');

			$extension = Sanitize::getString($media['Media'],'extension');

			if($listing_id && !$review_id && $extension != '')
			{
				$query = "
					SELECT
						count(*)
					FROM
						#__jreviews_media
					WHERE
						listing_id = " . $listing_id . " AND extension = '" . $extension . "'
						AND	main_media = 1
				";

				$count = $this->query($query,'loadResult');

				if(!$count) {
					$media['Media']['main_media'] = 1;
				}
			}

		}
	}

    /**
	 * Update media count
	 * @param type $data
	 */
	function afterSave($status)
    {
		if($status) {

	        clearCache('','__data');

	        clearCache('','views');

            cmsFramework::clearSessionVar('Media', 'findCount');

			return $this->updateMediaCount();
		}
	}

	/**
	 * Finds the largest ordering value so new media is saved with the next value up
	 * @param  integer $listing_id
	 * @param  string $media_type
	 * @return integer             new ordering value
	 */
	function getNewOrdering($listing_id, $media_type)
	{
		$query = "
			SELECT
				max(ordering) + 1
			FROM
				#__jreviews_media
			WHERE
				listing_id = " . $listing_id . "
				AND
				media_type = " . $this->Quote($media_type) . "
		";

		return (int) $this->query($query,'loadResult');
	}

	function del($media_ids)
	{
		if(is_array($media_ids))
		{
			$media_ids = cleanIntegerCommaList(implode(',',$media_ids));
		}
		else {
			$media_ids = (int) $media_ids;
		}

		$data_after_delete = Sanitize::getVar($this,'data_after_delete');

		if(is_null($data_after_delete))
		{

			$this->data_after_delete = $this->findAll(array(
				'conditions'=>array(
					'Media.media_id IN (' . $media_ids . ')'
				)
			));

		}

		$query = "
			DELETE
				Media,
				MediaLike,
				Encoding
			FROM
				#__jreviews_media AS Media
			LEFT JOIN
				#__jreviews_media_likes AS MediaLike ON MediaLike.media_id = Media.media_id
			LEFT JOIN
				#__jreviews_media_encoding AS Encoding ON Encoding.media_id = Media.media_id
			WHERE
				Media.media_id IN (". $media_ids .")
		";

		if($result = $this->query($query)) {

			$this->afterDeleteMedia($media_ids);

            cmsFramework::clearSessionVar('Media', 'findCount');

	        clearCache('','__data');

	        clearCache('','views');

			return true;
		}

		return false;
	}

	function delThumb($media_id, $size)
	{
		$media = $this->findRow(array('conditions'=>array('Media.media_id = ' . (int) $media_id)));

		$media_info = $media['Media']['media_info'];

		if(isset($media_info['thumbnail'][$size])) {

			unset($media_info['thumbnail'][$size]); //Remove the thumbnail from the media info array

			$media_info = json_encode($media_info);

			$query = "UPDATE #__jreviews_media SET media_info = " . $this->Quote($media_info) . " WHERE media_id = " . $media_id;

			$this->query($query);

			// Delete the media files from the storage service
			$MediaStorage = Configure::read('Media.storage');

			$MediaStorage->deleteThumb($media, $size);
		}

		return true;
	}

	function deleteByListingId($listing_id, $extension = 'com_content')
	{
		$media = $this->findAll(array('conditions'=>array(
			'Media.listing_id IN (' . $this->Quote($listing_id) . ') AND Media.extension = ' . $this->Quote($extension)
		)));

		$this->data_after_delete = $media;

		if(!empty($media)) {
			return $this->del(array_keys($media));
		}

		return true;
	}

	function deleteByReviewId($review_id)
	{
		$media = $this->findAll(array('conditions'=>array(
			'Media.review_id IN (' . $this->Quote($review_id) . ')'
		)));

		$this->data_after_delete = $media;

		if(!empty($media)) {
			return $this->del(array_keys($media));
		}

		return true;
	}

	function afterDeleteMedia($media_id = null)
	{
		// Delete the media files from the storage service
		$MediaStorage = Configure::read('Media.storage');

		$MediaStorage->delete($this->data_after_delete);

		// Update media totals
		foreach($this->data_after_delete AS $data)
		{
			$this->data = $data;

			$this->updateMediaCount();
		}
	}

	function updateMediaCount($job_id = null)
	{
		$conditions = $countsData = array();

		$total = 0;

		if($job_id) {

			$MediaEncoding = ClassRegistry::getClass('MediaEncodingModel');

			$this->data = $MediaEncoding->findRow(array(
				'conditions'=>array('MediaEncoding.job_id = ' . $this->Quote($job_id)
			)));

			if(empty($this->data)) return false;
		}

		$listing_id = Sanitize::getInt($this->data['Media'],'listing_id');

		$review_id = Sanitize::getInt($this->data['Media'],'review_id');

		$extension = Sanitize::getString($this->data['Media'],'extension');

		$review_hash = md5($review_id.$listing_id.$extension);

		$listing_hash = md5($listing_id.$extension);

		// Review media
		if($review_id && $listing_id)
		{
			if(!isset($this->__update_counts[$listing_hash]))
			{
				$this->updateListingCounts($listing_id, $extension);
			}

			if(!isset($this->__update_counts[$review_hash]))
			{
				$this->updateReviewCounts($review_id);
			}


		}
		// Listing media
		elseif($listing_id)
		{

			if(!isset($this->__update_counts[$listing_hash]))
			{
				$this->updateListingCounts($listing_id, $extension);
			}

		}

		$this->__update_counts[$review_hash] = true;

		$this->__update_counts[$listing_hash] = true;

		return true;
	}

	function updateListingCounts($listing_id = 0, $extension = '')
	{
		if($listing_id > 0 && $extension != '')
		{
			$where = 'WHERE listing_id = ' . $listing_id
				. ' AND extension = ' . $this->Quote($extension)
				. ' AND published = 1'
				. ' AND approved = 1'
				;

			$query = "
				INSERT INTO #__jreviews_listing_totals
					(listing_id, extension, media_count, video_count, photo_count, audio_count, attachment_count,
						media_count_user, video_count_user, photo_count_user, audio_count_user, attachment_count_user)
					SELECT
						$listing_id AS listing_id,
						'".$extension."' AS extension,
						COUNT(*) AS media_count,
						SUM(IF(media_type = 'video', 1, 0)) video_count,
						SUM(IF(media_type = 'photo', 1, 0)) photo_count,
						SUM(IF(media_type = 'audio', 1, 0)) audio_count,
						SUM(IF(media_type = 'attachment', 1, 0)) attachment_count,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0), 1, 0)) media_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'video', 1, 0)) video_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'photo', 1, 0)) photo_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'audio', 1, 0)) audio_count,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'attachment', 1, 0)) attachment_count
					FROM
						#__jreviews_media AS Media
					LEFT JOIN
						#__content AS Listing ON Listing.id = Media.listing_id AND Media.extension = 'com_content'
					{$where}
				ON DUPLICATE KEY UPDATE
					media_count = VALUES(media_count),
					video_count = VALUES(video_count),
					photo_count = VALUES(photo_count),
					audio_count = VALUES(audio_count),
					attachment_count = VALUES(attachment_count),
					media_count_user = VALUES(media_count_user),
					video_count_user = VALUES(video_count_user),
					photo_count_user = VALUES(photo_count_user),
					audio_count_user = VALUES(audio_count_user),
					attachment_count_user = VALUES(attachment_count_user)
			";
		}
		else {

			$query = "
				INSERT INTO #__jreviews_listing_totals
					(listing_id, extension, media_count, video_count, photo_count, audio_count, attachment_count,
						media_count_user, video_count_user, photo_count_user, audio_count_user, attachment_count_user)
					SELECT
						listing_id AS listing_id,
						extension AS extension,
						SUM(CASE
                     		WHEN published = 1 AND approved = 1 THEN 1
                     		ELSE 0
                   		END) AS media_count,
						SUM(IF(media_type = 'video' AND published = 1 AND approved = 1, 1, 0)) video_count,
						SUM(IF(media_type = 'photo' AND published = 1 AND approved = 1, 1, 0)) photo_count,
						SUM(IF(media_type = 'audio' AND published = 1 AND approved = 1, 1, 0)) audio_count,
						SUM(IF(media_type = 'attachment' AND published = 1 AND approved = 1, 1, 0)) attachment_count,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0), 1, 0)) media_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'video' AND published = 1 AND approved = 1, 1, 0)) video_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'photo' AND published = 1 AND approved = 1, 1, 0)) photo_count_user,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'audio' AND published = 1 AND approved = 1, 1, 0)) audio_count,
						SUM(IF((Media.user_id != Listing.created_by OR Media.user_id = 0) AND media_type = 'attachment' AND published = 1 AND approved = 1, 1, 0)) attachment_count
					FROM
						#__jreviews_media AS Media
					LEFT JOIN
						#__content AS Listing ON Listing.id = Media.listing_id AND Media.extension = 'com_content'
					GROUP
						BY listing_id, extension
					ORDER BY
						NULL
				ON DUPLICATE KEY UPDATE
					media_count = VALUES(media_count),
					video_count = VALUES(video_count),
					photo_count = VALUES(photo_count),
					audio_count = VALUES(audio_count),
					attachment_count = VALUES(attachment_count),
					media_count_user = VALUES(media_count_user),
					video_count_user = VALUES(video_count_user),
					photo_count_user = VALUES(photo_count_user),
					audio_count_user = VALUES(audio_count_user),
					attachment_count_user = VALUES(attachment_count_user)
			";
		}


		$result = $this->query($query);

		return $result;
	}

	function updateReviewCounts($review_id  = 0)
	{
		$where = '';

		if($review_id > 0)
		{
			$where = 'WHERE review_id = ' . $review_id
				. ' AND published = 1'
				. ' AND approved = 1'
			;

			$query = "
				INSERT INTO
					#__jreviews_comments
					(id, media_count, video_count, photo_count, audio_count, attachment_count)
					SELECT
						$review_id AS id,
						COUNT(*) AS media_count,
						SUM(IF(media_type = 'video', 1, 0)) video_count,
						SUM(IF(media_type = 'photo', 1, 0)) photo_count,
						SUM(IF(media_type = 'audio', 1, 0)) audio_count,
						SUM(IF(media_type = 'attachment', 1, 0)) attachment_count
					FROM
						#__jreviews_media
					{$where}
				ON DUPLICATE KEY UPDATE
					media_count = VALUES(media_count),
					video_count = VALUES(video_count),
					photo_count = VALUES(photo_count),
					audio_count = VALUES(audio_count),
					attachment_count = VALUES(attachment_count);
			";

		}
		else {

			$query = "
				INSERT INTO
					#__jreviews_comments
					(id, media_count, video_count, photo_count, audio_count, attachment_count)
					SELECT
						review_id AS id,
						COUNT(*) AS media_count,
						SUM(IF(media_type = 'video', 1, 0)) video_count,
						SUM(IF(media_type = 'photo', 1, 0)) photo_count,
						SUM(IF(media_type = 'audio', 1, 0)) audio_count,
						SUM(IF(media_type = 'attachment', 1, 0)) attachment_count
					FROM
						#__jreviews_media
					WHERE
						published = 1 AND approved = 1
					GROUP BY
						review_id
					HAVING
						review_id > 0
					ORDER BY
						NULL
				ON DUPLICATE KEY UPDATE
					media_count = VALUES(media_count),
					video_count = VALUES(video_count),
					photo_count = VALUES(photo_count),
					audio_count = VALUES(audio_count),
					attachment_count = VALUES(attachment_count);
			";
		}

		$result = $this->query($query);

		return $result;
	}

	function completeMediaUrls($media, $filename_url)
	{
		$media_type = $media['media_type'];

		$media_info = $media['media_info'] = json_decode($media['media_info'],true);

		$media['media_path'] = $filename_url;

		if(isset($media_info['video']) && !empty($media_info['video'])) {
			foreach($media_info['video'] AS $format=>$video) {
				$media['media_info']['video'][$format]['url'] = $filename_url . '.' . $format;
			}
		}

		if(isset($media_info['audio']) && !empty($media_info['audio'])) {
			foreach($media_info['audio'] AS $format=>$audio) {
				$media['media_info']['audio'][$format]['url'] = $filename_url . '.' . $format;
			}
		}

		$image = Sanitize::getVar($media_info,'image');

		$format = null;

		if($image) {

			$format = Sanitize::getString($image,'format');
		}

		if($format) {

			$media['media_info']['image']['url'] = $filename_url . '.' . $format;
		}

		if(isset($media_info['thumbnail']) && !empty($media_info['thumbnail']))
		{
			foreach($media_info['thumbnail'] AS $size_folder=>$thumbnail)
			{
				extract(parse_url($filename_url)); /* $scheme, $host, $path */

				$path = str_replace('/'.MEDIA_ORIGINAL_FOLDER.'/','/'.MEDIA_THUMBNAIL_FOLDER.'/' . $size_folder .'/',$path);

				// Rebuild url
				$tn_url = $scheme . '://' . $host . $path;

				$media['media_info']['thumbnail'][$size_folder]['url'] = $tn_url . '.' . $thumbnail['format'];
			}
		}

		return $media;
	}

	function completeEmbedUrls($media, $filename_url)
	{
		$media_type = $media['media_type'];

		$media_info = $media['media_info'] = json_decode($media['media_info'],true);

		$media['media_info']['video'] = array(
			$media['embed']=>array('url'=>$filename_url)
		);

		return $media;
	}

	function getListingInfo($media_id)
	{
		$query = '
			SELECT
				listing_id, review_id, extension, Listing.created_by AS owner_id
			FROM
				#__jreviews_media AS Media
			LEFT JOIN
				#__content AS Listing ON Listing.id = Media.listing_id
			WHERE
				media_id = ' . (int) $media_id . '
		';

		return $this->query($query,'loadAssoc');
	}


	function addMedia($results, $modelName, $mediaKey, $params)
	{
//		if(Sanitize::getString($params,'controller') == 'com_content' && Sanitize::getString($params,'action') == 'com_content_view') {
			return $this->addMediaDetail($results, $modelName, $mediaKey, $params);
//		}
//		else {
//			return $this->addMediaList($results, $modelName, $mediaKey, $params);
//		}
	}

	function addMediaDetail($results, $modelName, $mediaKey, $params)
	{
		$owner_id = null;

		$claim_approved = false;

		$photo_limit_number = Sanitize::getVar($params,'photo_limit');

		$video_limit_number = Sanitize::getVar($params,'video_limit');

		$photo_limit = 'BETWEEN 1 AND ' . $photo_limit_number;

		$video_limit = 'BETWEEN 1 AND ' . $video_limit_number;

		$photo_layout = Sanitize::getString($params,'photo_layout');

		$video_layout = Sanitize::getString	($params,'video_layout');

		$sort = Sanitize::getString($params,'sort','liked');

		$controller = Sanitize::getString($params,'controller');

		$action = Sanitize::getString($params,'action');

		$extension = Sanitize::getString($params,'extension');

		$menu_id = $publishedAndApproved = '';

		$disable_overrides = Sanitize::getInt($params,'disable_overrides');

		// In Reviews Module, use the Listing Main media thumbnail. No need to load the review media.
//		if($modelName == 'Review' && $controller == 'module_reviews') {
//			return $results;
//		}

		$object_ids = $mediaArray = $_StorageEngines = array();

        $Config = Configure::read('JreviewsSystem.Config');

		$ConfigComponent = ClassRegistry::getClass('ConfigComponent');

		$ConfigComponent->startup();

		$separate_owner_media = $ConfigComponent->media_detail_separate_media;

		$use_catch_all = Sanitize::getBool($Config,'media_url_catchall');

		$Menu = ClassRegistry::getClass('MenuModel');

		$menu_id_catchall = $Menu->getMenuIdByAction(101); // Media Catch-All menu

        // First get the object ids
        foreach($results AS $key=>$row)
        {
        	if(isset($row['ListingType']) && !$disable_overrides) {

				$photo_layout = $ConfigComponent->getOverride('media_detail_photo_layout',$row['ListingType']['config']);

				$video_layout = $ConfigComponent->getOverride('media_detail_video_layout',$row['ListingType']['config']);
        	}

			// In detail pages check for inpage gallery settings to ignore the photo and video limits if necessary
			if($controller == 'com_content' && $action == 'com_content_view' && $modelName == 'Listing')
			{
//		$attachment_limit = Sanitize::getInt($params,'attachment_limit'); // All attachments are shown in detail page / review layout

//		$audio_limit = Sanitize::getInt($params,'audio_limit'); // All audio is shown in detail page / review layout

				if(!$disable_overrides) {

					$separate_owner_media = $ConfigComponent->getOverride('media_detail_separate_media',$row['ListingType']['config']);

					$photo_limit_number = $ConfigComponent->getOverride('media_detail_photo_limit',$row['ListingType']['config']) + 1;

					$video_limit_number = $ConfigComponent->getOverride('media_detail_video_limit',$row['ListingType']['config']) + 1;
				}

				$photo_limit = $photo_limit_number == '' ? ' >= 0' : ' BETWEEN 1 AND ' . (int) $photo_limit_number;

				$video_limit = $video_limit_number == '' ? ' >= 0' : ' BETWEEN 1 AND ' . (int) $video_limit_number;

				if($row['Listing']['photo_count'] == 1) {
					$row['ListingType']['config']['photo_layout'] = 'contact_lightbox';
				}

				(in_array($photo_layout, array('contact_lightbox', 'gallery_small','gallery_large', 'film_lightbox'))) and $photo_limit = ' >= 0';

				(in_array($video_layout, array('contact_lightbox','video_player', 'film_lightbox'))) and $video_limit = ' >= 0';

				$owner_id = $separate_owner_media ? $row['User']['user_id'] : null;

				$claim_approved = Sanitize::getBool($row['Claim'],'approved');
			}
			elseif($modelName == 'Review') {
//		$attachment_limit = Sanitize::getInt($params,'attachment_limit'); // All attachments are shown in detail page / review layout

//		$audio_limit = Sanitize::getInt($params,'audio_limit'); // All audio is shown in detail page / review layout

				$photo_limit_number = Sanitize::getInt($Config,'media_review_photo_limit',0);

				$video_limit_number =  Sanitize::getInt($Config,'media_review_video_limit',0);

				$photo_limit = $photo_limit_number == '' ? ' >= 0' : ' BETWEEN 1 AND ' . (int) $photo_limit_number;

				$video_limit = $video_limit_number == '' ? ' >= 0' : ' BETWEEN 1 AND ' . (int) $video_limit_number;

				(in_array($photo_layout, array('contact_lightbox', 'gallery_small','gallery_large', 'film_lightbox'))) and $photo_limit = ' >= 0';

				(in_array($video_layout, array('contact_lightbox','video_player', 'film_lightbox'))) and $video_limit = ' >= 0';
			}

			if($use_catch_all && $menu_id_catchall) {
				 $menu_id = $menu_id_catchall;
			}
			elseif(isset($row['Category']) && Sanitize::getInt($row['Category'],'menu_id')) {
				 $menu_id = Sanitize::getInt($row['Category'],'menu_id');
			}

			$menu_id == 0 and $menu_id = '';

			if(isset($row[$modelName][$mediaKey]))
			{
				if($row[$modelName]['media_count'] > 0) {

	                 $object_ids[$row[$modelName][$mediaKey]] = $row[$modelName][$mediaKey];
				}
            }
			// For Reviews module, check if Media key already set so we don't overwrite the listing media
			if(!isset($row['Media']))
			{
				$results[$key]['Media'] = array(); // Initialize the Media array

				$results[$key]['MainMedia']['media_type'] = 'photo';

				// In reviews of me plugin the Listing key is not set because it's not necessary
				if(isset($row['Listing'])) {

					$results[$key]['MainMedia']['title'] = Sanitize::getString($row['Listing'],'title');
				}

				if(isset($row['Category']) && $params = Sanitize::getVar($row['Category'],'params'))
				{
					$params = json_decode($params,true);

					$cat_image = Sanitize::getString($params,'image');

					$cat_image != '' and $results[$key]['MainMedia']['category'] = $cat_image;
				}

				// Everywhere Add-on Listing images
				if(isset($row['Listing']) && Sanitize::getString($row['Listing'],'extension','com_content') != 'com_content' && !empty($row['Listing']['images'])) {

					$results[$key]['MainMedia']['everywhere'] = $row['Listing']['images'][0]['path'];
				}
			}
        }

		if(!empty($object_ids))
        {
			if(!defined('MVC_FRAMEWORK_ADMIN')) {
				$publishedAndApproved = 'AND published = 1 AND approved = 1';
			}

			// Add access condition to enforce media view access levels

			$Access = Configure::read('JreviewsSystem.Access');

			$access_level = 'AND access IN (' . $Access->getAccessLevels() . ')';

			switch($sort)
			{
				case 'popular':
					$order_inner = 'main_media DESC, '.$mediaKey.', media_type, views DESC';
					$order_outer = 'tmp.main_media DESC, tmp.'.$mediaKey.', tmp.media_type, tmp.views DESC';
				break;
				case 'liked':
					$order_inner = 'main_media DESC, '.$mediaKey.', media_type, likes_rank DESC';
					$order_outer = 'tmp.main_media DESC, tmp.'.$mediaKey.', tmp.media_type, tmp.likes_rank DESC';
				break;

				case 'newest':
					$order_inner = 'main_media DESC, '.$mediaKey.', media_type, created DESC';
					$order_outer = 'tmp.main_media DESC, tmp.'.$mediaKey.', tmp.media_type, tmp.created DESC';
				break;

				case 'ordering':
					$order_inner = 'main_media DESC, '.$mediaKey.', media_type, ordering';
					$order_outer = 'tmp.main_media DESC, tmp.'.$mediaKey.', tmp.media_type, tmp.ordering';
				break;
				default:
					$order_inner = 'main_media DESC, '.$mediaKey.', media_type';
					$order_outer = 'tmp.main_media DESC, tmp.'.$mediaKey.', tmp.media_type';
				break;
			}

			if(($controller == 'com_content' && $action == 'com_content_view')
				||
				($controller == 'everywhere' && $action == 'index')
				||
				($controller  == 'discussions' && $action == 'review' && $modelName == 'Review')
				||
				($controller  == 'listings' && $action == 'detail' && $modelName == 'Review')
				||
				($controller  == 'reviews' && $action == 'latest_user' && $modelName == 'Review')
				||
				($controller  == 'reviews' && $action == 'latest_editor' && $modelName == 'Review')
				||
				($controller  == 'reviews' && $action == 'latest' && $modelName == 'Review')
				||
				($controller  == 'reviews' && $action == 'myreviews' && $modelName == 'Review')
				||
				($controller  == 'reviews' && $action == '_save' && $modelName == 'Review')
			) {
				// owner separated media condition
				$owner_media_condition = '';

				if($owner_id) {

					// For claimed listings we can show the listing owner media as listing owned media, otherwise only show it in reviews
					if($claim_approved) {

						$owner_media_condition = "AND (user_id = $owner_id OR main_media = 1)";
					}
					else {

						$owner_media_condition = $owner_id ? "AND ((review_id = 0 AND user_id = $owner_id) OR main_media = 1)" : '';
					}
				}

				// media query limits the number of entries shown by certain media types based on settings
				$query = "
					SELECT
						tmp.media_id,
						tmp.listing_id,
						tmp.review_id,
						tmp.media_type,
						tmp.likes_rank,
						tmp.main_media,
						media.likes_up,
						media.likes_total,
						media.created,
						media.listing_id,
						media.review_id,
						media.user_id,
						media.filename,
						media.file_extension,
						media.rel_path,
						media.title,
						media.description,
						media.duration,
						media.media_info,
						media.metadata,
						media.embed,
						media.published,
						media.approved,
						media.access,
						media.views,
						media.filesize,
						media.extension
					FROM (
						SELECT
						    media_id,
						    listing_id,
						    review_id,
							media_type,
							main_media,
							views,
							likes_rank,
							created,
							ordering,
						    CASE
						      WHEN @id != {$mediaKey} AND media_type = 'photo' " . $owner_media_condition ." THEN @photo := 1
						      ELSE IF(media_type = 'photo' " . $owner_media_condition .",@photo := @photo + 1,NULL)
						    END AS photo_rank,
						    CASE
						      WHEN @id != {$mediaKey} AND media_type = 'video' " . $owner_media_condition ." THEN @video := 1
						      ELSE IF(media_type = 'video' " . $owner_media_condition .",@video := @video + 1,NULL)
						    END AS video_rank,
						    CASE
						      WHEN @id != {$mediaKey} AND media_type = 'attachment' THEN @attachment := 1
						      ELSE IF(media_type = 'attachment',@attachment := @attachment + 1,NULL)
						    END AS attachment_rank,
						    CASE
						      WHEN @id != {$mediaKey} AND media_type = 'audio' THEN @audio := 1
						      ELSE IF(media_type = 'audio',@audio := @audio + 1,NULL)
						    END AS audio_rank,
						    @id := {$mediaKey}
						FROM (
							SELECT
								media_id, review_id, listing_id, user_id, media_type, likes_rank, likes_up, likes_total, created, main_media, ordering, views
							FROM
								#__jreviews_media
							WHERE

								".$mediaKey." IN (".implode(',',$object_ids). ")

								".$publishedAndApproved."

								".$access_level."

								" . ($extension != ''? "AND extension = " . $this->Quote($extension) : "") . "


						) mtmp
  						JOIN (SELECT @id := NULL, @photo := 0, @video := 0, @attachment := 0, @audio := 0) r
						ORDER BY
							".$order_inner."
					) tmp

					JOIN
						#__jreviews_media AS media ON media.media_id = tmp.media_id

					WHERE
						(tmp.photo_rank {$photo_limit}) OR
						(tmp.video_rank {$video_limit}) OR
						(tmp.attachment_rank > 0) OR
						(tmp.audio_rank > 0)

					ORDER BY
						" . $order_outer . "
				";

//						tmp.rank_attachment <= {$attachment_limit} OR
//						tmp.rank_audio <= {$audio_limit}
			}
			else {

				// Retrieves only the main media for each listing
				$query = "
					SELECT
						media.media_id,
						media.media_type,
						media.likes_rank,
						media.likes_up,
						media.likes_total,
						media.created,
						media.main_media,
						media.listing_id,
						media.review_id,
						media.user_id,
						media.filename,
						media.file_extension,
						media.rel_path,
						media.title,
						media.description,
						media.media_info,
						media.metadata,
						media.embed,
						media.published,
						media.approved,
						media.access,
						media.views,
						media.filesize,
						media.extension
					FROM
						#__jreviews_media AS media
					WHERE

						".$mediaKey." IN (".implode(',',$object_ids). ")

						".$publishedAndApproved."

						".$access_level."

						AND main_media = 1
				";

			}

			$media = $this->query($query,'loadAssocList');

			$MediaStorage = Configure::read('Media.storage');

			// Group media by object id
			foreach($media AS $media_id=>$row)
			{
				$object_id = $row[$mediaKey];

				if($row['media_type'] == 'video' && $row['embed'] != '') {
					$media_type = 'video_embed';
				}
				else {
					$media_type = $row['media_type'];
				}

				$filename_url = $MediaStorage->getStorageUrl($media_type, $row['rel_path'] . $row['filename'], array('cdn'=>true));

				$row = $this->completeMediaUrls($row, $filename_url);

				isset($row['metadata']) and $row['metadata'] = json_decode($row['metadata'],true);

				$row['menu_id'] = $menu_id;

				if($row['main_media'] && $modelName == 'Listing') {
					$mediaArray[$object_id]['MainMedia'] = array_merge($results[$key]['Media'],$row);
				}
				// In review module, use the firt photo or video thumb as the main image
				elseif(in_array($row['media_type'],array('video','photo')) && $controller == 'module_reviews' && $modelName == 'Review' && count($mediaArray) == 0) {
					$mediaArray[$object_id]['MainMedia'] = array_merge($results[$key]['Media'],$row);
				}
				else {
					$mediaArray[$object_id]['Media'][$row['media_type']][] = $row;
				}
			}

			foreach($results AS $key=>$row)
            {
				if(isset($row['Listing']) && Sanitize::getInt($row['Listing'],'photo_count') == 1) {
					$results[$key]['ListingType']['config']['photo_layout'] = 'contact_lightbox';
				}

				isset($results[$key]['MainMedia']) and $results[$key]['MainMedia']['menu_id'] = $menu_id;

				if(isset($mediaArray[$key]))
                {
                	$results[$key] = array_insert($results[$key],$mediaArray[$key]);
                }

				// Unset the MainMedia key if a category or everywhere image is not present
				if(!isset($results[$key]['MainMedia']['media_info'])
					&& !isset($results[$key]['MainMedia']['everywhere'])
					&& !isset($results[$key]['MainMedia']['category'])) {

					unset($results[$key]['MainMedia']);
				}
            }
        }

		return $results;
	}

	/**
	 * Updates media ordering
	 * @param  array $ordering array of media ids in the desired new ordering
	 * @return boolean
	 */
	function reorderMedia($ordering)
	{
		$query = "
			UPDATE
				#__jreviews_media SET ordering = CASE media_id
		";

		foreach ($ordering AS $order=>$media_id)
		{
			$media_ids[] = (int) $media_id;
		    $query .= sprintf("WHEN %d THEN %d ", (int) $media_id, $order);
		}

		$media_ids = implode(',', $media_ids);

		$query .= "END WHERE media_id IN ($media_ids)";

		return $this->query($query);
	}

	function setMainMedia($listing_id, $media_id)
	{
		$query = "
			UPDATE #__jreviews_media
				SET main_media = CASE media_id
					WHEN " .(int) $media_id." THEN 1
					ELSE 0
				END
			WHERE listing_id = " . (int) $listing_id . "
		";

		$this->_db->setQuery($query);

		if($this->_db->query()) {
			return true;
		}

		return false;
	}

	/**
	 * Modifies the ORDER BY condition for the query
	 * @param type $order
	 */
	function processSorting($order = '')
	{
		switch($order)
		{
			case 'ordering':
				$this->order[] = '`Media.media_type`, `Media.ordering`';
				break;
			case 'oldest':
				$this->order[] = '`Media.created` ASC';
				break;
			case 'popular':
				$this->order[] = '`Media.views` DESC';
				break;
			case 'liked':
				$this->order[] = '`Media.likes_rank` DESC';
				break;
			case 'newest':
			case 'recent':
			default:
				$this->order[] = '`Media.created` DESC';
				break;
		}
	}

	function getListingPublishedUploads($listing_id, $review_id, $extension, $media_type) {

		$conditions = array();

		$conditions[] = $review_id > 0 ? 'review_id = ' . (int) $review_id : 'listing_id = ' . (int) $listing_id;

		$conditions[] = 'extension =  ' . $this->Quote($extension);

		$media_type and $conditions[] = 'media_type = ' . $this->Quote($media_type);

		$conditions[] = 'published = 1';

		$query = "
			SELECT
				count(*) AS count
			FROM
				#__jreviews_media
			WHERE
			" . implode (" AND ", $conditions) . "
		";

		$count = $this->query($query, 'loadResult');

		return $count;

	}

	function getUserUploads($user_id, $extension, $listing_id, $review_id, $media_type = null)
	{
		$conditions = array();

		$conditions[] = $review_id > 0 ? 'review_id = ' . (int) $review_id : 'listing_id = ' . (int) $listing_id;

		if($user_id > 0)
		{
			$conditions[] = 'user_id = ' . (int) $user_id;
		}
		else {

			$conditions[] = 'INET_NTOA(ipaddress) = ' . $this->Quote(s2GetIpAddress());
		}

		$conditions[] = 'extension =  ' . $this->Quote($extension);

		$media_type and $conditions[] = 'media_type = ' . $this->Quote($media_type);

		$query = "
			SELECT
				media_type, count(*) AS count
			FROM
				#__jreviews_media
			WHERE
			" . implode (" AND ", $conditions) . "
			GROUP BY media_type
		";

		$counts = $this->query($query, 'loadAssocList', 'media_type');

		if($media_type)
		{
			return isset($counts[$media_type]) ? Sanitize::getInt($counts[$media_type],'count') : 0;
		}

		return $counts;
	}

}
