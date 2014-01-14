<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidTxnLogModel extends MyModel  {

    var $name = 'PaidTxnLog';

    var $useTable = '#__jreviews_paid_txn_logs AS `PaidTxnLog`';

    var $primaryKey = 'PaidTxnLog.log_id';

    var $realKey = 'log_id';

    var $fields = array('PaidTxnLog.*');

    var $log_note = array();

    function addNote($note)
    {
        $this->log_note[] = $note;
    }

    function getNote()
    {
        return implode("\n",$this->log_note);
    }

    function findDuplicate($txn_id)
    {
        // Avoid duplicates - find active order from the same day first with same txn_id
        $txn_duplicate = $this->findCount(
            array
            (
                'conditions'=>array(
                    'PaidTxnLog.txn_id = ' . $this->Quote($txn_id),
                    'PaidTxnLog.txn_success = 1',
                    'PaidTxnLog.txn_date >= "'.date('Y-m-d').' 00:00:00" AND PaidTxnLog.txn_date < "'.date('Y-m-d').' 23:59:59"'
                )
            ),
            '*'
        );
        return $txn_duplicate;
    }

    function save($order, $order_data, $txn_id, $paid = false)
    {
        $txn_duplicate = $this->findDuplicate($txn_id);

        $note = $this->getNote();

        $txn_duplicate and $note = "Duplicate Txn.\n" . $note;

        $data = array(
            'PaidTxnLog'=>array(
                'txn_date'=>_CURRENT_SERVER_TIME,
                'txn_id'=>$txn_id,
                'handler_id'=>$order['PaidOrder']['handler_id'],
                'order_id'=>$order['PaidOrder']['order_id'],
                'txn_data'=>json_encode($order_data),
                'txn_success'=>$txn_duplicate ? 0 : $paid,
                'log_note'=>$note
            )
        );
        $this->store($data);
        $this->log_note = array(); // Reset notes after save
    }

    function afterFind($results)
    {
        $tmp = current($results);
        if(isset($tmp['PaidOrder']))
        {
            foreach($results AS $key=>$row)
            {
                $results[$key]['PaidOrder']['plan_info'] = json_decode($row['PaidOrder']['plan_info'],true);
                $results[$key]['PaidOrder']['listing_info'] = json_decode($row['PaidOrder']['listing_info'],true);
                if(isset($results[$key]['Listing'])) {
                    $results[$key]['PaidOrder']['listing_info']['listing_title'] = $results[$key]['Listing']['title'];
                }
            }
        }
        return $results;
    }
}