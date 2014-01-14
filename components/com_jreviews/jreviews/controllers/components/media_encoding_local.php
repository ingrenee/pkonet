<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * There is no support for Local video encoding
 */
class MediaEncodingLocalComponent extends S2Component {
	
	function __construct(&$controller) {
		parent::__construct();
	}
	
	/**
	 * @param type $input local file path
	 * @param type $target remote file name
	 * @return type remote path
	 */
	function startJob($options, &$result) 
	{
		return array(
			'encoding_job'=>true,
			'state'=>'finished'
		);
	}
}