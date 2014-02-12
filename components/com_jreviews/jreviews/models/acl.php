<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AclModel extends MyModel  {

    function getAccessGroupList($groups = null)
    {
        /* Groupids reference */
    //    18 - Registered
    //    19 - Author
    //    20 - Editor
    //    21 - Published
    //    23 - Manager
    //    24 - Administrator
    //    25 - Super Administrator

        $whereGroups = $groups ? "\n AND id IN ($groups)" : "";

        $query = "
            SELECT
                id AS value, title AS text
            FROM
                #__usergroups
			WHERE 1=1 "
            . $whereGroups .
			" ORDER BY id ASC"
            ;

        return $this->query($query,'loadAssocList');
    }

	function getAccessLevelList()
	{
        $query = "
            SELECT
                id AS value, title AS text
            FROM
                #__viewlevels
			ORDER BY
				ordering
            ";

		return $this->query($query,'loadAssocList');

	}
}