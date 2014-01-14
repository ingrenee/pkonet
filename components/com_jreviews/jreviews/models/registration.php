<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RegistrationModel extends MyModel  {

	var $name = 'Registration';

	var $useTable = '#__jreviews_registration AS `Registration`';

	var $primaryKey = 'Registration.id';

	var $realKey = 'id';

	function afterSave($ret)
	{
		$model_name = Sanitize::getString($this->data,'model');

		if($model_name == 'Media') {

			$session_id = Sanitize::getString($this->data['Registration'],'session_id');

			$user_id = Sanitize::getString($this->data['Registration'],'user_id');

			$name = Sanitize::getString($this->data,'name');

			$email = Sanitize::getString($this->data,'email');

			$query = "

				UPDATE
					#__jreviews_media
				SET
					user_id = " . (int) $user_id . "
					,name = " . $this->Quote($name) . "
					,email = " . $this->Quote($email) . "
				WHERE
					user_id = 0
					AND media_id IN (

							SELECT
								media_id
							FROM
								#__jreviews_registration
							WHERE
								session_id = " . $this->Quote($session_id) . "
						)

			";

			$this->query($query);

		}
	}

	function clearTable($expiration = 3600)
	{
		$time = time();

		$query = "
			DELETE
				FROM #__jreviews_registration
			WHERE
				session_time < " . ( $time - $expiration )
		;

		$this->query($query);
	}

	/**
	 * Checks it the current guest user's session id matches the id of the submitted item
	 * @param  array $ids associative array with item id and id value array('media_id'=>100)
	 * @return boolean      validation passed/failed
	 */
	function validateGuestSession($ids) {

		$where = array();

		$session_id = session_id();

		foreach($ids AS $column=>$value)
		{
			$where[] = $column . ' = ' . $value;
		}

		$query = "
			SELECT
				COUNT(*)
			FROM
				#__jreviews_registration
			WHERE
				session_id = " . $this->Quote($session_id) . "
				AND
			" . implode(' AND ', $where) . "
		";

		$count = $this->query($query,'loadResult');

		return $count > 0;
	}
}
