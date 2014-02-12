<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidlistingsController extends MyController
{
    var $uses = array('menu','media','paid_plan','paid_plan_category','paid_handler','paid_order','paid_txn_log');

    var $components = array('config','access','everywhere','paidlistings_notifications','media_storage');

    var $helpers = array('routes','assets','time','html','form','paginator','media','widgets','community','rating','paid','paid_routes');

    var $autoRender = false;

    var $autoLayout = true;

    // Need to return object by reference for PHP4
    function &getObserverModel()
    {
        return $this->Listing;
    }

    function beforeFilter()
    {
        parent::beforeFilter();
    }

    function myaccount()
    {
        if(!$this->_user->id)
        {
            return $this->render('elements','login');
        }

        // Listings pending payment - Those that are neither active noror expired.
        $listings = $this->Listing->findAll(array(
            'joins'=>array(
                'INNER JOIN #__jreviews_paid_plans_categories AS PaidCategory ON Listing.catid = PaidCategory.cat_id',
                'RIGHT JOIN #__jreviews_paid_plans AS PaidPlan ON PaidPlan.plan_id = PaidCategory.plan_id AND PaidPlan.plan_type = 0 AND PaidPlan.plan_state = 1' // New listings
            ),
            'conditions'=>array(
                'Listing.created_by = ' . (int) $this->_user->id,
                '(
                    Listing.id NOT IN
                        (SELECT
                            PaidOrder.listing_id
                        FROM
                            #__jreviews_paid_orders AS PaidOrder
                        WHERE
							PaidOrder.order_status = "Incomplete"
							OR
                            PaidOrder.order_active = 1
                            OR
                            (PaidOrder.order_active = 0 AND DATEDIFF(DATE(PaidOrder.order_expires),CURDATE()) < 0)
                            OR
                            (PaidOrder.order_active = 0 AND PaidOrder.order_never_expires = 1)
                        )
                )',
            ),
            'order'=>array(
                'Listing.created DESC'
            )
        ));

        if($listings)
        {
            $listings = $this->PaidOrder->completeOrderInfo($listings);

            $listings = $this->PaidPlan->completePlanInfo($listings);

            $this->set(array('listings'=>$listings,'Access'=>$this->Access));
        }

        $orders_incomplete_array = $this->PaidOrder->findAll(array(
            'fields'=>array(
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE())' . ' AS `PaidOrder.daysToExpiration`',
                'PaidOrder.listing_id AS `Listing.listing_id`',
                'Listing.title AS `Listing.title`',
                'Listing.catid AS `Listing.cat_id`',
                'Listing.alias AS `Listing.slug`',
                'Listing.state AS `Listing.state`',
                '\'com_content\' AS `Listing.extension`',
                'Listing.publish_down AS `Listing.publish_down`',
                'Listing.publish_up AS `Listing.publish_up`',
                'Category.alias AS `Category.slug`'
            ),
            'conditions'=>array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'PaidOrder.order_status = "Incomplete"'
            ),
            'joins'=>array(
                'LEFT JOIN #__content AS Listing ON Listing.id = PaidOrder.listing_id',
                'LEFT JOIN #__categories AS Category ON Category.id = Listing.catid'
            )
        ));

        $expiration_period1 = Sanitize::getInt($this->Config,'paid.notify_expiration1_days',10);

        $orders_expiring_array = $this->PaidOrder->findAll(array(
            'fields'=>array(
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE())' . ' AS `PaidOrder.daysToExpiration`',
                'PaidOrder.listing_id AS `Listing.listing_id`',
                'Listing.title AS `Listing.title`',
                'Listing.catid AS `Listing.cat_id`',
                'Listing.alias AS `Listing.slug`',
                'Listing.state AS `Listing.state`',
                'Listing.publish_down AS `Listing.publish_down`',
                'Listing.publish_up AS `Listing.publish_up`',
                '\'com_content\' AS `Listing.extension`',
                'Category.alias AS `Category.slug`'
			),
            'conditions'=>array(
                'PaidOrder.user_id = ' . (int) $this->_user->id,
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE()) <= ' . $expiration_period1,
                'DATEDIFF(DATE(PaidOrder.order_expires),CURDATE()) > 0',
                'PaidOrder.order_active = 1',
                'PaidOrder.order_never_expires = 0',
                'PaidOrder.payment_type IN (0,2)', // Only one time payment orders expire
                'PaidOrder.order_id_renewal = 0'
            ),
            'joins'=>array(
                'LEFT JOIN #__content AS Listing ON Listing.id = PaidOrder.listing_id',
                'LEFT JOIN #__categories AS Category ON Category.id = Listing.catid'
            )
		));

        $orders_incomplete_array and $this->set('orders_incomplete',$orders_incomplete_array);

        $orders_expiring_array and $this->set('orders_expiring',$orders_expiring_array);

        // Extract listing ids which will be used to generate the listing urls
        $listing_ids = array();

        foreach($orders_incomplete_array AS $orders_incomplete) {

            $listing_ids[$orders_incomplete['PaidOrder']['listing_id']] = $orders_incomplete['PaidOrder']['listing_id'];
        }

        foreach($orders_expiring_array AS $orders_expiring) {

            $listing_ids[$orders_expiring['PaidOrder']['listing_id']] = $orders_expiring['PaidOrder']['listing_id'];
        }

		if(!empty($listing_ids)) {

            $listings2 = $this->Listing->findAll(
				array('conditions'=>'Listing.id IN ('.$this->quote($listing_ids).')'),
				array('afterFind')
			);

			$this->set('listings2',$listings2);
		}

        return $this->render('paid_account', 'index');
    }
}
