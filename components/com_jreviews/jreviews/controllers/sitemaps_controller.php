<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

ini_set('memory_limit','500M');

class SitemapsControllerX extends MyController
{
    var $uses = array('user','menu','media');

    var $helpers = array('routes','html','text','jreviews','time','media','community');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    var $sm_url_limit = 10; // 50000

	function beforeFilter() {

        Configure::write('ListingEdit',false);

        # Call beforeFilter of MyController parent class
		parent::beforeFilter();
	}

    function getEverywhereModel() {
        return $this->Media;
    }
        function afterFilter() {}

    function video()
    {
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        $fname = Sanitize::getString($this->params,'fname','video').'.xml';

        $limit = Sanitize::getInt($this->params,'limit');

        $queryData = array(
            'conditions'=>array(
                'Media.published = 1',
                'Media.approved = 1',
                'Media.media_type = "video"'
            ),
            'order'=>'Media.media_id DESC'
        );

        // if($limit > 0) $queryData['limit'] = $limit;

        $count = $this->Media->findCount($queryData);

        $num_sm_files = ceil($count/$this->sm_url_limit);

        for($i = 0; $i < $num_sm_files; $i++) {

            $queryData['limit'] = $this->sm_url_limit;

            $queryData['offset'] = $i*$this->sm_url_limit;

            $videos = $this->Media->findAll($queryData);

            $this->set('videos',$videos);

            $out =  $this->render('sitemaps','video');

    echo '<textarea style="width:100%;height:500px">';
    echo $out;
    echo '</textarea>';

        }


return;
        $fpath = PATH_ROOT . 'tmp' . DS . $fname;

        $fpath_zip = $fpath.'.zip';

        $file = fopen($fpath, "w+");

        fputs($file, $out);

        fclose($file);

        $zip = new ZipArchive();

        if($zip->open($fpath_zip, ZIPARCHIVE::OVERWRITE) === true) {
            $zip->addFile($fpath, $fname);
            $zip->close();
        }
        else {
            echo 'cannot create zip';
        }
    }
}
