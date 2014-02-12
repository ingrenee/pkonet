<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class PaidRoutesHelper extends MyHelper
{
    var $Html;

    var $routes = array(
        'myaccount'=>'index.php?option=com_jreviews&amp;Itemid=%s',
        'order_complete'=>'index.php?option=com_jreviews&amp;url=paidlistings_orders/complete/%s',
        'listing_new_category'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_c%s/plan_id:%s',
        'listing_new_category_j15'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_s%s_c%s/plan_id:%s',
        'invoice'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;tmpl=component&amp;url=paidlistings_invoices/view/invoice:%s',
        'invoice_user'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;tmpl=component&amp;url=paidlistings_invoices/view/invoice:%s/user:%s',
        'unpaid_invoice'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;tmpl=component&amp;url=paidlistings_invoices/unpaid/order:%s'
    );

    function __construct()
    {
        $this->cmsVersion = getCmsVersion();

        $this->Html = new HtmlHelper('jreviews');
    }

    function myaccount($attributes = array())
    {
        if(isset($attributes['title']))
        {
            $title = $attributes['title'];
            unset($attributes['title']);
        } else {
            $title = '';
            $attributes['return_url'] = true;
        }

        $params = array();

        if(isset($attributes['params']))
        {
            foreach($attributes['params'] AS $key=>$value)
            {
                $params[] = $key.':'.$value;
            }
            unset($attributes['params']);
        }

        $params = implode('/',$params);

        $menuModel = ClassRegistry::getClass('MenuModel');

        $menu_id = $menuModel->getMenuIdByAction(19);

        $url = sprintf($this->routes['myaccount'], $params . ($menu_id>0 ? $menu_id : '&amp;url=paidlistings/myaccount'));

        return $this->Html->sefLink($title,$url,$attributes);
    }

    function invoice($invoice_id, $user_id = '')
    {
        $menuModel = ClassRegistry::getClass('MenuModel');

        $menu_id = $menuModel->getMenuIdByAction(19);

        if($this->Access->isManager() && $user_id > 0) {

            $url = sprintf($this->routes['invoice_user'], $menu_id, $invoice_id, $user_id);
        }
        else {

            $url = sprintf($this->routes['invoice'], $menu_id, $invoice_id);
        }

        return cmsFramework::route($url);
    }

    function unpaidInvoice($order_id)
    {
        $menuModel = ClassRegistry::getClass('MenuModel');
        $menu_id = $menuModel->getMenuIdByAction(19);
        $url = sprintf($this->routes['unpaid_invoice'], $menu_id, $order_id);
        return cmsFramework::route($url);
    }

    function newListing($title,$category,$plan_id,$attributes = array())
    {
        if($this->cmsVersion == CMS_JOOMLA15) {

            $url = sprintf($this->routes['listing_new_category_j15'],Sanitize::getInt($category['Category'],'menu_id') ? $category['Category']['menu_id'] : '',$category['Category']['section_id'],$category['Category']['cat_id'],$plan_id);
        }
        else {

            $url = sprintf($this->routes['listing_new_category'],Sanitize::getInt($category['Category'],'menu_id') ? $category['Category']['menu_id'] : '',$category['Category']['cat_id'],$plan_id);
        }

        return $this->Html->sefLink($title,$url,$attributes);
    }

    function orderComplete($paramsArray)
    {
        $params = array();
        // Check if there's a menu for this category to prevent duplicate urls
        if(isset($paramsArray['menu_id']))
        {
            $menu_id = (int) $paramsArray['menu_id'];
            unset($paramsArray['menu_id']);
        } else {
            $menuModel = ClassRegistry::getClass('MenuModel');
            $menu_id = $menuModel->getMenuIdByAction(19);
        }

        foreach($paramsArray AS $key=>$val)
        {
            $params[] = $key.':'.$val;
        }
        $url = sprintf($this->routes['order_complete'], implode('/',$params) . ($menu_id>0 ? '&amp;Itemid='.$menu_id : ''));
        return cmsFramework::route($url);
    }

    function getPaymentLink($listing,$attributes = array())
    {

        if(isset($listing['Paid']['plans']['PlanType0']))
        {
            $base_plan = current($listing['Paid']['plans']['PlanType0']);

            $base_plan_never_expires = $base_plan['order_never_expires'] ? true : false;

            // Get ids of available listing upgrade plans
            $available_upgrade_plan_ids = array();

            if(isset($listing['PaidPlan']['PlanType1']))
            {
                foreach($listing['PaidPlan']['PlanType1'] AS $payment_type=>$plans)
                {
                    foreach($plans AS $plan_id=>$plan)
                    {
                        if($base_plan_never_expires || (!$base_plan_never_expires && $plan['plan_array']['duration_period']=='never')) // Base plan expires, so we remove expiring upgrades
                        {
                            $available_upgrade_plan_ids[] = $plan_id;
                        }
                    }
                }
            }

            // Listing has an active upgrade plan - check if it is exclusive and abort if it is
            if(isset($listing['Paid']['plans']['PlanType1']))
            {
                $upgrade = current($listing['Paid']['plans']['PlanType1']);
                if($upgrade['plan_upgrade_exclusive'])
                {
                    return '';
                }
            }

            // Upgrade not exclusive - filter valid upgrades
            $applicable_upgrade_plan_ids = isset($listing['Paid']['plans']['PlanType1'])
                ?
                array_diff($available_upgrade_plan_ids,array_keys($listing['Paid']['plans']['PlanType1']))
                :
                $available_upgrade_plan_ids
                ;

            if(isset($listing['PaidPlan']['PlanType1']) && !empty($applicable_upgrade_plan_ids))
            {   // If there are upgrade plans different from the active ones, show upgrade link
                $this->upgradeLink($listing, $attributes);
            }
        }
        elseif(isset($listing['PaidPlan']['PlanType0'])) {   // Listing without base plan and there are base plans available

            $this->newLink($listing, $attributes);
        }
    }

    function upgradeLink($listing, $attributes)
    {
        $plan_id = Sanitize::getInt($attributes,'plan_id',0);

        $listing_id = $listing['Listing']['listing_id'];

        $listing_title = $listing['Listing']['title'];

        $listing_title = htmlspecialchars($listing_title,ENT_COMPAT,'UTF-8');
        ?>

            <button class="jr-paid-buy jrButton jrSmall"

                data-listing-id="<?php echo $listing_id;?>" data-plan-id="<?php echo $plan_id;?>" data-title="<?php echo $listing_title;?>"

                data-plan-type="1">

                <span class="jrIcon jrIconCart"></span><?php __t("Upgrade");?>

            </button>

        <?php
    }

    function newLink($listing, $attributes)
    {
        $plan_id = Sanitize::getInt($attributes,'plan_id',0);

        $plan_type = Sanitize::getInt($attributes,'plan_type',0);

        $order_id = Sanitize::getInt($attributes,'order_id',0);

        $renewal = Sanitize::getInt($attributes,'renewal',0);

        $link_text = Sanitize::getString($attributes,'link_text');

        !$link_text and $link_text = __t("Order",true);

        $listing_id = $listing['Listing']['listing_id'];

        $listing_title = $listing['Listing']['title'];

        $listing_title = htmlspecialchars($listing_title,ENT_COMPAT,'UTF-8');
        ?>

            <button class="jr-paid-buy jrButton jrSmall"

                data-listing-id="<?php echo $listing_id;?>" data-plan-id="<?php echo $plan_id;?>" data-title="<?php echo $listing_title;?>"

                data-renewal="<?php echo $renewal;?>" data-order-id="<?php echo $order_id;?>"

                data-plan-type="<?php echo $plan_type;?>">

                <span class="jrIcon jrIconCart"></span><?php echo $link_text;?>

            </button>

        <?php
    }

}