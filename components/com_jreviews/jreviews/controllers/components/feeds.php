<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FeedsComponent extends S2Component {

	var $encoding;
    var $layout = 'feeds';
    var $view;
    var $expiration = 3600;

	function startup(&$controller) {

        # Check feed cache
        if(Sanitize::getString($controller->params,'action')=='xml'){
            $this->useCached(S2_CACHE . DS .'views' . DS . 'jreviewsfeed_'.md5($controller->here).'.xml');
        }

        $this->encoding = cmsFramework::getCharset();
        $this->params = &$controller->params;
        $this->c = &$controller;
	}

    function saveFeed($filename="", $view) {

        if(Sanitize::getString($this->params,'action')!='xml'){
            return false;
        }

        $type='.'.Sanitize::getString($this->params,'type','rss2');

        $App = S2App::getInstance();

        if(!isset($App->jreviewsPaths['Theme'][$this->c->viewTheme][$this->layout][$view.$type.'.thtml']) &&

        !isset($App->jreviewsPaths['Theme']['default'][$this->layout][$view.$type.'.thtml']) ){
            return false;
        }

        $this->c->autoLayout = false;

        $this->c->autoRender = false;

        $rss = array(
            'title'=>$this->c->Config->rss_title,
            'link'=>WWW_ROOT,
            'description'=>$this->c->Config->rss_description,
            'image_url'=>WWW_ROOT . "images/stories/" . $this->c->Config->rss_image,
            'image_link'=>WWW_ROOT
        );

        $this->c->set(array(
            'encoding'=>$this->encoding,
            'rss'=>$rss
        ));

        $feedFile = fopen($filename, "w+");

        if ($feedFile) {

            $feed = $this->c->render($this->layout,$view.$type);

            fputs($feedFile,$feed);

            fclose($feedFile);

            echo $feed;
            die;

        } else {
            echo "<br /><b>Error creating feed file, please check write permissions.</b><br />";
            die;
        }
    }

    function useCached($filename="") {
        if (file_exists($filename) AND (time()-filemtime($filename) < $this->expiration)) {
            $this->redirect($filename);
        }
    }

    function redirect($filename) {
        Header("Content-Type: text/xml; charset=".$this->encoding."; filename=".basename($filename));
        Header("Content-Disposition: inline; filename=".basename($filename));
        readfile($filename, "r");
        die();
    }
}