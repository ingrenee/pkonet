<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityHelper extends MyHelper {

    var $helpers = array('html');

    function profileLink($name,$user_id,$menu_id)
    {
        if($user_id > 0) {
            $community_url = Configure::read('Community.profileUrl');
            $url = sprintf($community_url,$user_id,$menu_id);
            return $this->Html->sefLink($name,$url,array(),false);
        }else {
            return $name;
        }
    }

    function avatar($entry)
    {
        if(isset($entry['Community']) && isset($entry['Community']['community_user_id']) && $entry['User']['user_id'] > 0)
        {
            $screenName = $this->screenName($entry,null,false);

            if(isset($entry['Community']['avatar_path']) && $entry['Community']['avatar_path'] != '') {
                return $this->profileLink($this->Html->image($entry['Community']['avatar_path'],array('class'=>'jrAvatar','alt'=>$screenName,'border'=>0)),$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
            } else {
                return $this->profileLink($this->Html->image($this->viewImages.'tnnophoto.jpg',array('class'=>'jrAvatar','alt'=>$screenName,'border'=>0)),$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
            }
        }
    }

    function screenName(&$entry, $link = true)
    {
        // $Config param not being used
        $screenName = $this->Config->name_choice == 'realname' ? $entry['User']['name'] : $entry['User']['username'];

        if($link && !empty($entry['Community']) && isset($entry['Community']['community_user_id']) && $entry['User']['user_id'] > 0) {
            return $this->profileLink($screenName,$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
        }

        $screenName = $screenName == '' ? __t("Guest",true) : $screenName;

        return $screenName;
    }

	function socialBookmarks($listing)
	{
        $output = '';

        $options = Sanitize::getVar($this->Config,'social_sharing_detail',array());

        $googlePlusOne = $twitter = $facebook = $pinterest = $linkedIn = '';

        $countPosition = $this->Config->social_sharing_count_position;

        switch($countPosition)
        {
            case 'vertical':
                $countPositionClass = "Vertical";
                $twitterCount = 'vertical';
                $facebookCount = 'box_count';
                $gplusCount = 'tall';
                $linkedInCount = 'top';
                $pinterestCount = 'vertical';
                break;
            case 'horizontal':
                $countPositionClass = '';
                $twitterCount = 'horizontal';
                $facebookCount = 'button_count';
                $gplusCount = 'medium';
                $linkedInCount = 'right';
                $pinterestCount = 'horizontal';
            break;
            case 'none':
                $countPositionClass = '';
                $twitterCount = 'horizontal';
                $facebookCount = 'button_count'; // no support for disabling it
                $gplusCount = 'none';
                $linkedInCount = 'none';
                $pinterestCount = 'none';
                break;
        }

        $facebook_xfbml = Sanitize::getBool($this->Config,'facebook_opengraph') && Sanitize::getBool($this->Config,'facebook_appid');

        $href = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

        if (isset($listing['MainMedia']['media_info']['image'])) {

            $thumb_url = $listing['MainMedia']['media_info']['image']['url'];

        }
        else {

            $thumb_url = '';
        }

        if(in_array('twitter',$options)) {

            $twitter = '
                <a href="http://twitter.com/share" data-url="'.$href.'" class="jr-tweet twitter-share-button" data-count="'.$twitterCount.'">'.__t("Tweet",true).'</a>'
            ;
        }

        if(in_array('fbsend',$options)) {

            $facebook = '<div class="jr-fb-send fb-send" data-href="'.$href.'" data-colorscheme="light"></div>';
        }

        if(in_array('fblike',$options)) {

            if($facebook_xfbml) {
                $facebook .= '<div class="jr-fb-like fb-like" data-show-faces="false" data-href="'.$href.'" data-action="like" data-colorscheme="light" data-layout="'.$facebookCount.'"></div>';
            }
            else {
                $facebook .= '
                    <div class="jr-fb-like fb-like" data-layout="'.$facebookCount.'" data-show_faces="false"></div>';
            }
        }

        if(in_array('gplusone',$options)) {

            $googlePlusOne = '<span class="jr-gplusone jrHidden"></span><g:plusone href="'.$href.'" size="'.$gplusCount.'"></g:plusone>';
        }

        if(in_array('linkedin',$options)) {

            $linkedIn = '<span class="jr-linkedin jrHidden"></span><script type="IN/Share" data-url="'.$href.'" data-counter="'.$linkedInCount.'"></script>';
        }


        if(in_array('pinit',$options)) {

            if ($thumb_url != '') {

                $pinterest = '<a href="http://pinterest.com/pin/create/button/?url='.$href.'&media='.$thumb_url.'" class="jr-pinterest pin-it-button" count-layout="'.$pinterestCount.'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
            }
            else {

                $pinterest = '<a href="http://pinterest.com/pin/create/button/?url='.$href.'" class="jr-pinterest pin-it-button" count-layout="'.$pinterestCount.'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
            }
        }

        $buttons = $facebook . $twitter . $googlePlusOne . $linkedIn . $pinterest;

        if ($buttons != '') {

            $output .= '<div class="socialBookmarks'.$countPositionClass.'">';

            $output .= $buttons;

            $output .= '</div>';

        }

        echo $output;
	}

}