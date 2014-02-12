<?php
/**
 * GeoMaps Addon for JReviews
 * Copyright (C) 2006-2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminGeomapsUninstallController extends MyController {

    var $autoLayout = false;
    var $autoRender = false;

    function uninstall()
    {
        $db = cmsFramework::getDB();
        // Delete GeoMaps module
        $query = "DELETE FROM #__modules WHERE module = 'mod_jreviews_geomaps'";
        $db->setQuery($query);
        $db->query();

        $query = "DELETE FROM #__extensions WHERE name = 'mod_jreviews_geomaps'";
        $db->setQuery($query);
        $db->query();

        // Remove GeoMaps module files
        $target = PATH_ROOT . 'modules' . DS . 'mod_jreviews_geomaps';

        $Folder = new S2Folder();

        if(@$Folder->delete($target))
        {
            return '<div style="color:green;">GeoMaps Module successfully uninstalled.</div>';
        } else {
            return '<div style="color:red;">There was a problem uninstalling the GeoMaps module.</div>';
        }
    }
}