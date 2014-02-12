<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 20010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaEncodingModel extends MyModel {

	var $name = 'MediaEncoding';

	var $useTable = '#__jreviews_media_encoding AS MediaEncoding';

	var $primaryKey = 'MediaEncoding.job_id';

	var $realKey = 'job_id';

	var $fields = array(
		'MediaEncoding.*',
		'Media.media_id AS `Media.media_id`',
		'Media.media_type AS `Media.media_type`',
		'Media.listing_id AS `Media.listing_id`',
		'Media.review_id AS `Media.review_id`',
		'Media.extension AS `Media.extension`',
		'Media.media_info AS `Media.media_info`'
	);

	var $joins = array('
		LEFT JOIN #__jreviews_media AS Media ON Media.media_id = MediaEncoding.media_id
	');

	function afterFind($results)
	{
		if(empty($results)) return;

		if(isset($results['Media'])) {
			$results['Media']['media_info'] = json_decode($results['Media']['media_info'],true);
			return $results;
		}

		foreach($results AS $key=>$result) {
			if(isset($result['Media'])) {
				$results[$key]['Media']['media_info'] = json_decode($result['Media']['media_info'],true);
			}
		}

		return $results;
	}

	function updateJobStatus($job_id, $response)
	{
		$data = array();

		$data['insert'] = false;

		$data['MediaEncoding']['job_id'] = $job_id;

		$data['MediaEncoding']['media_id'] = $response['media_id'];

		$data['MediaEncoding']['status'] = $response['state'];

		$data['MediaEncoding']['response'] = print_r($response['response'],true);

		$data['MediaEncoding']['updated'] = _CURRENT_SERVER_TIME;

		$this->update('#__jreviews_media_encoding','MediaEncoding',$data,'job_id');
	}

	function alreadyNotified($media_id)
	{
		$query = "
			SELECT
				notifications
			FROM
				#__jreviews_media_encoding
			WHERE
				media_id = " . (int) $media_id;

		$result = (int) $this->query($query,'loadResult');

		if($result == 0) {

			$query = "
				UPDATE
					#__jreviews_media_encoding
				SET
					notifications = 1
				WHERE
					media_id = " . (int) $media_id;

			$this->query($query,'query');

			return false;
		}

		return true;
	}
}
