<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminTwitterController extends MyController
{
    var $uses = array();

    var $components = array('config');

    var $autoRender = false;

    var $autoLayout = false;

    var $layout = 'empty';

    function form()
    {
        $this->autoRender = false;
        $this->autoLayout = false;
        $twitter_oauth = json_decode($this->Config->twitter_oauth,true);
        ?>
        <p>For additional help read the <a target="_blank" href="http://docs.reviewsforjoomla.com/Setup_Twitter_integration">Twitter integration</a> article.</p>
        <form name="twitter_form" id="twitter_form">
            <table border="0">
            <tr><th style="text-align:right;">Consumer Key</th><td><input name="data[twitter_oauth][key]" value="<?php echo $twitter_oauth['key'];?>" style="width:15em;" /></td></tr>
            <tr><th style="text-align:right;">Consumer Secret</th><td><input name="data[twitter_oauth][secret]" value="<?php echo $twitter_oauth['secret'];?>" style="width:30em;" /></td></tr>
            <tr><th style="text-align:right;">Access Token</th><td><input name="data[twitter_oauth][token]" value="<?php echo $twitter_oauth['token'];?>" style="width:35em;" /></td></tr>
            <tr><th style="text-align:right;">Access Token Secret</th><td><input name="data[twitter_oauth][tokensecret]" value="<?php echo $twitter_oauth['tokensecret'];?>" style="width:30em;" /></td></tr>
            </table>
            <input type="hidden" name="data[controller]" value="admin/admin_twitter" />
            <input type="hidden" name="data[action]" value="_save" />
        </form>
        <?php
    }

    function _save()
    {
        $this->Config->twitter_oauth = Sanitize::getVar($this->data,'twitter_oauth');

        $this->Config->store();
    }
}