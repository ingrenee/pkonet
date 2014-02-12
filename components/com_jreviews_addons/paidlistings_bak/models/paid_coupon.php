<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidCouponModel extends MyModel  {

    var $name = 'PaidCoupon';

    var $useTable = '#__jreviews_paid_coupons AS `PaidCoupon`';

    var $primaryKey = 'PaidCoupon.coupon_id';

    var $realKey = 'coupon_id';

    var $fields = array('PaidCoupon.*');

    function afterFind($results)
    {
        $user_ids = array();
        foreach($results AS $key=>$result)
        {
            $coupon_user_ids = array_filter(explode(',',Sanitize::getString($result['PaidCoupon'],'coupon_users')));
            $user_ids = array_merge($user_ids,$coupon_user_ids);
            $results[$key]['PaidCoupon']['coupon_users'] = $coupon_user_ids;
            $results[$key]['PaidCoupon']['coupon_categories'] = array_filter(explode(',',$result['PaidCoupon']['coupon_categories']));
            $results[$key]['PaidCoupon']['coupon_plans'] = array_filter(explode(',',$result['PaidCoupon']['coupon_plans']));
        }
        $user_ids = array_filter(array_unique($user_ids));
        S2App::import('Model','user','jreviews');
        $UserModel = ClassRegistry::getClass('UserModel');
        !empty($user_ids) and $users = $UserModel->findAll(array(
            'fields'=>array('User.id AS `User.id`','User.name AS `User.name`'),
            'conditions'=>array('User.id IN (' .implode(',',$user_ids). ')' ),
            'order'=>array('User.name ASC')
        ));
        if(!empty($users))
        {
            foreach($results AS $key=>$result)
            {
                $results[$key]['User'] = $users;
            }
        }
        return $results;
    }

}
