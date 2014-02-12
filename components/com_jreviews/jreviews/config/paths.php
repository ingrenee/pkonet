<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$basePaths = array(
    PATH_ROOT . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'libs' . DS,
    PATH_ROOT . 'components' . DS . 'com_'.$app . DS. $app . DS
);

$plugins_folder =  PATH_ROOT . 'components' . DS . 'com_jreviews_addons' . DS;

$d = @dir($plugins_folder);
$docs = array();
if($d)
{
    while (FALSE !== ($entry = $d->read()))
    {
        //$filename = $entry;
        if( substr($entry,0,1) != '.' && $entry != 'index.html' && !strstr($entry,'bak') && !strstr($entry,'ioncube'))
        {
            $basePaths[] = $plugins_folder . $entry . DS;
        }
    }
    $d->close();
}

$basePaths['overrides'] =  PATH_ROOT . 'templates' . DS . 'jreviews_overrides' . DS;

$relativePaths = array(
    'Lib' => '',
    'Controller' => array(
        'controller', // s2framework
        'controllers',
        'controllers' . DS . 'cb_plugins',
        'controllers' . DS . 'community_plugins',
        'controllers' . DS . 'modules',
    ),
    'AdminController' => 'admin_controllers',
    'Component' => array(
        'controller' . DS . 'components', // s2framework
        'controllers' . DS . 'components'
    ),
    'AdminComponent' => 'admin_controllers' . DS . 'components',
    'Model' => array(
        'model', // s2framework
        'models',
        'models' . DS . 'community',
        'models' . DS . 'everywhere'
    ),
    'Helper' => array(
        'view' . DS . 'helpers', // s2framework
        'views' . DS . 'helpers'
    ),
    'AdminHelper' => 'views' . DS . 'admin' . DS . 'helpers',
    'Plugin' => 'plugins'
);

$themePath = 'views' . DS. 'themes';
$themePathAdmin = 'views' . DS . 'admin' . DS . 'themes';
$jsPath = 'views' . DS . 'js';
$jsPathAdmin = 'views' . DS . 'admin' . DS . 'js';
