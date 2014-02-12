<?php
class JreviewsLocale {

	public static function getPHP($key = '') {

		$langArray= array(
		// Listing
		'LISTING_SELECT_CAT'          			=>	__t("Select Category",true),
		'LISTING_USER_REVIEWS_TITLE_SEO'		=>	__t("User Reviews: %s",true),
		'LISTING_EDITOR_REVIEWS_TITLE_SEO'		=>	__t("Editor Reviews: %s",true),
		'LISTING_MEDIA_TITLE_SEO'				=>	__t("Media for %s",true),

		// List Pages
		'LIST_PAGE_LISTINGS_BY_TITLE_SEO'		=>	__t("Listings by %s",true),
		'LIST_PAGE_ORDERED_BY_TITLE_SEO'		=>	__t("ordered by %s",true),
		'LIST_PAGE_ORDERED_BY_DESC_TITLE_SEO'	=>	__t("ordered by %s desc",true),
		'LIST_PAGE_FAVORITES_BY_TITLE_SEO'		=>	__t("Favorites by %s",true),

		// Review
		'REVIEW_WRITTEN_BY'          			=>	__t("Reviews written by %s",true),
		'REVIEW_DETAIL_TITLE_SEO'				=>	__t("Review for %s, %s",true),

		// Favorite
		'FAVORITE_REMOVE'             			=>	__t("Remove from favorites",true),
		'FAVORITE_ADD'                			=>	__t("Add to favorites",true),
		'FAVORITE_OTHER_INTERESTED_USERS'		=> 	__t("Other users interested in {title}",true),

		// Search
		'SEARCH_RESULTS_MATCH_ANY'    			=>	__t("Search ANY selected option",true),
		'SEARCH_RESULTS_MATCH_ALL'    			=>	__t("Search ALL selected options",true),
		'SEARCH_SELECT_CATEGORY'    			=>	__t("Select Category",true),
		'SEARCH_SELECT'    						=>	__t("Select",true),

		// COMPARISON
		'COMPARISON_NO_LISTINGS'      			=>	__t("No listings selected for comparison.",true),
		'COMPARISON_DEFAULT_TITLE'    			=>	__t("%s Comparison",true),
		'COMPARISON_VALIDATE_DIFFERENT_TYPES' 	=> 	__t("Only listings with the same listing type can be compared. One or more of the listing ids in the menu has a different listing type.",true),

		// Media
		'MEDIA_MYMEDIA_ALL'           			=>	__t("Media by %s",true),
		'MEDIA_MYMEDIA_PHOTO'         			=>	__t("Photos by %s",true),
		'MEDIA_MYMEDIA_VIDEO'         			=>	__t("Videos by %s",true),
		'MEDIA_MYMEDIA_AUDIO'         			=>	__t("Audio by %s",true),
		'MEDIA_MYMEDIA_ATTACHMENT'    			=>	__t("Attachments by %s",true),
		'MEDIA_PHOTOS_FOR_LISTING'    			=>	__t("Photos for %s",true),
		'MEDIA_VIDEO_FOR_LISTING'     			=>	__t("Video for %s",true),
		'MEDIA_OWNER_PHOTOS_FOR_LISTING'      	=>	__t("Listing photos for %s",true),
		'MEDIA_USER_PHOTOS_FOR_LISTING'       	=>  __t("User photos for %s",true),
		'MEDIA_REVIEWER_PHOTOS_FOR_LISTING'   	=>	__t("Reviewer photos for %s",true),
		'MEDIA_PHOTOS_LOWER'					=>	__t("photos",true),
		'MEDIA_VIDEOS_LOWER'					=>	__t("videos",true),
		'MEDIA_ATTACHMENTS_LOWER'				=>	__t("attachments",true),
		'MEDIA_AUDIO_LOWER'						=>	__t("audio",true),
		'MEDIA_GENERIC_TYPE_LOWER'				=>	__t("this type of file",true),

		// User registration
		'USERNAME_VALID'						=> 	__t("Username is valid",true),
		'USERNAME_INVALID'						=>	__t("Username is invalid",true),

		// Notifications
		'NOTIFY_UPLOAD_PROCESSING_DONE'			=>	__t("We finished processing your upload",true),
		'NOTIFY_NEW_LISTING'					=>	__t("New listing %s",true),
		'NOTIFY_EDITED_LISTING'					=>	__t("Edited listing %s",true),
		'NOTIFY_NEW_REVIEW'						=>	__t("New review for %s",true),
		'NOTIFY_EDITED_REVIEW'					=>	__t("Edited review for %s",true),
		'NOTIFY_NEW_OWNER_REPLY'				=>	__t("Owner review reply submitted for listing %s",true),
		'NOTIFY_NEW_REPORT'						=>	__t("A new report has been submitted",true),
		'NOTIFY_NEW_REVIEW_COMMENT'				=>	__t("New comment for review: %s",true),
		'NOTIFY_EDITED_REVIEW_COMMENT'			=>	__t("Edited comment for review: %s",true),
		'NOTIFY_LISTING_CLAIM'					=>	__t("Listing claim submitted for %s",true),

		// Listing inquiries
		'INQUIRY_TITLE'							=>	__t("New inquiry for: %s",true),
		'INQUIRY_FROM'							=>	__t("From: %s",true),
		'INQUIRY_EMAIL'							=>	__t("Email: %s",true),
		'INQUIRY_PHONE'							=>	__t("Phone number: %s",true),
		'INQUIRY_LISTING'						=>	__t("Listing: %s",true),
		'INQUIRY_LISTING_LINK'					=>	__t("Listing link: %s",true),

		// Facebook Activities
		'FB_NEW_LISTING'						=>	__t("submitted a new listing titled %s",true),
		'FB_NEW_REVIEW'							=>	__t("wrote a review for %s",true),
		'FB_NEW_REVIEW_COMMENT'					=>	__t("posted a new comment",true),
		'FB_NEW_HELPFUL_VOTE'					=>	__t("liked this review for %s",true),
		'FB_PROPERTIES_WEBSITE'					=>	__t("Website",true),
		'FB_PROPERTIES_RATING'					=>	__t("Rating",true),
		'FB_READ_MORE'							=>	__t("Read more",true),
		'FB_READ_REVIEW'						=>	__t("Read review",true),
		'FB_N_RATING_STARS'						=>	__t("%s stars",true)
		);

		if($key != '') {

			return $langArray[$key];
		}

		return $langArray;
	}

	public static function getJS($key = '') {

		$langArray= array(
		// Error msgs
		'INVALID_TOKEN'              =>    s2Messages::invalidToken(),
		'ACCESS_DENIED'              =>    s2Messages::accessDenied(),
		'DB_ERROR'                   =>    s2Messages::submitErrorDb(),
		'PROCESS_REQUEST_ERROR'      =>    s2Messages::submitErrorGeneric(),

		// Validation common
		'VALIDATE_NAME'              =>    __t("Please enter your name.",true),
		'VALIDATE_EMAIL'             =>    __t("Please enter a valid email address.",true),
		'VALIDATE_USERNAME'          =>    __t("Please enter a username.",true),
		'VALIDATE_TOS'             	 =>    __t("Please accept our Terms & Conditions.",true),
		'VALID_CAPTCHA'              =>    __t("Please enter the security code.",true),
		'VALID_CAPTCHA_INVALID'      =>    __t("The security code you entered was invalid.",true),

		// General
		'CLOSE'                      =>    __t("Close",true),
		'CANCEL'					 =>    __t("Cancel",true),
		'SUBMIT'					 =>    __t("Submit",true),
		'DELETE'                      =>    __t("Delete",true),
		'DOWNLOAD'                    =>    __t("Download",true),
		'START'                       =>    __t("Start",true),
		'STOP'                        =>    __t("Stop",true),
		'CONTINUE'                    =>    __t("Continue",true),
		'BACK'                        =>    __t("Back",true),
		'PLACE_ORDER'                 =>    __t("Place Order",true),
		'PUBLISHED'                   =>    __t("Published",true),
		'UNPUBLISHED'                 =>    __t("Unpublished",true),
		'FEATURED'                    =>    __t("Featured",true),
		'NOT_FEATURED'                =>    __t("Not featured",true),
		'FRONTPAGED'                  =>    __t("Frontpaged",true),
		'NOT_FRONTPAGED'              =>    __t("Not frontpaged",true),
		'CLEAR_DATE'                  =>    __t("Clear",true),
		'FACEBOOK_PUBLISH'            =>    __t("Publish to Facebook",true),
		'LOADING'                     =>    __t("Loading...",true),
		'USERNAME_CREATE_ACCOUNT'     =>    __t("Fill in to create an account",true),
		'SHOW_MORE'                   =>    __t("show more",true),
		'HIDE_MORE'                   =>    __t("hide",true),

		// Listing
		'LISTING_SUBMIT_MODERATED'    =>    __t("Thank you for your submission. It will be published once it is verified.",true),
		'LISTING_SUBMIT_NEW'          =>    __t("Thank you for your submission.",true),
		'LISTING_SUBMIT_EDIT'         =>    __t("The listing was successfully saved.",true),
		'LISTING_SUBMIT_DISALLOWED'   =>    __t("You are not allowed to submit listings in this category.",true),
		'LISTING_SUBMIT_CAT_INVALID'  =>    __t("The category selected is invalid.",true),
		'LISTING_SUBMIT_DUPLICATE'    =>    __t("A listing with that title already exists.",true),
		'LISTING_VALIDATE_SELECT_CAT'    =>    __t("You need to select a category.",true),
		'LISTING_VALIDATE_TITLE'         =>    __t("Please enter the listing title.",true),
		'LISTING_VALIDATE_SUMMARY'       =>    __t("Please enter a summary.",true),
		'LISTING_VALIDATE_DESCRIPTION'   =>    __t("Please enter a description.",true),
		'LISTING_GO_TO'               =>    __t("Go to listing",true),
		'LISTING_DELETE_CONFIRM'      =>    __t("Are you sure you want to delete this listing?",true),
		'LISTING_DELETED'             =>    __t("The listing was deleted.",true),

		// Media
		'MEDIA_DELETE_CONFIRM'        =>    __t("Are you sure you want to delete this file?",true),
		'MEDIA_UPLOAD_DISALLOWED'     =>    __t("%s upload failed. You don't have permission to upload %s.",true),
		'MEDIA_UPLOAD_INVALID_EXT'    =>    __t("%s has an invalid extension.",true),
		'MEDIA_UPLOAD_INVALID_EXT_LIST'=>   __t("File has an invalid extension, it should be one of %s.",true),
		'MEDIA_UPLOAD_PHOTO_LIMIT'    =>    __t("Photo upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_VIDEO_LIMIT'    =>    __t("Video upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_ATTACHMENT_LIMIT'=>   __t("Attachment upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_AUDIO_LIMIT'    =>    __t("Audio upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_NOT_WRITABLE'   =>    __t("Upload directory is not writable.",true),
		'MEDIA_UPLOAD_NOT_UPLOADED'   =>    __t("No files were uploaded.",true),
		'MEDIA_UPLOAD_ZERO_SIZE'      =>    __t("%s is empty.",true),
		'MEDIA_UPLOAD_MAX_SIZE'       =>    __t("%s is too large. The maximum allowed size is %sMB.",true),
		'MEDIA_EMBED_VIDEO_NOT_FOUND' =>    __t("A video was not found at the specified url.",true),
		'MEDIA_EMBED_DISALLOWED'      =>    __t("Video linking is disabled",true),
		'MEDIA_DOWNLOAD_NOT_FOUND'    =>    __t("File not found",true),
		'MEDIA_UPLOAD_VIDEO_INCOMPLETE_SETUP' =>  __t("Cannot complete video upload because remote storage and video encoding setup is incomplete in Media Settings.",true),
		'MEDIA_UPLOAD_AUDIO_INCOMPLETE_SETUP' =>  __t("Cannot complete audio upload because remote storage and audio encoding setup is incomplete in Media Settings.",true),
		'MEDIA_UPLOAD_TYPE_ERROR'		=>	__t("{file} has an invalid extension. Allowed extensions: {extensions}.",true),
		'MEDIA_UPLOAD_SIZE_ERROR'		=>	__t("{file} is too large, maximum file size is {sizeLimit}.",true),
		'MEDIA_UPLOAD_EMPTY_ERROR'		=>	__t("{file} is empty.",true),
		'MEDIA_UPLOAD_NOFILE_ERROR'		=>	__t("No files to upload.",true),
		'MEDIA_UPLOAD_ONLEAVE'			=>	__t("The files are being uploaded, if you leave now the upload will be cancelled.",true),
		'MEDIA_UPLOAD_URL_NOLINKING'	=>	__t("The remote site rejected the connection to download the file.",true),
		'MEDIA_UPLOAD_URL_INVALID'		=>	__t("The upload URL is not valid, please try again.",true),
		'MEDIA_PHOTO_PUBLISH_LIMIT'		=>	__t("You've reached your photo limit for this listing. To publish this photo you'll first need to unpublish one of the other photos.",true),
		'MEDIA_VIDEO_PUBLISH_LIMIT'		=>	__t("You've reached your video limit for this listing. To publish this video you'll first need to unpublish one of the other videos.",true),
		'MEDIA_ATTACHMENT_PUBLISH_LIMIT'=>	__t("You've reached your attachment limit for this listing. To publish this attachment you'll first need to unpublish one of the other attachments.",true),
		'MEDIA_AUDIO_PUBLISH_LIMIT'		=>	__t("You've reached your audio limit for this listing. To publish this audio track you'll first need to unpublish one of the other audio tracks.",true),

		// Review
		'REVIEW_WRITTEN_BY'          =>     __t("Reviews written by %s",true),
		'REVIEW_DUPLICATE'            =>    __t("You already submitted a review.",true),
		'REVIEW_NOT_OWN_LISTING'      =>    __t("You are not allowed to review your own listing.",true),
		'REVIEW_SUBMIT_NEW'           =>    __t("Thank you for your submission",true),
		'REVIEW_SUBMIT_NEW_REFRESH'   =>    __t("Thank you for your submission. Refresh the page to see it.",true),
		'REVIEW_SUBMIT_EDIT'          =>    __t("Your changes were saved.",true),
		'REVIEW_SUBMIT_EDIT_REFRESH'  =>    __t("Your changes were saved, refresh the page to see them.",true),
		'REVIEW_VALIDATE_TITLE'          =>    __t("Please enter a title for the review.",true),
		'REVIEW_VALIDATE_COMMENT'        =>    __t("Please enter your comment.",true),
		'REVIEW_VALIDATE_CRITERIA'       =>    __t("You are missing a rating in %s criteria.",true),

		// Owner Replies
		'OWNER_REPLY_DELETE_CONFIRM'  =>    __t("Are you sure you want to delete your reply?",true),
		'OWNER_REPLY_DELETED'		  =>	__t("The reply was deleted.",true),

		// Review Votes
		'REVIEW_VOTE_NOT_OWN'         =>    __t("You are not allowed to vote on your own review.",true),
		'REVIEW_VOTE_REGISTER'        =>    __t("Login or register to vote.",true),
		'REVIEW_VOTE_DUPLICATE'       =>    __t("You already voted.",true),

		// Discussions
		'DISCUSSION_SUBMIT_NEW'       =>    __t("Thank you for your submission.",true),
		'DISCUSSION_SUBMIT_MODERATED' =>    __t("Thank you for your submission. It will be published once it is verified.",true),
		'DISCUSSION_SUBMIT_EDIT'      =>    __t("Your changes were saved.",true),
		'DISCUSSION_VALIDATE_COMMENT'    =>    __t("Please enter your comment.",true),
		'DISCUSSION_DELETE_CONFIRM'   =>    __t("Are you sure you want to delete this comment?",true),
		'DISCUSSION_DELETED'          =>    __t("The comment was deleted",true),

		// Claim
		'CLAIM_SUBMIT'                =>    __t("Your claim was submitted, thank you.",true),
		'CLAIM_VALIDATE_MESSAGE'         =>    __t("The message is empty.",true),
		'CLAIM_REGISTER'              =>    __t("Please register to claim this listing",true),

		// Favorites
		'FAVORITE_REMOVE'             =>    __t("Remove from favorites",true),
		'FAVORITE_ADD'                =>    __t("Add to favorites",true),
		'FAVORITE_REGISTER'           =>    __t("Register to add this entry to your favorites",true),

		// Inquiry
		'INQUIRY_SUBMIT'              =>    __t("Your inquiry has been submitted.",true),

		'OWNER_REPLY_NEW'             =>    __t("Your reply was submitted and has been approved.",true),
		'OWNER_REPLY_MODERATE'        =>    __t("Your reply was submitted and will be published once it is verified.",true),
		'OWNER_REPLY_DUPLICATE'       =>    __t("A reply for this review already exists.",true),
		'OWNER_REPLY_VALIDATE_REPLY'     =>    __t("The reply is empty.",true),

		// Report
		'REPORT_INAPPROPRIATE'        =>    __t("Report as inappropriate",true),
		'REPORT_SUBMIT'               =>    __t("Your report was submitted, thank you.",true),
		'REPORT_VALIDATE_MESSAGE'        =>    __t("The message is empty.",true),

		// Fields
		'FIELD_SELECT'                =>    __t("-- Select --",true),
		'FIELD_SELECT_FIELD'          =>    __t("-- Select %s --",true),
		'FIELD_NO_RESULTS'            =>    __t("No results found, try a different spelling.",true),
		'FIELD_AUTOCOMPLETE_HELP'     =>    __t("Start typing for suggestions",true),
		'FIELD_ADD_OPTION'            =>    __t("Add",true),

		// Comparison
		'COMPARE_HEADING'             =>    __t("Compare",true),
		'COMPARE_COMPARE_ALL'         =>    __t("Compare All",true),
		'COMPARE_REMOVE_ALL'          =>    __t("Remove All",true),
		'COMPARE_SELECT_MORE'         =>    __t("You need to select more than one listing for comparison.",true),
		'COMPARE_SELECT_MAX'          =>    __t("You selected maximum number of listings for comparison.",true),

		// GEOMAPS

		'GEOMAPS_MAPIT'               =>    __t("Map it",true),
		'GEOMAPS_CLEAR_COORDINATES'   =>    __t("Clear coordinates",true),
		'GEOMAPS_STREEVIEW_UNAVAILABLE'=>   __t("Street view not available for this address.",true),
		'GEOMAPS_CANNOT_GEOCODE'      =>    __t("Address could not be geocoded. Modify the address and click on the Geocode Address button to try again.",true),
		'GEOMAPS_DRAG_MARKER'         =>    __t("Drag the marker to fine-tune the geographic location on the map.",true),
		'GEOMAPS_DIRECTIONS_INVALID_ADDRESS'	=> __t("No corresponding geographic location could be found for one of the specified addresses. This may be due to the fact that the address is relatively new, or it may be incorrect.",true),
		'GEOMAPS_ENTER_LOCATION'      =>    __t("Enter a location",true),

		// PAIDLISTINGS
		'PAID_FREE_LIMIT_REACHED'     =>    __t("You have exceeded your quota of free listings for the selected plan.",true),
		'PAID_INVALID_PLAN'           =>    __t("The plan selected for this listing is not valid.",true),
		'PAID_DUPLICATE_ORDER'        =>    __t("There is already an unpaid order for this listing.",true),
		'PAID_VALIDATE_TOS'           =>    __t("You need to agree to the Terms of Service.",true),
		'PAID_ACCOUNT_SAVED'		  =>	__t("Your account information was saved.",true)
		);

		if($key != '') {

			return $langArray[$key];
		}

		return $langArray;
	}
}