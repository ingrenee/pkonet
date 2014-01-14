<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


(defined('MVC_FRAMEWORK') || defined('JPATH_BASE')) or die( 'Direct Access to this location is not allowed.' );

class cmsFramework extends cmsFrameworkJoomla
{
    public static function getConnection()
    {
        $db = &JFactory::getDBO();
        return $db->_resource;
    }

    public static function getTemplate(){
        global $mainframe;
        return $mainframe->getTemplate();
    }

    public static function addScript($text, $inline=false, $duress = false)
    {
        $scripts = ClassRegistry::getObject('scripts');

        if($text != '' && ($duress || !isset($scripts[md5($text)])))
        {
            if($inline)
            {
                echo $text;

            } else
            {
                global $mainframe;
                $mainframe->addCustomHeadTag($text);
            }

            $scripts[md5($text)] = true;
            ClassRegistry::setObject('scripts',$scripts);
        }
    }

	public static function language()
    {
        $lang = & JFactory::getLanguage();
        return $lang->getBackwardLang();
    }

	/**
	 * Get url language code
	 */
	public static function getUrlLanguageCode()
	{
		return Sanitize::getString($_REQUEST,'lang');
	}

    public static function getIgnoredSearchWords()
    {
        $search_ignore = array();
        $lang = JFactory::getLanguage();
        $tag = $lang->getTag();

        if(method_exists($lang,'getIgnoredSearchWords'))
        {
            $search_ignore = $lang->getIgnoredSearchWords();
            $ignoreFile = $lang->getLanguagePath().DS.$tag.DS.$tag.'.ignore.php';
            if (file_exists($ignoreFile)) {
                include $ignoreFile;
            }
        }
        return $search_ignore;
    }

    public static function noAccess()
    {
        echo JText::_('ALERTNOTAUTH');
    }

    /**
    * Overrides CMSs breadcrumbs
    * $paths is an array of associative arrays with keys "name" and "link"
    */
    public static function setPathway($crumbs)
    {
        global $mainframe;
        foreach($crumbs AS $key=>$crumb)
        {
            $crumbs[$key] = (object)$crumb;
        }
        $pathway =& $mainframe->getPathway();
        $pathway->setPathway($crumbs);
    }

    public static function UrlTransliterate($string)
    {
        return JFilterOutput::stringURLSafe($string);
    }
}
