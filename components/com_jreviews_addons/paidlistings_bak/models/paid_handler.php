<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidHandlerModel extends MyModel  {

    var $name = 'PaidHandler';

    var $useTable = '#__jreviews_paid_handlers AS `PaidHandler`';

    var $primaryKey = 'PaidHandler.handler_id';

    var $realKey = 'handler_id';

    var $fields = array('PaidHandler.*');

    var $order = array('PaidHandler.ordering ASC');

    function afterFind($results)
    {
        foreach($results AS $key=>$result)
        {
            if(isset($result['PaidHandler']['settings']))
            {
                $results[$key]['PaidHandler']['settings'] = json_decode($result['PaidHandler']['settings'],true);
            }
        }
        return $results;
    }

    function getList()
    {
        $query = "
            SELECT
                handler_id AS value, name AS text
            FROM
                #__jreviews_paid_handlers
            ORDER BY
                name ASC
        ";
        $this->_db->setQuery($query);
        return $this->_db->loadAssocList();
    }
}