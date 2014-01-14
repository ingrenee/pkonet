<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class WidgetsHelper extends MyHelper
{
    function __construct()
    {
        parent::__construct();

        $this->Routes = ClassRegistry::getClass('RoutesHelper');

        $this->Paid = ClassRegistry::getClass('PaidHelper');

        $this->PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper');
    }

    function addMedia($listing)
    {
        if($this->Access->canAddAnyListingMedia($listing['User']['user_id'], $listing['ListingType']['config'], $listing['Listing']['listing_id'])) {

            echo $this->Routes->mediaCreate('<span class="jrIconAddMedia"></span>' . __t("Add Media",true),$listing,array('cat_menu_id'=>$listing['Category']['menu_id'],'class'=>'jrButton jrSmall jrAddMedia'));

        }
    }

    function addReview($listing)
    {
        if(Sanitize::getInt($listing['Criteria'],'state') == 2):?>

            <div class="jr-review-add jr-listing-info jrButton jrSmall"><span class="jrIconComments"></span><?php echo sprintf(__t("Comments (%s)",true), $listing['Review']['review_count']); ?></div>

        <?php endif;

        if(!$listing['duplicate_review'] && $this->Access->canAddReview($listing['User']['user_id']) && Sanitize::getInt($listing['Criteria'],'state') == 1 && $this->Config->user_reviews):?>

            <div class="jr-review-add jr-listing-info jrButton jrSmall"><span class="jrIconAddReview"></span><?php __t("Write Review");?></div>

        <?php endif;
    }

    function claim($listing)
    {
        $show_claim_button = Sanitize::getBool($this->Config,'claims_enable',false);

        if(!$show_claim_button) return '';

        $User = cmsFramework::getUser();

        $listing_id = $listing['Listing']['listing_id'];

        $approved = isset($listing['Claim']) && Sanitize::getBool($listing['Claim'],'approved');

		$claimable = $this->Config->claims_enable_userids == '' || ($this->Config->claims_enable_userids != '' && in_array($listing['Listing']['user_id'],explode(',',$this->Config->claims_enable_userids)));

        if($this->Access->canClaimListing($listing) && !$approved)
        {
            $state = 'active';
        }
        elseif($claimable && $User->id == 0 && !$approved)
        {
            $state = 'no-access';
        }
        else {
            return '';
        }
        ?>

        <button class="jr-listing-claim jrButton jrSmall"

            data-listing-id="<?php echo $listing_id;?>" data-state="<?php echo $state;?>">

            <span class="jrIconClaim"></span>

            <span><?php __t("Claim this listing");?></span>

        </button>

        <?php
    }

    function compareCheckbox($listing, $location = '') {

        $show_compare_cb = $this->Config->getOverride('list_compare',$listing['ListingType']['config']);
        $tn_size = $this->Config->getOverride('media_list_thumbnail_size',$listing['ListingType']['config']);
        $tn_mode = $this->Config->getOverride('media_list_thumbnail_mode',$listing['ListingType']['config']);
        $listing_thumb = $tn_size.$tn_mode[0];

        if(!$show_compare_cb) return '';

        $listing_title = htmlspecialchars($listing['Listing']['title'],ENT_QUOTES,cmsFramework::getCharset());

        $listing_id = $listing['Listing']['listing_id'];

        $listing_url = $this->Routes->content($listing['Listing']['title'],$listing,array('return_url'=>true));

        if (isset($listing['MainMedia']['media_info']['thumbnail']) &&
                isset($listing['MainMedia']['media_info']['thumbnail'][$listing_thumb])
            ) {

            $listing_thumb_url = $listing['MainMedia']['media_info']['thumbnail'][$listing_thumb]['url'];

        } else {

            $listing_thumb_url = '';

        }

        $listing_type_id = $listing['Criteria']['criteria_id'];

        $listing_type_title = $listing['Criteria']['title'];

        ?>

        <span class="jrCompareButton jrButton jrSmall">

            <input type="checkbox" class="jrCheckListing listing<?php echo $listing_id;?>"
                id="listing<?php echo $listing_id.$location;?>" data-location="<?php echo $location;?>"
                data-listingurl="<?php echo $listing_url;?>" data-thumburl="<?php echo $listing_thumb_url;?>"
                data-listingtitle="<?php echo $listing_title;?>" data-listingid="listing<?php echo $listing_id;?>"
                data-listingtypeid="<?php echo $listing_type_id;?>" data-listingtypetitle="<?php echo $listing_type_title;?>"
                value="<?php echo $listing_id;?>" />&nbsp;
            <label class="jrCheckListingLabel" for="listing<?php echo $listing_id.$location;?>"><?php __t("Compare");?> </label>

        </span>

        <?php
    }

    function download($attachment)
    {
        extract($attachment);

        $m = s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));

        $session_token = cmsFramework::getToken();

        $integrity_token = cmsFramework::getCustomToken($media_id, $media_type, $filename, $created);

        ?>

            <button class="jr-media-download jrButton jrSmall"

                data-media-id="<?php echo $m;?>"

                data-token-s="<?php echo $session_token;?>"

                data-token-i="<?php echo $integrity_token;?>">

                <span class="jrIconArrowDown"></span><span><?php __t("Download");?></span>

            </button>

        <?php
    }

    function inquiry($listing)
    {
        $enabled = Sanitize::getBool($this->Config,'inquiry_enable',false);

        $recipient = Sanitize::getString($this->Config,'inquiry_recipient');

        if($enabled) {

            switch($recipient) {
                case 'owner':
                    $recipient_exists = $listing['User']['user_id'] > 0;
                break;
                case 'field':
                    $field = Sanitize::getString($this->Config,'inquiry_field');
                    $recipient_exists = $field != '' && isset($listing['Field']['pairs'][$field]) && Sanitize::getString($listing['Field']['pairs'][$field]['text'],'0');
                break;
                case 'admin':
                    $recipient_exists = true;
                break;
            }

            if($recipient_exists) {

                $listing_id = Sanitize::getInt($listing['Listing'],'listing_id');
                ?>

                    <button class="jr-send-inquiry jrButton jrSmall" data-listing-id="<?php echo $listing_id;?>">

                        <span class="jrIconMessage"></span><span><?php __t("Send Inquiry");?></span>

                    </button>

                <?php

            }
        }
    }

    function favorite($listing)
    {
        $show_favorite_button = Sanitize::getBool($this->Config,'favorites_enable');

        if(!$show_favorite_button) return '';

        $output = '';

        $listing_id = $listing['Listing']['listing_id'];

        $User = cmsFramework::getUser();

        if($listing['Favorite']['my_favorite']) { // Already in user's favorites
            $state = 'favored';
        }
        elseif($User->id) { // Not in user's favorites
            $state = 'not_favored';
        }
        else { // This is a guest user, needs to register to use the favorites widget
            $state = 'no_access';
        }

        ?>

        <button class="jr-listing-favorite jrButton jrSmall"

            data-states='{"favored":"jrIconUnfavorite","not_favored":"jrIconFavorite"}'

            data-listing-id="<?php echo $listing_id;?>"

            data-state="<?php echo $state;?>">

            <span class="<?php echo ($state == 'favored' ? 'jrIconUnfavorite' : 'jrIconFavorite');?>"></span>

            <span><?php echo $state == 'favored' ? JreviewsLocale::getPHP('FAVORITE_REMOVE') : JreviewsLocale::getPHP('FAVORITE_ADD');?></span>

        </button>

        <?php
    }

    function ownerReply($review)
    {
        $state = Sanitize::getInt($review['Criteria'],'state');

        $review_id = Sanitize::getInt($review['Review'],'review_id');

        $text = $state != 2 ? __t("Reply to this review",true) : __t("Reply to this comment",true);

        ?>

        <button class="jr-owner-reply jrButton jrSmall" data-review-id="<?php echo $review_id;?>">

            <span class="jrIconAddComment"></span><span><?php echo $text;?></span>

        </button>

        <?php
    }

    function ownerReplyDelete($listing, $review)
    {
        $review_id = $review['Review']['review_id'];

        $token = cmsFramework::getCustomToken($review_id);

        if(!$this->Access->canDeleteOwnerReply($listing)) return;
        ?>

        <button class="jr-owner-reply-del jrButton jrSmall" data-review-id="<?php echo $review_id;?>" data-token="<?php echo $token;?>">

            <span class="jrIconDelete"></span><span><?php __t("Delete Reply");?></span>

        </button>

        <?php
    }

    function reviewEdit($review)
    {
        $review_id = Sanitize::getInt($review['Review'],'review_id');

        $referrer = Sanitize::getString($this->data,'referrer');

        if($referrer == '') {

            $referrer = $this->name == 'reviews' ? 'list' : 'detail';
        }
        ?>

        <button class="jr-review-edit jrButton jrSmall" data-review-id="<?php echo $review_id;?>" data-referrer="<?php echo $referrer;?>">

            <span class="jrIconEdit"></span><span><?php __t("Edit");?></span>

        </button>

        <?php
    }

    function report($data)
    {
        extract($data); // $post, $review, $listing

        $listing_id = Sanitize::getInt($review['Review'],'listing_id');

        $review_id = Sanitize::getInt($review['Review'],'review_id');

        $post_id = 0;

        $media_id = 0;

        if(isset($post)) {

            $post_id = Sanitize::getInt($post['Discussion'],'discussion_id');

            $extension = Sanitize::getString($post['Discussion'],'extension');

            $title = __t("Report this comment",true);

        }

        elseif (isset($review)) {

            $state = Sanitize::getInt($review['Criteria'],'state');

            $title = $state != 2 ? __t("Report this review",true) : __t("Report this comment",true);

            $extension = Sanitize::getString($review['Review'],'extension');

            $class = 'jrReportReview';

        }

        ?>

        <button class="jr-report jrRight jrLinkButton" title="<?php echo $title;?>"

            data-listing-id="<?php echo $listing_id;?>"

            data-review-id="<?php echo $review_id;?>"

            data-post-id="<?php echo $post_id;?>"

            data-media-id="<?php echo $media_id;?>"

            data-extension="<?php echo $extension;?>">

                <span class="jrIconWarning"></span>

                <span class="jrHidden"><?php echo $title;?></span>
        </button>

        <?php
    }

    function reviewVoting($review)
    {
        $review_id = $review['Review']['review_id'];

        $User = cmsFramework::getUser();

        if($this->Access->canVoteHelpful($review['User']['user_id'])) {
            $state = 'access';
        }
        elseif($User->id > 0) {
            $state = 'no_access';
        }
        else {
            $state = 'register';
        }
        ?>

        <div class="jrReviewHelpful">

            <div class="jrHelpfulTitle"><?php __t("Was this review helpful to you?");?>&nbsp;</div>

            <div class="jr-review-vote jrButtonGroup jrLeft" data-review-id="<?php echo $review_id;?>" data-state="<?php echo $state;?>">

                <button href="#review-vote" class="jrVoteYes jrButton jrSmall" data-vote="yes">

                        <span class="jrIconThumbUp"></span>
                        <span class="jrButtonText" style="color: green;"><?php echo $review['Vote']['yes'];?></span>

                </button>

                <button href="#review-vote" class="jrVoteNo jrButton jrSmall" data-vote="no">

                        <span class="jrIconThumbDown"></span>
                        <span class="jrButtonText" style="color: red;"><?php echo $review['Vote']['no'];?></span>

                </button>

                <span class="jrLoadingSmall jrHidden"></span>

            </div>

        </div>

        <?php
    }

    function relatedListingsJS($listing)
    {
        # Detail page widgets
        $key = 0;

        $listingtype = Sanitize::getInt($listing['Criteria'],'criteria_id');

        $listing_id = Sanitize::getInt($listing['Listing'],'listing_id');

        $listing_title = Sanitize::getString($listing['Listing'],'title');

        $ajax_init = true;

        $target_id = $target_class = '';
        // Process related listings
        $related_listings = Sanitize::getVar($listing['ListingType']['config'],'relatedlistings',array());

        $related_listings = array_filter($related_listings);

        $created_by = Sanitize::getVar($listing['User'],'user_id');

        $field_pairs = $listing['Field']['pairs'];

        $type = 'relatedlistings';

        // Created an array of tab ids => tab indices
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        var jrRelatedWidgets = [],
            jrTabArray = {};

        <?php
        foreach($related_listings AS $key=>$related_listing):

            if(!Sanitize::getInt($related_listing,'enable',0)) continue;

            $module_id = 10000 + $listing_id + $key;

            $target_id = Sanitize::getString($related_listing,'target_id','jrRelatedListings');

            $target_class = Sanitize::getString($related_listing,'target_class');

            extract($related_listing);

            $title = str_ireplace('{title}',$listing_title,__t(Sanitize::getString($related_listing,'title'),true));

            $title = htmlspecialchars($title,ENT_QUOTES,'utf-8');

            $targetElement = $target_class ? $target_class : $target_id;

            $widgetParams = compact('key','module_id','ajax_init','listing_id','type','title','target_id','target_class');
        ?>

        jrRelatedWidgets.push(<?php echo json_encode($widgetParams);?>);

        <?php endforeach;

        // Process favorite users
        $key++;

        $module_id = 11000 + $listing_id;

        $userfavorites = Sanitize::getVar($listing['ListingType']['config'],'userfavorites',array());

        if(Sanitize::getBool($userfavorites,'enable'))
        {
            $target_id = Sanitize::getString($userfavorites,'target_id','jrRelatedListings');

            $target_class = Sanitize::getString($userfavorites,'target_class');

            extract($userfavorites);

            $id = $listing_id;

            $title = str_ireplace('{title}',$listing_title,__t(Sanitize::getString($userfavorites,'title'),true,true));

            $title = htmlspecialchars($title,ENT_QUOTES,'utf-8');

            $targetElement = $target_class ? $target_class : $target_id;

            $favorites = true;

            $widgetParams = compact('key','module_id','ajax_init','id','listingtype','type','title','target_id','target_class','favorites');
            ?>

            jrRelatedWidgets.push(<?php echo json_encode($widgetParams);?>);
        <?php
        }

        ?>
        /* ]]> */
        </script>
        <?php
    }

    /**
    * Edit, delete buttons for review discussions
    *
    */
    function discussionManager($post)
    {
        extract($post['Discussion']);

        $overrides = isset($post['ListingType']) ? Sanitize::getVar($post['ListingType'],'config') : array();

        $canEdit = $this->Access->canEditPost($user_id, $overrides);

        $canDelete = $this->Access->canDeletePost($user_id, $overrides);

        $token = cmsFramework::getCustomToken($discussion_id);

        if($canEdit || $canDelete):?>

            <div class="jrDropdown jrManage jrButton jrSmall jrIconOnly">
                <span class="jrIconManage"></span><span class="jrManageText"><?php __t("Manage");?></span>

                <ul class="jr-comment-manager jrDropdownMenu">

                    <?php if($canEdit):?>

                    <li>

                        <a class="jr-comment-edit" href="javascript:void(0)"

                            data-discussion-id="<?php echo $discussion_id;?>"

                            data-review-id="<?php echo $review_id;?>"

                            >

                            <span class="jrIconEdit"></span><span><?php __t("Edit");?></span>

                        </a>

                    </li>

                    <?php endif;?>

                    <?php if($canDelete):?>

                    <li>

                        <a class="jr-comment-delete" href="javascript:void(0)"

                            data-token="<?php echo $token ;?>" data-discussion-id="<?php echo $discussion_id;?>">

                            <span class="jrIconDelete"></span><span><?php __t("Delete");?></span>

                        </a>

                    </li>

                    <?php endif;?>

                </ul>

            </div>

        <?php endif;
    }

    function listingManager($listing)
    {
        if($this->Access->isGuest()) return;

        $overrides = $listing['ListingType']['config'];

        $listing_id = $listing['Listing']['listing_id'];

        $canEdit = $this->Access->canEditListing($listing['Listing']['user_id'],$overrides);

        $canPublish = $this->Access->canPublishListing($listing['Listing']['user_id'],$overrides);

        $canDelete = $this->Access->canDeleteListing($listing['Listing']['user_id'],$overrides);

        $canAddMedia = $this->Access->canAddAnyListingMedia($listing['User']['user_id'], $overrides, $listing_id);

        $isManager = $this->Access->isManager();

        $formToken = cmsFramework::getCustomToken($listing_id);

        if($canEdit || $canPublish || $canDelete || $isManager)
        {
        ?>

        <div class="jrDropdown jrManage jrButton jrSmall jrIconOnly">

            <span class="jrIconManage"></span><span class="jrManageText"><?php __t("Manage");?></span>

            <ul class="jr-listing-manager jrDropdownMenu">

                <?php if($canEdit):?>

                <li>
                    <?php $icon = '<span class="jrIconEditListing"></span>' ?>
                    <?php echo $this->Routes->listingEdit($icon . ' <span>' . __t("Edit Listing",true) . '</span>',$listing,array('class'=>'jr-edit'));?>
                </li>

                <?php endif;?>

                <?php if($canAddMedia):?>

                <li>
                    <?php $icon = '<span class="jrIconAddMedia"></span>'; ?>
                    <?php echo $this->Routes->mediaCreate($icon . ' <span>' . __t("Add Media",true) . '</span>',$listing,array('cat_menu_id'=>$listing['Category']['menu_id']));?>
                </li>

                <?php endif;?>

                <?php if($canEdit):?>

                <li>
                    <?php $icon = '<span class="jrIconEdit"></span>' ?>
                    <?php echo $this->Routes->listingMedia($icon . ' <span>' . __t("Edit Media",true) . '</span>',$listing,array('class'=>'jr-edit'));?>
                </li>

                <?php endif;?>

                <?php if($canPublish):?>

                <li>
                    <a href="javascript:void(0)" class="jr-listing-publish"
                        data-token="<?php echo $formToken;?>"
                        data-listing-id="<?php echo $listing_id;?>"
                        data-states='{"on":"jrIconPublished","off":"jrIconUnpublished"}'>
                        <span class="<?php echo $listing['Listing']['state'] ? 'jrIconPublished' : 'jrIconUnpublished';?>"></span>
                        <span><?php echo ($listing['Listing']['state'] ? __t("Published",true): __t("Unpublished",true));?></span>
                    </a>
                </li>

                <?php endif;?>

                <?php if($isManager):?>

                <li>
                    <a href="javascript:void(0)" class="jr-listing-feature"
                        data-token="<?php echo $formToken;?>"
                        data-listing-id="<?php echo $listing_id;?>"
                        data-states='{"on":"jrIconPublished","off":"jrIconUnpublished"}'>
                        <span class="<?php echo $listing['Listing']['featured'] ? 'jrIconPublished' : 'jrIconUnpublished';?>"></span>
                        <span><?php echo ($listing['Listing']['featured'] ? __t("Featured",true): __t("Not featured",true));?></span>
                    </a>
                </li>

                <?php endif;?>

                <?php if($canDelete):?>

                <li>
                    <a href="javascript:void(0)" class="jr-listing-delete"
                        data-token="<?php echo $formToken;?>"
                        data-listing-id="<?php echo $listing_id;?>">
                        <span class="jrIconDelete"></span>
                        <span><?php __t("Delete");?></span>
                    </a>
                </li>

                <?php endif;?>

            </ul>

        </div>
        <?php }
    }

    function listingUpgrade($listing)
    {
        $canOrder = false;

        if(isset($this->Paid) && is_object($this->Paid) && $this->Paid->canOrder($listing)) {

            $this->PaidRoutes->getPaymentLink($listing,array('lazy_load'=>true));
        }
    }

    function mediaManagerListing($listing, $media)
    {
        if($this->Access->isGuest()) return;

        extract($media['Media']);

        $formTokenKeysEdit = array('media_id'=>'media_id','listing_id'=>'listing_id','review_id'=>'review_id','extension'=>'extension','user_id'=>'user_id');

        $formToken = cmsFramework::formIntegrityToken($media['Media'],$formTokenKeysEdit,false);
        ?>

            <div class="jr-media-manager jrButtonGroup">

                <?php $this->mediaSetMain($listing, $media);?>

                <?php if($this->Access->canManageMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                    <?php if($this->Access->canPublishMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                        <button class="jr-media-publish jrButton jrIconOnly"

                            data-token="<?php echo $formToken;?>"

                            data-states='{"on":"jrIconPublished","off":"jrIconUnpublished"}'

                            data-media-id="<?php echo $media_id;?>">

                            <span title="<?php if($published):?><?php __t("Published");?><?php else:?><?php __t("Unpublished");?><?php endif;?>" class="<?php if($published):?>jrIconPublished<?php else:?>jrIconUnpublished<?php endif;?>"></span>

                        </button>

                    <?php endif;?>

                    <?php if($this->Access->canEditMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                        <button class="jr-media-edit jrButton jrIconOnly" data-media-id="<?php echo $media_id;?>">

                            <span class="jrIconEdit"></span>

                        </button>

                    <?php endif;?>

                    <?php if($this->Access->canDeleteMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                        <button class="jr-media-delete jrButton jrIconOnly" data-media-id="<?php echo $media_id;?>"

                            data-token="<?php echo $formToken;?>">

                            <span class="jrIconDelete"></span>

                        </button>

                    <?php endif;?>

                <?php endif;?>

            </div>

            <?php
    }

    function mediaManager($listing, $media)
    {
        if($this->Access->isGuest()) return;

        extract($media['Media']);

        $formTokenKeysEdit = array('media_id','media_id','listing_id'=>'listing_id','review_id'=>'review_id','extension'=>'extension','user_id'=>'user_id');

        $formToken = cmsFramework::formIntegrityToken($media['Media'],$formTokenKeysEdit,false);

        if($this->Access->canManageMedia($media_type, $user_id, $listing['Listing']['user_id'])) {
            ?>

            <div class="jrDropdown jrManage jrButton jrSmall jrIconOnly">

                <span class="jrIconManage"></span><span class="jrManageText"><?php __t("Manage");?></span>

                <ul class="jr-media-manager jrDropdownMenu">

                    <?php if($this->Access->canPublishMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                    <li>

                        <a class="jr-media-publish" href="javascript:void(0)"

                            data-token="<?php echo $formToken;?>"

                            data-states='{"on":"jrIconPublished","off":"jrIconUnpublished"}'

                            data-media-id="<?php echo $media_id;?>">

                            <span class="<?php if($published):?>jrIconPublished<?php else:?>jrIconUnpublished<?php endif;?>"></span>

                            <span><?php if($published):?><?php __t("Published");?><?php else:?><?php __t("Unpublished");?><?php endif;?></span>

                        </a>

                    </li>

                    <?php endif;?>

                    <?php if($this->Access->canEditMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                    <li>

                        <a class="jr-media-edit" href="javascript:void(0)"

                            data-media-id="<?php echo $media_id;?>">

                            <span class="jrIconEdit"></span><span><?php __t("Edit");?></span></a>

                    </li>

                    <?php endif;?>

                    <?php if($this->Access->canDeleteMedia($media_type, $user_id, $listing['Listing']['user_id'])):?>

                    <li>
                        <a class="jr-media-delete" href="javascript:void(0)"

                            data-media-id="<?php echo $media_id;?>"

                            data-token="<?php echo $formToken;?>">

                            <span class="jrIconDelete"></span><span><?php __t("Delete");?></span></a>
                    </li>

                    <?php endif;?>

                </ul>

            </div>

            <?php
        }
    }

    function mediaSetMain($listing, $media)
    {
        if($this->Access->isGuest()) return;

        extract($media['Media']);

        $formTokenKeysEdit = array('media_id','listing_id'=>'listing_id','review_id'=>'review_id','extension'=>'extension','user_id'=>'user_id');

        $formToken = cmsFramework::formIntegrityToken($media['Media'],$formTokenKeysEdit,false);

        $canSetMainMedia = !empty($listing)

            && $this->Access->canEditListing($listing['User']['user_id'])

            && in_array($media_type,array('video','photo'));

        $state = $main_media ? 'jrIconStar' : 'jrIconEmptyStar';

        $disabled = $main_media ? 'disabled="disabled"' : '';

        if($canSetMainMedia) {
            ?>

                <button class="jr-media-main jrButton" <?php echo $disabled;?>

                    data-media-id="<?php echo $media_id;?>"

                    data-listing-id="<?php echo $listing_id;?>"

                    data-token="<?php echo $formToken;?>"

                    data-states='{"on":"jrIconStar","off":"jrIconEmptyStar"}'>

                    <span title="<?php __t("Main Media");?>" class="<?php echo $state;?>"></span>

                </button>

            <?php
        }
    }

    function listingDetailButtons($listing) {
        ?>

        <div class="jrListingButtons">

        <?php

            $this->listingManager($listing);

            $this->listingUpgrade($listing);

            $this->compareCheckbox($listing);

            $this->addMedia($listing);

            $this->addReview($listing);

            $this->inquiry($listing);

            $this->claim($listing);

            $this->favorite($listing);

            ?>

        </div>

        <?php

    }

    function listPageButtons($listing, $mobile = false) {

        $this->listingManager($listing);

        if(!$mobile) $this->compareCheckbox($listing);

        if($this->Config->list_show_readmore) {

            echo $this->Routes->content(__t("Read more",true),$listing,array('class'=>'jrButton jrSmall','rel'=>'nofollow')) . " ";

        }

        if($this->Config->list_show_readreviews && Sanitize::getInt($listing['Criteria'],'state') == 1) {

            echo $this->Routes->content(__t("Read reviews",true),$listing,array('class'=>'jrButton jrSmall','rel'=>'nofollow'),'userReviews') . " ";

        }

        if($this->Config->list_show_readreviews && Sanitize::getInt($listing['Criteria'],'state') == 2) {

            echo $this->Routes->content(sprintf(__t("Comments (%s)",true), $listing['Review']['review_count']),$listing,array('class'=>'jrButton jrSmall','rel'=>'nofollow'),'userReviews') . " ";

        }

        if($this->Config->list_show_newreview && Sanitize::getInt($listing['Criteria'],'state') == 1) {

            echo $this->Routes->content(__t("Write review",true),$listing,array('class'=>'jrButton jrSmall','rel'=>'nofollow'),'reviewForm') . " ";

        }
    }
}
