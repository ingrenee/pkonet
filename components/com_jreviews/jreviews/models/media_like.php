<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2010 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaLikeModel extends MyModel {

    var $name = 'MediaLike';

    var $useTable = '#__jreviews_media_likes AS MediaLike';

    var $primaryKey = 'Media.media_id';

    var $realKey = 'media_id';

    var $fields = array(
		'MediaLike.*'
    );

    var $joins = array();

    var $conditions = array();

	function vote($media_id, $vote)
	{
		$media_id = (int) $media_id;
		$vote = (int) $vote;
		$created = $this->Quote(CURRENT_SERVER_TIME);
		$ipaddress = $_SERVER['REMOTE_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
		$User = cmsFramework::getUser();
		$user_id = (int) $User->id;
		$count = 0;

		// Check if user already voted for this media, ignore ip check for localhost
		$ipcheck = $ipaddress != '127.0.0.1' ? "OR (media_id = $media_id AND ipaddress = " . $this->Quote(ip2long($ipaddress)) . ")" : '';

		// Ignore user id checks for guests
		$usercheck = $user_id > 0 ? "AND user_id = {$user_id}" : '';

		if($ipcheck != '' || $usercheck != '')
		{
			$query = "
				SELECT
					count(*)
				FROM
					#__jreviews_media_likes
				WHERE
					(media_id = {$media_id} {$usercheck}) {$ipcheck}
			";

			$count = $this->query($query,'loadResult');
		}

		if($count == 0)
		{
			// Then insert
			$query = "
				INSERT INTO
					#__jreviews_media_likes
					(media_id, user_id, ipaddress, created, vote)
				VALUES
					($media_id, $user_id, INET_ATON(" . $this->Quote($ipaddress)."), $created, $vote)
			";

			$result = $this->query($query);

			if($result) {

				$this->updateCount($media_id);
			}

			$data = array(
				'media_id'=>$media_id,
				'vote'=>$vote
			);

			$this->data = $data;

			// Trigger afterSavePlugin
			$this->plgAfterSave($this);

			return $result;
		}

		return false;
	}

	/**
	 *
	 * @param type $media_id If passed only the current media totals are  updated
	 */
	function updateCount($media_id = null)
	{
		// Done in three steps because we can't determine max number of votes any media
		// has received until we've build likes_total for each media

		$query1 = "
			INSERT INTO
				#__jreviews_media (media_id, likes_up, likes_total)
				(SELECT
					media_id AS media_id,
					SUM(IF(vote = 1, 1, 0)) AS likes_up,
					COUNT(*) AS likes_total
				FROM #__jreviews_media_likes
				" . ($media_id > 0 ? "WHERE media_id = " . (int) $media_id : '') . "
				GROUP BY media_id
				ORDER BY NULL
				)
			ON DUPLICATE KEY UPDATE
				likes_up = VALUES(likes_up),
				likes_total = VALUES(likes_total);
		";

		if(!$media_id) {

			// Determine max_likes
			$query2 = "
				SELECT @max_likes := MAX(likes_total) FROM #__jreviews_media;
			";

			// UPDATE likes_rank
			$query3 = "
				INSERT INTO
					#__jreviews_media (media_id, likes_rank)
					(SELECT
						media_id AS media_id,
						(SUM(IF(vote = 1, 1,0))/COUNT(*)) * (COUNT(*)/@max_likes) * 16000000 AS likes_rank
					FROM
						#__jreviews_media_likes
					GROUP BY
						media_id
					ORDER BY
						NULL
					)
				ON DUPLICATE KEY UPDATE
						likes_rank = VALUES(likes_rank);
			";

		}

		if($this->query($query1)){

			if(!$media_id) {

				if($this->query($query2)) {

					$this->query($query3);
				}

			}

		}
	}
}
