<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidOrderModel extends MyModel  {

    var $name = 'PaidOrder';

    var $useTable = '#__jreviews_paid_orders AS `PaidOrder`';

    var $primaryKey = 'PaidOrder.order_id';

    var $realKey = 'order_id';

    var $fields = array('PaidOrder.*');

    var $status_count_array = array(
            'Failed'=>array(0,1,0),
            'Fraud'=>array(0,2,0),
            'Cancelled'=>array(0,3,0),
            'Incomplete'=>array(0,4,0),
            'Processing'=>array(0,5,0),
            'Pending'=>array(0,6,0),
            'Complete'=>array(0,7,0)
        );

	function addListingUrl($orders)
	{
        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');
        $Routes = ClassRegistry::getClass('RoutesHelper');
        foreach($orders AS $key=>$order)
        {
            if(isset($orders[$key]['Listing']) && isset($orders[$key]['Listing']['cat_id'])) {

				$menu_id_array = array(
					'cat_id'=>$order['Listing']['cat_id'],
					'dir_id'=>null,
					'listing'=>$order['Listing']['listing_id']
				);

				$orders[$key]['Listing']['menu_id'] = $Menu->getCategory($menu_id_array);

				$orders[$key]['Listing']['url'] = $Routes->content('',$orders[$key],array('return_url'=>true));
			}
        }

		return $orders;
	}

    function changeFeaturedState($listing_id, $new_state)
    {
        if(is_array($listing_id)) {
            $listing_id = implode(',',$listing_id);
        }

        if(empty($listing_id)) return false;

        $query = "
            UPDATE
                #__jreviews_content
            SET
                featured = " . (int)$new_state . "
            WHERE
                contentid IN( ". $listing_id.")
            ";

        $this->_db->setQuery($query);

        return (bool) $this->_db->query();
    }

    function makeOrder($plan,$listing,$options = array())
    {
        $handler_id = Sanitize::getInt($options,'handler_id');

        $status = Sanitize::getString($options,'status','Incomplete');

        $discounted_price = Sanitize::getVar($options,'discounted_price');

        $tax_rate = Sanitize::getVar($options,'tax_rate',0);

        isset($listing['Listing']['url']) and $listing['Listing']['url'] = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

        // Store transaction prior to payment for verification and reporting purposes
        $order_created = _CURRENT_SERVER_TIME;

        if($plan['PaidPlan']['plan_array']['duration_period'] != 'never') {

            $order_expires = $this->getExpirationDate($plan['PaidPlan']['plan_array']['duration_period'],$plan['PaidPlan']['plan_array']['duration_number'],$order_created);
        }
        else {

            $order_expires = _NULL_DATE;
        }

        $order = array();

        $order['PaidOrder'] = array(
            'listing_id'=>$listing['Listing']['listing_id'],
            'user_id'=>$listing['User']['user_id'],
            'order_amount'=>!is_null($discounted_price) ? round($discounted_price+$discounted_price*$tax_rate,2) : round($plan['PaidPlan']['plan_price']+$plan['PaidPlan']['plan_price']*$tax_rate,2),
            'order_discount'=>!is_null($discounted_price) ? round($plan['PaidPlan']['plan_price']-$discounted_price,2) : '',
            'order_tax'=>!is_null($discounted_price) ? round($discounted_price*$tax_rate,2) : round($plan['PaidPlan']['plan_price']*$tax_rate,2),
            'coupon_name'=>!is_null($discounted_price) ? Sanitize::getString($options,'coupon') : '',
            'order_created'=>$order_created,
            'order_expires'=>$order_expires,
            'order_never_expires'=>$plan['PaidPlan']['plan_array']['duration_period'] == 'never' ? 1 : 0,
            'plan_id'=>$plan['PaidPlan']['plan_id'],
            'plan_type'=>$plan['PaidPlan']['plan_type'],
            'payment_type'=>$plan['PaidPlan']['payment_type'],
            'plan_info'=>json_encode($plan['PaidPlan']),
            'plan_updated'=>$order_created,
            'listing_info'=>json_encode(array(
                'listing_title'=>$listing['Listing']['title'],
                'listing_url'=>Sanitize::getString($listing['Listing'],'url'),
                'owner_name'=>$listing['User']['name'],
                'owner_username'=>$listing['User']['username']
            )),
            'handler_id'=>$handler_id,
            'order_status'=>$status
        );

        return $order;
    }

    /**
    * Completes listing info with the latest transaction if available and pricing plan availability for each listing
    * @param array $results
    */
    function completeOrderInfo($results,$options = array('order_active'=>1, 'order_status'=>'Complete', 'paid_data'=>false))
    {
        is_numeric($results) and $results = array($results=>array('Listing'=>array('featured'=>0))); // Listing id passed as argument

        $listing_ids = array_keys($results);

        $paid_data = Sanitize::getBool($options,'paid_data');

        if(!empty($listing_ids))
        {
            $conditions = array(
                '`PaidOrder`.listing_id IN (' . implode(',',$listing_ids).')'
            );

            isset($options['order_status']) and $conditions[] = '`PaidOrder`.order_status = "' . $options['order_status'] . '"';

            isset($options['order_active']) and $conditions[] = '`PaidOrder`.order_active = ' .$options['order_active'];

            $orders = $this->findAll(array(
                'conditions'=>$conditions,
                'order'=>array(
                    '`PaidOrder`.order_created ASC'
                )
            ));

            foreach($orders AS $order=>$row)
            {
                $results[$row['PaidOrder']['listing_id']]['PaidOrder'][$row['PaidOrder']['order_id']] = $row['PaidOrder'];

                if($paid_data
                    ||
                    (Sanitize::getInt($row['PaidOrder'],'order_active')==1 && Sanitize::getString($row['PaidOrder'],'order_status')=='Complete')
                ){
                    if(!isset($results[$row['PaidOrder']['listing_id']]['Paid']))
                    {
                        $results[$row['PaidOrder']['listing_id']]['Paid']['fields'] = isset($row['PaidOrder']['plan_info']['plan_array']['fields']) ? $row['PaidOrder']['plan_info']['plan_array']['fields'] : array();

                        $results[$row['PaidOrder']['listing_id']]['Paid']['photo'] = Sanitize::getVar($row['PaidOrder']['plan_info'],'photo');

                        $results[$row['PaidOrder']['listing_id']]['Paid']['video'] = Sanitize::getVar($row['PaidOrder']['plan_info'],'video');

                        $results[$row['PaidOrder']['listing_id']]['Paid']['attachment'] = Sanitize::getVar($row['PaidOrder']['plan_info'],'attachment');

                        $results[$row['PaidOrder']['listing_id']]['Paid']['audio'] = Sanitize::getVar($row['PaidOrder']['plan_info'],'audio');

                        $results[$row['PaidOrder']['listing_id']]['Paid']['custom_vars'] = Sanitize::getVar($row['PaidOrder']['plan_info']['plan_array'],'custom_vars',array());
                    }
                    else {

                        isset($row['PaidOrder']['plan_info']['plan_array']['fields']) and $results[$row['PaidOrder']['listing_id']]['Paid']['fields'] = array_merge($results[$row['PaidOrder']['listing_id']]['Paid']['fields'],$row['PaidOrder']['plan_info']['plan_array']['fields']);

                        $results[$row['PaidOrder']['listing_id']]['Paid']['photo'] = max($results[$row['PaidOrder']['listing_id']]['Paid']['photo'],$row['PaidOrder']['plan_info']['photo']);

                        $results[$row['PaidOrder']['listing_id']]['Paid']['video'] = max($results[$row['PaidOrder']['listing_id']]['Paid']['video'],$row['PaidOrder']['plan_info']['video']);

                        $results[$row['PaidOrder']['listing_id']]['Paid']['attachment'] = max($results[$row['PaidOrder']['listing_id']]['Paid']['attachment'],$row['PaidOrder']['plan_info']['attachment']);

                        $results[$row['PaidOrder']['listing_id']]['Paid']['audio'] = max($results[$row['PaidOrder']['listing_id']]['Paid']['audio'],$row['PaidOrder']['plan_info']['audio']);

                        isset($row['PaidOrder']['plan_info']['plan_array']['custom_vars']) and $results[$row['PaidOrder']['listing_id']]['Paid']['custom_vars'] = array_merge($results[$row['PaidOrder']['listing_id']]['Paid']['custom_vars'],$row['PaidOrder']['plan_info']['plan_array']['custom_vars']);
                    }

                    $results[$row['PaidOrder']['listing_id']]['Paid']['fields'] = array_filter($results[$row['PaidOrder']['listing_id']]['Paid']['fields']);

                    $results[$row['PaidOrder']['listing_id']]['Paid']['fields'][] = 'jr_gm_distance'; // Ensure the virtual distance field is always available

//                    $results[$row['PaidOrder']['listing_id']]['Listing']['featured'] = max($results[$row['PaidOrder']['listing_id']]['Listing']['featured'],$row['PaidOrder']['plan_info']['plan_featured']);  /* commented because featured is at the listing level, not the order */
                    // More detailed plan info

                    $results[$row['PaidOrder']['listing_id']]['Paid']['plans']['PlanType'.$row['PaidOrder']['plan_info']['plan_type']][$row['PaidOrder']['plan_info']['plan_id']]['plan_id'] = $row['PaidOrder']['plan_info']['plan_id'];

                    $results[$row['PaidOrder']['listing_id']]['Paid']['plans']['PlanType'.$row['PaidOrder']['plan_info']['plan_type']][$row['PaidOrder']['plan_info']['plan_id']]['order_never_expires'] = $row['PaidOrder']['order_never_expires'];

                    $row['PaidOrder']['plan_info']['plan_type']==1 /*upgrade*/ and  $results[$row['PaidOrder']['listing_id']]['Paid']['plans']['PlanType'.$row['PaidOrder']['plan_info']['plan_type']][$row['PaidOrder']['plan_info']['plan_id']]['plan_upgrade_exclusive'] = Sanitize::getInt($row['PaidOrder']['plan_info'],'plan_upgrade_exclusive');
                }
            }
        }

        unset($orders);

        return $results;
    }

    function getExpirationDate($interval,$number,$start_date)
    {
        $start_date = (strtotime($start_date) != -1) ? strtotime($start_date) : $start_date;
        $start_date_parts = getdate($start_date);
        $yr = $start_date_parts['year'];
        $mon = $start_date_parts['mon'];
        $day = $start_date_parts['mday'];
        $hr = $start_date_parts['hours'];
        $min = $start_date_parts['minutes'];
        $sec = $start_date_parts['seconds'];

        switch($interval)
        {
            case "days"://days
                $day += $number;
                break;
            case "weeks"://Week
                $day += ($number * 7);
                break;
            case "months":
                $mon += $number;
                break;
            case "years":
                $yr += $number;
                break;
            default:
                $day += $number;
        }
        $end_date = mktime($hr,$min,$sec,$mon,$day,$yr);
        $end_date_parts = getdate($end_date);

        $nosecmin = 0;
        $min = $end_date_parts['minutes'];
        $sec = $end_date_parts['seconds'];

        if ($hr == 0){$nosecmin += 1;}
        if ($min == 0){$nosecmin += 1;}
        if ($sec == 0){$nosecmin += 1;}

        if ($nosecmin > 2){
            return(gmdate("Y-m-d",$end_date));
        } else {
            return(gmdate("Y-m-d G:i:s",$end_date));
        }
    }

    function updateAllExpiredOrders()
    {
        $PaidListingField = ClassRegistry::getClass('PaidListingFieldModel');

        # Find all active orders expiring today or already expired
        $orders = $this->findAll(array('conditions'=>array(
            'PaidOrder.order_active = 1',
            'PaidOrder.order_expires <= "'.gmdate('Y-m-d',time()).'"',
            'PaidOrder.order_never_expires = 0'
        )));

        if($orders)
        {
            foreach($orders AS $order)
            {
                $order_update = array('order_active'=>0);
                $this->updateOrder($order,$order_update);
                $PaidListingField->removeFieldsFromListing($order['PaidOrder']['listing_id'],$order['PaidOrder']['plan_info']);
            }
        }
    }

    function updateOrder(&$order, $data)
    {
        appLogMessage('*** PaidOrder::updateOrder', 'database');

        $PaidTxnLog = ClassRegistry::getClass('PaidTxnLogModel');

        $order = array_insert($order,array('PaidOrder'=>$data));
        /****
        * IF changing status to inactive, then check if it's an Upgrades plan with featured status
        * to unfeature it. This is required for expirations, subscription cancellations, reversals, etc.
        */
        if($order['PaidOrder']['plan_type'] == 1
            &&
            $order['PaidOrder']['plan_info']['plan_featured']
            &&
            (isset($data['order_active']) && !$data['order_active'])
        ){

            $this->changeFeaturedState($order['PaidOrder']['listing_id'],0) and $PaidTxnLog->addNote("Featured: 0");
        }

        return $this->store($order);
    }

    function updateOrderNotification($order_ids, $column)
    {
        $query = "
            UPDATE
                #__jreviews_paid_orders
            SET
                $column = 1
            WHERE
                order_id IN (". implode(',',$order_ids) .")
        ";
        $this->_db->setQuery($query);
        return $this->_db->query();
    }

    function afterDelete($key, $values, $condition)
    {
        S2App::import('Model','paid_txn_log','jreviews');
        $Txn = ClassRegistry::getClass('PaidTxnLogModel');
        $Txn->delete($key,$values);

        is_array($values) and $values = implode(",",$values);
        $query = "
                UPDATE
                    #__jreviews_paid_orders
                SET
                    order_id_renewal = 0
                WHERE
                    order_id_renewal IN (" . $values . ")
          ";
          $this->_db->setQuery($query);
          $this->_db->query();
    }

    function afterFind($results)
    {
        foreach($results AS $key=>$row)
        {
            $results[$key]['PaidOrder']['plan_info'] = json_decode($row['PaidOrder']['plan_info'],true);
            $results[$key]['PaidOrder']['listing_info'] = json_decode($row['PaidOrder']['listing_info'],true);

            if(isset($row['Listing'])) {
                $results[$key]['PaidOrder']['listing_info']['listing_title'] = $row['Listing']['title'];
            }
        }

		$results = $this->addListingUrl($results);

       return $results;
    }

    function getActiveOrderPlanIdsByListing($listing_id)
    {
        $query = "
            SELECT
                PaidOrder.plan_id
            FROM
                #__jreviews_paid_orders AS PaidOrder
            WHERE
                PaidOrder.listing_id = " . (int) $listing_id . "
                AND
                PaidOrder.order_active = 1
        ";

        return $this->query($query,'loadColumn');
    }
}
