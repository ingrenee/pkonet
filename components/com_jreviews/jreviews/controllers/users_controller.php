<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UsersController extends MyController {

    var $uses = array('menu','user');

    var $helpers = array();

    var $components = array('config','access');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function _getUsername()
    {
        $user_id = Sanitize::getInt($this->params,'id');

        $query = "
            SELECT
                username
            FROM
                #__users
            WHERE
                id = " . $user_id
        ;

        $username = $this->User->query($query,'loadResult');

        return $username;
    }

    function _getList()
    {
        $limit = Sanitize::getInt($this->params,'limit',15);

        $q = $this->User->makeSafe(mb_strtolower(Sanitize::getString($this->params,'q'),'utf-8'));

        if (!$q) return '[]';

        $query = "
            SELECT
                id AS id, username AS value, name AS name, CONCAT(username,' (',name,')') AS label, email
            FROM
                #__users
            WHERE
                name LIKE " . $this->QuoteLike($q) . "
                OR
                username LIKE " . $this->QuoteLike($q) . "
            LIMIT {$limit}
        ";

        $users = $this->User->query($query,'loadObjectList');

        return cmsFramework::jsonResponse($users);
    }

    function _validateUsername()
    {
        $username = Sanitize::getString($this->params,'username');

        if($username != '') {
            $count = $this->User->findCount(array('conditions'=>array(
                    'username = ' . $this->Quote($username)
                )));

            if($count == 0) {
                $success = true;
                $text = JreviewsLocale::getPHP('USERNAME_VALID');
            }
            else {
                $success = false;
                $text = JreviewsLocale::getPHP('USERNAME_INVALID');
            }

            return cmsFramework::jsonResponse(array('success'=>$success,'text'=>$text));
        }
    }
}
