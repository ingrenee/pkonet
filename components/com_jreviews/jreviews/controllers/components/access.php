<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AccessComponent extends S2Component {

    var $gid = null;
    var $is_guest;
    var $guest = array(1);
    var $registered = array(2);
    var $editors = array(4,5,6,7,8); // Includes editor and above
    var $publishers = array(5,6,7,8); // Includes publisher and above
    var $managers = array(6,7,8); // Includes mabager and above
    var $admins = array(7,8); // admin, superadmin
    var $members = array(2,3,4,5,6,7,8); // Registered users and above
    var $guests = array(1,2,3,4,5,6,7,8);
    var $authorizedViewLevels = array();

    // jReviews access
    var $canAddMeta = null;
    var $canAddReview = null;

    var $Config;

	var $_user;

	var $__settings = array(
		'addnewaccess',
		'moderation_item',
		'editaccess',
		'listing_publish_access',
		'listing_delete_access',
		'addnewwysiwyg',
		'addnewmeta',
		'addnewaccess_reviews',
		'moderation_reviews',
		'user_vote_public',
		'editaccess_reviews',
		'media_access_view_photo_listing',
		'media_access_view_video_listing',
		'media_access_view_attachment_listing',
		'media_access_view_audio_listing',
		'media_access_view_photo_review',
		'media_access_view_video_review',
		'media_access_view_attachment_review',
		'media_access_view_audio_review',
		'media_access_submit_photo_listing',
		'media_access_submit_video_listing',
		'media_access_submit_attachment_listing',
		'media_access_submit_audio_listing',
		'media_access_submit_photo_review',
		'media_access_submit_video_review',
		'media_access_submit_attachment_review',
		'media_access_submit_audio_review',
		'media_access_moderate_photo',
		'media_access_moderate_video',
		'media_access_moderate_attachment',
		'media_access_moderate_audio',
        'media_access_upload_url_listing',
        'media_access_upload_url_review',
		'media_access_like_photo',
		'media_access_like_video',
		'media_access_like_attachment',
		'media_access_like_audio',
		'media_access_edit',
		'media_access_delete',
		'media_access_publish',
		'addnewaccess_posts',
		'moderation_posts',
		'post_edit_access',
		'post_delete_access',
		'moderation_owner_replies'
		);

	var $__settings_overrides = array(
		'addnewaccess',
		'addnewaccess_reviews',
		'media_access_view_photo_listing',
		'media_access_view_video_listing',
		'media_access_view_attachment_listing',
		'media_access_view_audio_listing',
		'media_access_view_photo_review',
		'media_access_view_video_review',
		'media_access_view_attachment_review',
		'media_access_view_audio_review',
		'media_access_submit_photo_listing',
		'media_access_submit_video_listing',
		'media_access_submit_attachment_listing',
		'media_access_submit_audio_listing',
		'media_access_submit_photo_review',
		'media_access_submit_video_review',
		'media_access_submit_attachment_review',
		'media_access_submit_audio_review',
		'media_access_moderate_photo',
		'media_access_moderate_video',
		'media_access_moderate_attachment',
		'media_access_moderate_audio',
        'media_access_upload_url_listing',
        'media_access_upload_url_review'
		);

    function __construct()
    {
        parent::__construct();

        $this->_user = cmsFramework::getUser();
    }

    function startup(&$controller)
    {
        $this->cmsVersion = $controller->cmsVersion;

        $this->authorizedViewLevels = array_unique(JAccess::getAuthorisedViewLevels($this->_user->id));
    }

    function init(&$Config)
    {
        $this->Config = &$Config;

        $this->gid = $this->getGroupId($this->_user->id);

        Configure::write('JreviewsSystem.Access',$this);
    }

    function showCaptcha()
    {
        if($this->Config->security_image && ($this->isGuest() || $this->isRegistered())) {

            return $this->in_groups($this->Config->security_image);
        }

        return false;
    }

    function isGuest()
    {
        return $this->is_guest;
    }

    function isRegistered()
    {
        return $this->in_groups($this->registered);
    }

    function isAdmin()
    {
        return $this->in_groups($this->admins);
    }

    function isEditor()
    {
        return $this->in_groups($this->editors);
    }

    function isManager()
    {
        return $this->in_groups($this->managers);
    }

    function isMember()
    {
        return $this->in_groups($this->members);
    }

    function isPublisher()
    {
        return $this->in_groups($this->publishers);
    }

    function isJreviewsEditor($user_id)
    {
        $jr_editor_ids = is_integer($this->Config->authorids) ? array($this->Config->authorids) : explode(',',$this->Config->authorids);
        if($this->Config->author_review && $user_id > 0 && in_array($user_id,$jr_editor_ids)){
            return true;
        }
        return false;
    }

	/************************************************
	 *					LISTINGS					*
	 ************************************************/

    function canEditListing($owner_id = null, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'editaccess', $overrides);
    }

    function canDeleteListing($owner_id, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'listing_delete_access', $overrides);
    }

	function canPublishListing($owner_id, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'listing_publish_access', $overrides);
    }

    function canClaimListing(&$listing)
    {
        return $this->Config->claims_enable
            && $this->_user->id > 0
            // && ($listing['Listing']['user_id'] != $this->_user->id)
            && $listing['Claim']['approved']<=0
            && (
                $this->Config->claims_enable_userids == ''
                || (
                    $this->Config->claims_enable_userids != ''
                    &&
                    in_array($listing['Listing']['user_id'],explode(',',$this->Config->claims_enable_userids))
                )
            )
        ;
    }

    function canAddListing($override = null)
    {
        $groups = $this->getOverride($override, 'addnewaccess');

        return $groups !='' && $this->in_groups($groups);
    }

    function canAddMeta()
    {
        return $this->Config->addnewmeta!='' && $this->in_groups($this->Config->addnewmeta);
    }

    function loadWysiwygEditor()
    {
        return $this->in_groups(Sanitize::getVar($this->Config,'addnewwysiwyg'));
    }

    function moderateListing()
    {
        return $this->Config->moderation_item!='' && $this->in_groups($this->Config->moderation_item);
    }


	/************************************************
	 *					REVIEWS						*
	 ************************************************/
	function canAddReview($owner_id = null)
    {
        if(
            // First check the access groups
            (!$this->in_groups($this->Config->addnewaccess_reviews) || $this->Config->addnewaccess_reviews == 'none')
            ||
            // If it's not a jReviewsEditor then check the owner listing
            (!$this->isJreviewsEditor($this->_user->id) && $this->Config->user_owner_disable && !is_null($owner_id) && $owner_id != 0 && $this->_user->id == $owner_id)
        ) {
            return false;
        }
        return true;
    }

    function canEditReview($owner_id, $overrides = null)
    {
        return $this->canMemberDoThis($owner_id,'editaccess_reviews', $overrides);
    }

    function moderateReview()
    {
        return $this->Config->moderation_reviews!='' && $this->in_groups($this->Config->moderation_reviews);
    }

    function canVoteHelpful($reviewer_id = null)
    {
        if($reviewer_id && $reviewer_id == $this->_user->id) return false;
        return $this->Config->user_vote_public!='' && $this->in_groups($this->Config->user_vote_public);
    }

	/************************************************
	 *					MEDIA GENERAL				*
	 ************************************************/
	function moderateMedia($media_type)
	{
		$setting = Sanitize::getVar($this->Config,'media_access_moderate_'.$media_type);
        return  $setting != '' && $this->in_groups($setting);
	}

	function canManageMedia($media_type, $owner_id = null, $listing_owner_id = null) {

        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

		return $this->canEditMedia($media_type,$owner_id) ||
            $this->canDeleteMedia($media_type,$owner_id) ||
            $this->canPublishMedia($media_type, $owner_id);

	}

	function canEditMedia($media_type, $owner_id = null, $listing_owner_id = null)
    {
        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_edit');
    }

    function canDeleteMedia($media_type, $owner_id, $listing_owner_id = null)
    {
        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_delete');
    }

    function canPublishMedia($media_type, $owner_id, $listing_owner_id = null)
    {

        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_publish');
    }

	function canApproveMedia()
	{
		return $this->isEditor();
	}

	function canVoteMedia($media_type, $voter_id = null)
	{
        if($voter_id && $voter_id == $this->_user->id) return false;
		$setting = Sanitize::getVar($this->Config,'media_access_like_'.$media_type);
        return  $setting != '' && $this->in_groups($setting);
	}

	/************************************************
	 *					MEDIA LISTINGS				*
	 ************************************************/
	function canAddAnyListingMedia($owner_id = null, $overrides = array(), $listing_id = null)
	{
		$allowed_types = array();

		if(!is_array($overrides)) {
			$overrides = json_decode($overrides,true);
		}

		$media_types = array('photo','video','attachment','audio');

		foreach($media_types AS $media_type)
		{
			if($this->canAddListingMedia($media_type, $owner_id, $overrides, $listing_id)) {
				array_push($allowed_types,$media_type);
			}
		}

		if(!empty($allowed_types)) return $allowed_types;

		return false;
	}

    function canAddMediaFromUrl($location = 'listing', $override = null)
    {
        $groups = $this->getOverride($override, 'media_access_upload_url_'.$location);

        return $groups !='' && $this->in_groups($groups);
    }

	function canAddListingMedia($media_type, $owner_id, $overrides = array(), $listing_id = null)
	{
		$override = $this->setOverride($overrides, "media_access_submit_{$media_type}_listing", -1);

        $groups = !empty($override) && $override != -1 ? $override : Sanitize::getVar($this->Config,"media_access_submit_{$media_type}_listing");

        if(!is_null($listing_id)) {

            // Checks for previously submitted listings as guest to verify if the user can submit media for them

            $session_ids = cmsFramework::getSessionVar('listings','jreviews');

            if(isset($session_ids[$listing_id]) && cmsFramework::getCustomToken($listing_id) == $session_ids[$listing_id]) {

                return $groups !='' && $this->in_groups($groups);
            }
        }

		$check_listing_owner = Sanitize::getString($this->Config,"media_access_submit_{$media_type}_listing_owner");

        if($check_listing_owner == 1 && !$this->isAdmin()) {

			return $this->canMemberDoThis($owner_id,"media_access_submit_{$media_type}_listing");
		}

		return $groups !='' && $this->in_groups($groups);
	}

	/************************************************
	 *					MEDIA REVIEWS				*
	 ************************************************/
	function canAddAnyReviewMedia($reviewer_id = null, $overrides = array(), $review_id = null)
	{
		$allowed_types = array();

		$media_types = array('photo','video','attachment','audio');

		foreach($media_types AS $media_type)
		{
			if($this->canAddReviewMedia($media_type, $reviewer_id, $overrides, $review_id)) {
				array_push($allowed_types,$media_type);
			}
		}

		if(!empty($allowed_types)) return $allowed_types;

		return false;
	}

	function canAddReviewMedia($media_type, $reviewer_id, $overrides = array(), $review_id = null)
	{
		$override = $this->setOverride($overrides, "media_access_submit_{$media_type}_review", -1);

        if(!is_null($review_id)) {

            $session_ids = cmsFramework::getSessionVar('reviews','jreviews');

            if(isset($session_ids[$review_id]) && cmsFramework::getCustomToken($review_id) == $session_ids[$review_id]) {

                $groups = explode(',',Sanitize::getString($this->Config,"media_access_submit_{$media_type}_review"));

                return $groups !='' && $this->in_groups($groups);
            }

        }

        return $this->canMemberDoThis($reviewer_id,"media_access_submit_{$media_type}_review");
	}

	function getOverride($override, $key) {

		if(is_array($override) && count($override) == 1 && (int) $override[0] == -1) {
			$override = -1;
		}

		return !is_null($override) && $override != -1 ? $override : $this->Config->$key;

	}

	function setOverride($overrides, $key, $default = -1) {

		$value = Sanitize::getVar($overrides,$key,$default);

		if(is_array($value) && count($value) == 1 && (int) $value[0] == -1) {
			$value = -1;
		}

		if((int) $value != -1)  {
			$this->Config->$key = $value;
		}

		return $this->Config->$key;
	}

	/************************************************
	 *					REVIEW COMMENTS				*
	 ************************************************/

    function canAddPost()
    {
        return $this->Config->addnewaccess_posts!='' && $this->in_groups($this->Config->addnewaccess_posts);
    }

    function canEditPost($owner_id, $override = null)
    {
        return $this->canMemberDoThis($owner_id,'post_edit_access',$override);
    }

    function canDeletePost($owner_id, $override = null)
    {
        return $this->canMemberDoThis($owner_id,'post_delete_access',$override);
    }

    function moderatePost()
    {
        return $this->Config->moderation_posts!='' && $this->in_groups($this->Config->moderation_posts) ? true : false;
    }

	/************************************************
	 *					OWNER REPLIES				*
	 ************************************************/

	function canAddOwnerReply(&$listing,&$review)
    {
        return $this->Config->owner_replies
            && (isset($listing['Claim']) && $listing['Claim']['approved'] == 1 || $this->isEditor()) // Only approved claims or editor group and above
            && $this->_user->id >0
            && isset($listing['Listing']['user_id'])
            && $listing['Listing']['user_id'] == $this->_user->id
            && $review['Review']['editor']==0
            && $review['Review']['owner_reply_approved']<=0
        ;
    }

    function canDeleteOwnerReply($listing)
    {
        return $this->isManager() ||
            ($this->_user->id > 0
            && isset($listing['Listing']['user_id']) && $this->Config->owner_replies
            && $listing['Listing']['user_id'] == $this->_user->id)
        ;
    }

    function moderateOwnerReply()
    {
        return $this->Config->moderation_owner_replies !='' && $this->in_groups($this->Config->moderation_owner_replies) ? true : false;
    }


	// Wrapper functions

    function canMemberDoThis($owner_id, $config_key, $override = null)
    {
        $setting = $override ? $this->getOverride(Sanitize::getVar($override,$config_key), $config_key) : $this->Config->{$config_key};

        $allowedGroups = is_array($setting) ?
                            $setting
                            :
                            explode(',',$setting);

        $new_user_id = UserAccountComponent::getUserId();

        if($new_user_id && $owner_id == $new_user_id) {

            $gid = $this->getGroupId($new_user_id);

            return $this->in_groups($allowedGroups, $gid);
        }
        elseif($this->_user->id == 0 || empty($this->gid)) {

            return false;

        } elseif (
            ($this->in_groups($this->editors) && $this->in_groups($allowedGroups))
            ||
            ($this->_user->id == $owner_id && $owner_id >0 && $this->in_groups($allowedGroups))
        ) {

            return true;
        }

        return false;
    }

    function isAuthorized($access)
    {
        return in_array($access,$this->authorizedViewLevels);
    }

    function getAccessId()
    {
        return cleanIntegerCommaList($this->_user->aid);
    }

    function getAccessLevels()
    {
		return implode(',',$this->authorizedViewLevels);
    }

    function in_groups($groups, $gid = null)
    {
        $gid = is_null($gid) ?  $this->gid : $gid;

        if($groups == 'all') return true;

        !is_array($groups) and $groups = explode(',',$groups);

        $check = array_intersect($gid,$groups);

        return !empty($check);
    }

    function getGroupId($user_id)
    {
        if($groups = cmsFramework::getSessionVar('gid','jreviews')) {

            return $groups;
        }

        if (!$user_id) {
            return array(1);
        }

        $db = cmsFramework::getDB();

        $query = "
            SELECT
                group_id
            FROM
                #__user_usergroup_map
            WHERE
                user_id = " . $user_id
        ;

        $db->setQuery($query);

        $groups = method_exists($db,'loadResultArray') ? $db->loadResultArray() : $db->loadColumn();

        cmsFramework::setSessionVar('gid',$groups,'jreviews');

        return $groups;
    }
}
