<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPaidlistingsInstallController extends MyController {

    var $components = array('config');

    var $autoLayout = false;

    var $autoRender = false;

    function install()
    {
        $action = array('db_install'=>true);

        $db = cmsFramework::getDB();

        $old_version = Sanitize::getString($this->Config,'paid.version');

        ob_start();

        include(dirname(dirname(__FILE__)) . DS . 'paidlistings.info.php');

        ob_end_clean();

        $new_version = $_addon['version'];

        $version_parts = explode('.',$new_version);

        $new_version = self::paddedVersion($new_version);

        $tables = $db->getTableList();

        $dbprefix = cmsFramework::getConfig('dbprefix');

        if(is_array($tables) && in_array($dbprefix . 'jreviews_paid_plans',array_values($tables)))
        {
            if($old_version != '') {

                $old_version = self::paddedVersion($old_version);
            }

            if(isset($this->params) && Sanitize::getBool($this->params,'sql'))
            {
                $old_version = 0;
            }

            $addons_upgrade_path = S2Paths::get('jreviews','S2_ADDONS') . DS . 'paidlistings' . DS . 'upgrades';

            // Read upgrades folder
            $Folder = new S2Folder($addons_upgrade_path);

            $exclude = array('.','paidlistings.sql','paidlistings.php','index.html');

            $files = $Folder->read(true,$exclude);

            $files = array_pop($files);

            try {

               foreach($files AS $file) {

                    // get the version number from the filename
                    $pathinfo = pathinfo($file);

                    $extension = $pathinfo['extension'];

                    $pathparts = explode('_',$pathinfo['filename']);

                    $version = self::paddedVersion(array_pop($pathparts));

                    $filepath = $addons_upgrade_path . DS . $file;

                    if($version > $old_version) {

                        if($extension == 'sql') {

                            $action['db_install'] = $this->__parseMysqlDump($filepath,$dbprefix) && $action['db_install'];
                        }
                    }
               }

               foreach($files AS $file) {

                    // get the version number from the filename
                    $pathinfo = pathinfo($file);

                    $extension = $pathinfo['extension'];

                    $pathparts = explode('_',$pathinfo['filename']);

                    $version = self::paddedVersion(array_pop($pathparts));

                    $filepath = $addons_upgrade_path . DS . $file;

                    if($version > $old_version) {

                        if($extension == 'php') {

                            include($filepath);

                        }
                    }
                }

               if(!$action['db_install']) {

                    return '<div class="jrError">There was a problem updating the database.</div>';
               }

               $this->Config->store((object) array('paid.version'=>$new_version));

               return '<div class="jrSuccess">The add-on was successfully installed/updated.</div>';
            }
            catch (Exception $e) {

                return 'Caught exception: ' .  $e->getMessage() . "\n";
            }

        }
        else {

            # It's a clean install so we use the complete sql file
            $sql_file = dirname(dirname(__FILE__)) . DS . 'upgrades' . DS . 'paidlistings.sql';

            if($this->__parseMysqlDump($sql_file,$dbprefix))
            {
                $this->Config->store((object) array('paid.version'=>$new_version));

                return '<div class="jrSuccess">The add-on was successfully installed/updated.</div>';
            }
            else {

                return '<div class="jrError">There was a problem creating updating the database.</div>';
            }
        }
    }
}