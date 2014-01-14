<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityModel extends MyModel  {

    var $name = 'Community';

    var $useTable = '#__comprofiler AS Community';

    var $primaryKey = 'Community.id';

    var $realKey = 'id';

    var $community = false;

    var $profileUrl = 'index.php?option=com_comprofiler&amp;task=userProfile&amp;user=%s&amp;Itemid=%s';

    var $registerUrl = 'index.php?option=com_comprofiler&amp;task=registers&amp;Itemid=%s';

    var $menu_id;

    function __construct(){

        parent::__construct();

        Configure::write('Community.profileUrl',$this->profileUrl);

        if (file_exists(PATH_ROOT . 'components' . _DS . 'com_comprofiler' . _DS . 'comprofiler.php'))
        {
            $this->community = true;

            $Menu = ClassRegistry::getClass('MenuModel');

            $this->menu_id = $Menu->getComponentMenuId('com_comprofiler&task=registers',true);

            if(!$this->menu_id)
            {
                $this->menu_id = $Menu->getComponentMenuId('com_comprofiler', true); // 2nd parameter forces a LIKE '%com_comprofiler' query to find only the profile menu
            }

            Configure::write('Community.register_url',sprintf($this->registerUrl,$this->menu_id));
        }

    }

    function getListingFavorites($listing_id, $user_id, $passedArgs)
    {
        $avatar    = Sanitize::getInt($passedArgs['module'],'avatar',1); // Only show users with avatars
        $module_id = Sanitize::getInt($passedArgs,'module_id');
        $rand = Sanitize::getFloat($passedArgs,'rand');
        $limit = Sanitize::getInt($passedArgs['module'],'module_total',10);

        $fields = array(
            'Community.id AS `User.user_id`',
            'User.name AS `User.name`',
            'User.username AS `User.username`'
        );

        $conditions = array(
            'Community.approved = 1',
            'Community.confirmed = 1',
            'User.block = 0'
        );

        $avatar and $conditions[] = 'Community.avatar IS NOT NULL';

        $listing_id and $conditions[] = 'Community.id in (SELECT user_id FROM #__jreviews_favorites WHERE content_id = ' . $listing_id . ')';

        $order = array('RAND('.$rand.')');

        $joins = array('LEFT JOIN #__users AS User ON Community.id = User.id');

         $profiles = $this->findAll(array(
            'fields'=>$fields,
            'conditions'=>$conditions,
            'order'=>$order,
            'joins'=>$joins,
            'limit'=>$limit
        ));

        return $this->addProfileInfo($profiles,'User','user_id');
    }

    function __getOwnerIds($results, $modelName, $userKey) {

        $owner_ids = array();

        foreach($results AS $result) {
            // Add only if not guests
            if($result[$modelName][$userKey]) {
                $owner_ids[] = $result[$modelName][$userKey];
            }

        }

        return array_unique($owner_ids);
    }

    function addProfileInfo($results, $modelName, $userKey)
    {
        if(!$this->community) {
            return $results;
        }

        $owner_ids = $this->__getOwnerIds($results, $modelName, $userKey);

        if(empty($owner_ids)) {
            return $results;
        }

        unset($this->limit);
        unset($this->offset);
        $profiles = $this->findAll(array(
            'fields'=>array('*'),
            'conditions'=>array('user_id IN (' . implode(',',$owner_ids) . ')'),
        ));

        $profiles = $this->changeKeys($profiles,$this->name,$this->realKey);

        $menu_id = $this->menu_id;

        # Add avatar_path to Model results
        foreach ($profiles AS $key=>$value)
        {
            if(false === strpos($key,'banned'))
            {
                $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

                $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = '';

                if ($profiles[$value[$this->name][$userKey]][$this->name]['avatar'] != '' && $profiles[$value[$this->name][$userKey]][$this->name]['avatarapproved']) {

                    if (file_exists(PATH_ROOT .'images' . DS . 'comprofiler' . DS . 'tn' . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] )) {

                        $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT. 'images' ._DS . 'comprofiler' . _DS . 'tn' . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];
                    }
                    elseif (file_exists(PATH_ROOT .'images' . DS . 'comprofiler' . DS . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] )) {

                        $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT. 'images' ._DS . 'comprofiler' . _DS . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];
                    }
                }
            }

            $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

            $profiles[$value[$this->name][$userKey]][$this->name]['url'] = cmsFramework::route(sprintf($this->profileUrl,$value[$this->name]['user_id'],$menu_id));
        }

        # Add Community Model to parent Model
        foreach ($results AS $key=>$result) {

            if(isset($profiles[$results[$key][$modelName][$userKey]])) {
                $results[$key] = array_merge($results[$key], $profiles[$results[$key][$modelName][$userKey]]);
            }

            $results[$key][$this->name]['menu_id'] = $menu_id;

        }

        return $results;
    }
}