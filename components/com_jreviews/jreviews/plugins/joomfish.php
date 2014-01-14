<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/**
 * This plugin allows for automatic translation of listing standard fields via the JReviews front-end listing editor
**/
 

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JoomfishComponent extends S2Component {
    
    var $plugin_order = 10;
    
    var $name = 'joomfish';
    
    var $published = false;
    
    var $autoPublishTranslation = true; // Joomfish setting
    
    function startup(&$controller)
    { 
        if(!defined('MVC_FRAMEWORK_ADMIN') && $controller->Config->joomfish_plugin == 1 && class_exists('JoomFishManager'))   
        {
            $this->c = & $controller;
            $this->published = true;
        } 
    }     
    
    function plgAfterSave(&$model)
    {
        switch($model->name)
        {
            case 'Discussion':
            break;   
            case 'Favorite':
            break;             
            case 'Listing':
                $this->_plgListingAfterSave($model);
            break;  
            case 'Review':
            break;  
            case 'Vote':
            break;  
        }
        
        $this->published = false;
    }      
    
    function plgBeforeDelete(&$model)
    {
        switch($model->name)
        {
            case 'Discussion':
            break;   
            case 'Listing':
            break;  
            case 'Review':
            break;  
        }
    }          
        
    function _plgListingAfterSave(&$model)
    {
        // Limit running this for new/edited listings. Not deletion of images or other listing actions.
        if($this->c->name == 'listings' && in_array($this->c->action,array('_save')))
        {
            include_once(PATH_ROOT . 'administrator' . DS. 'components' . DS . 'com_joomfish' . DS .'models' . DS . 'ContentObject.php');
            $lang    = & JFactory::getLanguage();
            $language = $lang->getTag();
            $jfm = &JoomFishManager::getInstance();
            $contentElement = $jfm->getContentElement( 'content' );
            $actContentObject = new ContentObject( $jfm->getLanguageID($language), $contentElement );
            $object = (object) $model->data['Listing'];
            $actContentObject->loadFromContentID( $object->id );
            $actContentObject->copyContentToTranslation( $object, $object );
            $actContentObject->setPublished($this->autoPublishTranslation); //Automatically publishes the translations
            $actContentObject->store();
            if ($jfm->getCfg("transcaching",1))
            { // clean the cache!
                $cache = $jfm->getCache($language);
                $cache->clean();
            }          
        }
    }
}
