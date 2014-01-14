<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InstallController extends MyController
{
    var $uses = array('menu','field');
    var $helpers = array('html');
    var $components = array();

    var $autoRender = false;
    var $autoLayout = false;
    var $layout = 'empty';

    # Run right after component installation
    function index()
    {
        $response = array();

        // Delete incorrect upgrade files
        $upgrade_path = PATH_ROOT . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'upgrades' . DS;

        $wrong_sql_files = array(
            'upgrade_build_2.4.8.0.sql',
            'upgrade_build_2.4.0.2.sql'
        );

        foreach($wrong_sql_files AS $wrong_file) {

            if(file_exists($upgrade_path . $wrong_file)) @unlink($upgrade_path . $wrong_file);
        }

        if(Sanitize::getString($this->params,'task')=='upgrade')
        {   // Where running the install script for upgrade we want a json object returned
            $this->autoLayout = false;

            $this->autoRender = false;

        }
        else {

            $this->autoLayout = true;

            $this->autoRender = true;
        }

        $this->name = 'install';

        # Create database tables
        // Start db upgrade logic
        $action = array();

        $action['db_install'] = true;

        $tables = $this->_db->getTableList();

        $dbprefix = cmsFramework::getConfig('dbprefix');

        $old_build = 0;

        // Get current version number
        $jreviewsxml = 'jreviews.xml';

        $xml = file(S2_CMS_ADMIN . $jreviewsxml);

        foreach($xml AS $xml_line) {

            if(strstr($xml_line,'version')) {

                $new_version = trim(strip_tags($xml_line));

                continue;

            }

        }

        $new_build = self::paddedVersion($new_version);

        if(is_array($tables) && in_array($dbprefix . 'jreviews_categories',array_values($tables)))
        {
            // Tables exist so we check the current build and upgrade accordingly, otherwise it's a clean install and no upgrade is necessary
            $query = "SELECT value FROM #__jreviews_config WHERE id = 'version'";

            $this->_db->setQuery($query);

            $old_version = trim(strip_tags($this->_db->loadResult()));

            if($old_version!='') {

                $old_build = self::paddedVersion($old_version);
            }

            if(Sanitize::getBool($this->params,'sql'))
            {
                $old_build = 0;
            }

           // prx($old_build . '<br/>' . $new_build) ;

           // Read upgrades folder
           $Folder = new S2Folder(S2Paths::get('jreviews','S2_APP') . 'upgrades');

           $exclude = array('.','jreviews.sql','jreviews.php','index.html');

           $files = $Folder->read(true,$exclude);

           $files = array_pop($files);

            try {

               foreach($files AS $file) {

                    // get the version number from the filename
                    $pathinfo = pathinfo($file);

                    $extension = $pathinfo['extension'];

                    $pathparts = explode('_',$pathinfo['filename']);

                    $version = self::paddedVersion(array_pop($pathparts));

                    $filepath = S2Paths::get('jreviews','S2_APP') . 'upgrades' . DS . $file;

                    if($version > $old_build) {

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

                    $filepath = S2Paths::get('jreviews','S2_APP') . 'upgrades' . DS . $file;

                    if($version > $old_build) {

                        if($extension == 'php') {

                            include($filepath);

                        }
                    }
               }
           }
           catch (Exception $e) {

                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

        }
        else
        {
            // It's a clean install so we use the whole jReviews sql file
            $sql_file = S2Paths::get('jreviews','S2_APP') . 'upgrades' . DS . 'jreviews.sql';

            $action['db_install'] = $this->__parseMysqlDump($sql_file,$dbprefix);


            // Run php updates
            $php_file = S2Paths::get('jreviews','S2_APP') . 'upgrades' . DS . 'jreviews.php';

            if(file_exists($php_file)) {
                include($php_file);
            }

        }

        # Update component id in pre-existing jReviews menus
        $query = "
            SELECT
                extension_id AS id
            FROM
                #__extensions
            WHERE
                element = '".S2Paths::get('jreviews','S2_CMSCOMP')."' AND type = 'component'
        ";

        $this->_db->setQuery($query);

        if($id = $this->_db->loadResult())
        {
                $query = "
                    UPDATE
                        `#__menu`
                    SET
                        component_id = $id
                    WHERE
                        type IN ('component','components')
                            AND
                        link LIKE 'index.php?option=".S2Paths::get('jreviews','S2_CMSCOMP')."%'
                ";

            $this->_db->setQuery($query);

            $this->_db->query();
        }

        # Update version number in the database
        S2App::import('Component','config','jreviews');
        $JReviewsConfig = ClassRegistry::getClass('ConfigComponent');
        $JReviewsConfig->store(array('version'=>$new_version));

        $action['plugin_install'] = true;//$this->_installPlugin();

        # Ensure that all field group names are slugs
        $query = "
            SELECT
                groupid, name
            FROM
                #__jreviews_groups
        ";

        $this->_db->setQuery($query);

        $groups = $this->_db->loadAssocList();

        if(!empty($groups)) {
            foreach($groups AS $group) {
                if(strpos($group['name'],' ')!== false) {
                    $name = cmsFramework::StringTransliterate($group['name']).$group['groupid'];
                    $query = "
                        UPDATE
                            #__jreviews_groups
                        SET
                            name = " . $this->Quote($name) . "
                        WHERE
                            groupid = " . $group['groupid']
                        ;
                    $this->_db->setQuery($query);
                    $this->_db->query();
                }
            }
        }

        $packages = $this->_installPackages();

        # Clear data and core caches
        clearCache('', '__data');

        clearCache('', 'core');

        //var_dump($action);

        if(Sanitize::getString($this->params,'task')=='upgrade')
        {
            $response = array('success'=>true,'html'=>'');

            if(!$action['db_install']) {

                $response['html'] = '<div class="jrError">There was a problem upgrading the database</div>';
            }

            // if(!$action['plugin_install']) {

            //     $response['html'] .= '<div class="jrError">There was a problem upgrading the JReviews plugin</div>';
            // }

            return json_encode($response);
        }

        $this->set(array(
            'action'=>$action,
            'packages'=>$packages
        ));
    }

    function _installPackages()
    {
        $packageLog = array();

        // Install additional packages
        $package_path = JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'packages' . DS;

        $packages = array(
            'modules'=>array(
                    'modules'. DS . 'mod_jreviews_advsearch'=>'Advanced Search Module',
                    'modules'. DS . 'mod_jreviews_directories'=>'Directories Module',
                    'modules'. DS . 'mod_jreviews_favorite_users'=>'Favorite Users Module',
                    'modules'. DS . 'mod_jreviews_fields'=>'Fields Module',
                    'modules'. DS . 'mod_jreviews_listings'=>'Listings Module',
                    'modules'. DS . 'mod_jreviews_media'=>'Media Module',
                    'modules'. DS . 'mod_jreviews_range'=>'Field Range Modules',
                    'modules'. DS . 'mod_jreviews_reviews'=>'Reviews Module',
                    'modules'. DS . 'mod_jreviews_totals'=>'Totals Module'
            ),
            'plugins'=>array(
                    'plugins' . DS . 'content' . DS .'jreviews'=>'JReviews Content Plugin',
                    'plugins' . DS . 'system' . DS .'jreviews_sef'=>'JReviews System SEF Plugin',
                    'plugins' . DS . 'community' . DS .'jreviews'=>'JReviews Activity Stream Plugin for JomSocial'
            )
        );

        $installer = new JInstaller;

        foreach($packages['modules'] AS $module=>$description)
        {
            $result = false;

            $package = $package_path . $module;

            if(file_exists($package))
            {
                $result = $installer->install($package);
            }

            $packageLog[] = array('name'=>$description,'status'=>$result,'type'=>'module');
        }

        foreach($packages['plugins'] AS $plugin=>$description)
        {
            $result = false;

            $package = $package_path . $plugin;

            if(file_exists($package))
            {
                $result = $installer->install($package);
            }

            $packageLog[] = array('name'=>$description,'status'=>$result,'type'=>'plugin');
        }

        // Publish the JReviews content plugin

        $query = "
            UPDATE
                #__extensions
            SET
                enabled = 1
            WHERE
                type = 'plugin' AND element = 'jreviews' AND folder = 'content'
        ";

        $this->_db->setQuery($query)->query();

        return $packageLog;
    }

    # Tools to fix installation problems any time
    function _installfix()
    {
        if(!class_exists('JreviewsLocale')) {

            require(S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'admin_locale.php' );
        }

        $task = Sanitize::getString($this->data,'task');

        $msg = '';

        switch($task) {

            case 'fix_content_fields':

                $output = '';

                $table = $this->Field->query(null,'getTableColumns','#__jreviews_content');

                $columns = isset($table['#__jreviews_content']) ? array_keys($table['#__jreviews_content']) : array_keys($table);

                $query = "
                    SELECT
                        name, type, maxlength
                    FROM
                        #__jreviews_fields
                    WHERE
                        location = 'content' AND type != 'banner'";

                $fields = $this->Field->query($query,'loadAssocList','name');

                foreach ($fields AS $field) {

                    if (!in_array($field['name'],$columns)) {

                        $output = $this->Field->addTableColumn($field,'content');
                    }
                }

                $query = "
                    DELETE
                    FROM
                        #__jreviews_fields
                    WHERE
                        name = ''";

                $output = $this->Field->query($query);

                if ($output != '') {

                    $msg = JreviewsLocale::getPHP('INSTALL_FIX_LISTING_FIELD_FAILED');
                }

                break;

            case 'fix_review_fields':

                $output = '';

                $table = $this->Field->query(null,'getTableColumns','#__jreviews_review_fields');

                $columns = isset($table['#__jreviews_review_fields']) ? array_keys($table['#__jreviews_review_fields']) : array_keys($table);

                $query = "
                    SELECT
                        name, type
                    FROM
                        #__jreviews_fields
                    WHERE
                        location = 'review' AND type != 'banner'";

                $fields = $this->Field->query($query,'loadAssocList','name');

                foreach ($fields AS $field) {

                    if (!in_array($field->name,$columns)) {

                        $output = $this->Field->addTableColumn($field,'review');
                    }
                }

                $query = "
                    DELETE
                    FROM
                        #__jreviews_fields
                    WHERE
                        name = ''";

                $output = $this->Field->query($query);

                if ($output != '') {

                    $msg = JreviewsLocale::getPHP('INSTALL_FIX_REVIEW_FIELD_FAILED');
                }

                break;

            default:
                break;
        }

        cmsFramework::redirect("index.php?option=com_jreviews",$msg);
    }
}
