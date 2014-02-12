<?php
class JreviewsLocale {

	public static function getPHP($key = '') {

		$langArray= array(
		// General
		'CACHE_REGISTRY_CLEARED'			=>	__a("The cache and file registry were cleared.",true),
		'REVIEWER_RANKS_REBUILT'			=>	__a("The reviewer ranks table was successfully rebuilt.",true),
		'MEDIA_COUNTS_UPDATE'				=>	__a("The media counts were successfully recalculated.",true),
		'REVIEWS_RESYNC_RATINGS_COMPLETE'	=>	__a("Ratings averages update complete.",true),
		'LISTINGS_RESYNC_RATINGS_COMPLETE'	=>	__a("Listings totals update complete.",true),
		'DB_ERROR'                  		=>	s2Messages::submitErrorDb(),
		'PROCESS_REQUEST_ERROR'				=>  s2Messages::submitErrorGeneric(),

		// Installation
		'INSTALL_FIX_PLUGIN_FAILED'			=>	__a("There was a problem updating the database or copying the plugin files. Make sure the Joomla plugins/content folder is writable.",true),
		'INSTALL_FIX_LISTING_FIELD_FAILED'	=>	__a("There was a problem fixing one or more of the content fields",true),
		'INSTALL_FIX_REVIEW_FIELD_FAILED'	=>	__a("There was a problem fixing one or more of the review fields",true),

		// Field
		'FIELD_GROUPS_NOT_CREATED'			=>	__a("You need to create at least one field group using the Field Groups Manager before you can create custom fields.",true),
		'FIELD_GROUP_TYPE_NOT_CREATED'		=>	__a("To add %1\$s custom fields you first need to create a %1\$s field group.",true),

		// Listing
		'LISTING_SELECT_CAT'          		=>  __a("Select Category",true),
		'LISTING_APPROVED'					=>	__a("Your listing has been approved",true),
		'LISTING_REJECTED'					=>	__a("Your listing has been rejected",true),
		'LISTING_HELD'						=>	__a("Your listing has been reviewed, but it is still pending moderation",true),

		// Review
		'REVIEW_APPROVED'					=>	__a("Your review has been approved",true),
		'REVIEW_REJECTED'					=>	__a("Your review has been rejected",true),
		'REVIEW_HELD'						=>	__a("Your review has been reviewed, but still pending moderation",true),


		// Owner Reply
		'OWNER_REPLY_APPROVED'				=>	__a("Your reply has been approved",true),
		'OWNER_REPLY_REJECTED'				=>	__a("Your reply has been rejected",true),
		'OWNER_REPLY_HELD'					=>	__a("Your reply has been reviewed, but still pending moderation",true),

		// Claims
		'CLAIM_APPROVED'					=>	__a("Your claim was approved",true),
		'CLAIM_REJECTED'					=>	__a("Your claim has been rejected",true),
		'CLAIM_HELD'						=>	__a("Your claim has been reviewed, but still pending moderation",true),

		// Review comments
		'COMMENT_APPROVED'					=>	__a("Your comment has been approved",true),
		'COMMENT_REJECTED'					=>	__a("Your comment has been rejected",true),

		// Media
		'MEDIA_APPROVED'					=>	__a("Your uploaded media has been approved",true),
		'MEDIA_REJECTED'					=>	__a("Your uploaded media has been rejected",true),
		'MEDIA_HELD'						=>	__a("Your uploaded media has been reviewed, but still pending moderation",true),

		//  Updater
		'UPDATER_FAILED_ADDON_FOLDER'		=>	__a("Creating the addons folder failed. You need to manually create the /components/com_jreviews_addons/ folder.",true),
		'UPDATER_FAILED_ADDON_SUBFOLDERS'	=>	__a("It's not possible to create new folders inside /components/com_jreviews_addons/. Please change permissions or ownership and try again.",true),
		'UPDATER_CURL_REQUIRED'				=> 	__a("The php CURL extension is required. Make sure both curl_init and curl_exec are enabled.",true),
		);

		if($key != '') {
			return $langArray[$key];
		}

		return $langArray;
	}

	public static function getJS($key = '') {

		$langArray= array(
		// Error msgs
		'INVALID_TOKEN'             	=>	s2Messages::invalidToken(),
		'ACCESS_DENIED'             	=>	s2Messages::accessDenied(),
		'DB_ERROR'                  	=>	s2Messages::submitErrorDb(),
		'PROCESS_REQUEST_ERROR'			=>	s2Messages::submitErrorGeneric(),

		// General
		'CANCEL'						=>	__a("Cancel",true),
		'SUBMIT'						=>	__a("Submit",true),
		'EDIT'                      	=>	__a("Edit",true),
		'SAVE'                      	=>	__a("Save",true),
		'APPLY'                     	=>	__a("Apply",true),
		'APPLY_SUCCESS'             	=>	__a("The changes were saved.",true),
		'DELETE'                    	=>	__a("Delete",true),
		'START'                     	=>	__a("Start",true),
		'STOP'                      	=>	__a("Stop",true),
		'CLEAR_DATE'                	=>	__a("Clear",true),
		'NEW_VERSION_AVAILABLE'     	=>	__a("New version available",true),

		// Messages
		'SETTINGS_SAVED'            	=>	__a("New settings saved successfully.",true),

		// Moderation
		'MODERATION_HELD'           	=>	__a("This submission will remain in moderation pending further action.",true),

		// License
		'LICENSE_VALIDATE'				=>	__a("Please enter a license number.",true),

		// Listing
		'LISTING_VALIDATE_SELECT_CAT'	=>	__a("Please enter the listing title.",true),
		'LISTING_SUBMIT_CAT_INVALID'  	=>  __a("The category selected is invalid.",true),
		'LISTING_VALIDATE_SUMMARY'      =>  __a("Please enter a summary.",true),
		'LISTING_VALIDATE_DESCRIPTION'	=>  __a("Please enter a description.",true),

		// Listing Type
		'LISTING_TYPE_VALIDATE_TITLE'	=>	__a("Please enter the listing type title.",true),
		'LISTING_TYPE_VALIDATE_CRITERIA'=>	__a("Add at least one criteria to rate your items.",true),
		'LISTING_TYPE_VALIDATE_WEIGHTS'	=>	__a("The criteria weights have to add up to 100.",true),
		'LISTING_TYPE_VALIDATE_CRITERIA_WEIGHT_COUNT'	=> __a("The number of criteria does not match the number of weights. Check your entries.",true),
		'LISTING_TYPE_VALIDATE_CRITERIA_TOOLTIP_COUNT'	=> __a("There are more tooltips than criteria, please remove the extra tooltips. You may leave blank lines for tooltips if there's a criteria that will not have a tooltip, but the number of lines must match the number of criteria",true),
		'LISTING_TYPE_VALIDATE_CRITERIA_REQUIRED_COUNT'	=>	__a("The number of criteria does not match the number of the 'Required' fields.",true),
		'LISTING_TYPE_EDIT_NOT_EMPTY'	=>	__a("There are %s reviews in the system for listings using this listing type which prevent you from changing the number of criteria. You can only edit the criteria labels, but not add or remove criteria unless you first delete the existing reviews.",true),
		'LISTING_TYPE_REMOVE_NOT_EMPTY'	=>	__a("You have at least one category using this listing type. You first need to remove the categories from the JReviews setup in the Category Manager in order to be able to delete the listing type.",true),
		'LISTING_TYPE_VALIDATE_COPY_SELECT'	=>	__a("Select the listing type you want to copy.",true),

		// Fields
		'FIELD_SELECT'                	=>  __a("-- Select --",true),
		'FIELD_SELECT_FIELD'          	=>  __a("-- Select %s --",true),
		'FIELD_NO_RESULTS'            	=>  __a("No results found, try a different spelling.",true),
		'FIELD_AUTOCOMPLETE_HELP'     	=>  __a("Start typing for suggestions",true),
		'FIELD_ADD_OPTION'            	=>  __a("Add",true),
		'FIELD_VALIDATE_LOCATION'		=>	__a("Please select a location for this field.",true),
		'FIELD_VALIDATE_TYPE'			=>	__a("Please select a field type.",true),
		'FIELD_VALIDATE_GROUP'			=>	__a("Please select a field group for this field.",true),
		'FIELD_VALIDATE_NAME'			=>	__a("Please enter a field name.",true),
		'FIELD_VALIDATE_DUPLICATE'		=>	__a("A field with that name already exists for either content or reviews. Each custom field name has to be unique.",true),
		'FIELD_VALIDATE_TITLE'			=> 	__a("Please enter a field title.",true),
		'FIELD_VALIDATE_ZERO_LENGTH'	=>	__a("New maximum characters value needs to be higher than zero.",true),
		'FIELD_VALIDATE_STORED_LENGTH'	=>	__a("New maximum characters value needs to be higher than maximum length of data already stored in the field.",true),
		'FIELD_VALIDATE_MAX_DB_LENGTH'	=>	__a("There was a problem changing the maximum length.",true),

		// Fieldoptions
		'FIELDOPTION_VALIDATE_TEXT_VALUE' =>  __a("Please enter both Text and Value fields.",true),
		'FIELDOPTION_VALIDATE_DUPLICATE'  =>  __a("An option with this value already exists for this field.",true),

		// Groups
		'GROUP_VALIDATE_TYPE'         	=>	__a("Please select a Field Group type.",true),
		'GROUP_VALIDATE_NAME'         	=>	__a("Please enter the Field Group name.",true),
		'GROUP_VALIDATE_TITLE'        	=>	__a("Please enter the Field Group title.",true),
		'GROUP_DELETE_NOT_EMPTY'		=>	__a("There are %s fields associated with this group. You need to delete those first.",true),

		// Directories
		'DIRECTORY_VALIDATE_NAME'     =>    __a("Please enter the Directory name.",true),
		'DIRECTORY_VALIDATE_TITLE'    =>    __a("Please enter the Directory title.",true),
		'DIRECTORY_DELETE_NOT_EMPTY'		=>	__a("You have categories using this directory, first you need to delete them or change the directory they have been assigned to.",true),

		// Categories
		'CATEGORY_VALIDATE_LISTING_TYPE' => __a("Please select a Listing Type.",true),
		'CATEGORY_VALIDATE_DIRECTORY' =>    __a("Please select a Directory.",true),
		'CATEGORY_VALIDATE_CATEGORY'  =>    __a("Please select one or more categories from the list.",true),
		'CATEGORY_REMOVE_NOT_EMPTY'			=>	__a("Some of the categories you are trying to delete have reviews and therefore cannot be deleted. Please choose categories without reviews or delete the reviews first.",true),

		// Updater
		'UPDATER_INSTALL_CONFIRM'     =>    __a("You need to check 'Updates' to confirm you understand that updating will overwrite existing addon files.",true),
		'UPDATER_ADDON_REMOVE_CONFIRM'=>    __a("Are you sure you want to remove this add-on?",true),
		'UPDATER_INSTALLED'           =>    __a("Installed",true),
		'UPDATER_REMOVED'             =>    __a("Removed",true),
		'UPDATER_COMPONENT_SUCCESS'   =>    __a("Component upgraded successfully.",true),
		'UPDATER_PACKAGE_TRANSFERING' =>    __a("Please wait while the package gets transferred to your site.",true),
		'UPDATER_PACKAGE_EXTRACT_OK'  =>    __a("New package extracted successfully.",true),
		'UPDATER_PACKAGE_EXTRACT_FAIL'=>    __a("There was a problem extracting the new package.",true),
		'UPDATER_RELOAD_PAGE'         =>    __a("Reload the page for all changes to take effect.",true),
		'UPDATER_ADDON_REMOVED_OK'    =>    __a("The addon was successfully removed.",true),
		'UPDATER_ADDON_REMOVED_FAIL'  =>    __a("There was a problem removing the add-on folder.",true),
		'UPDATER_SERVER_VALIDATION_FAILED'	=>	__a("There was a problem validating the site with the updates server.",true),
		'UPDATER_PACKAGE_SAVE_FAILED'		=>	__a("It was not possible to save the package locally.",true),
		'UPDATER_BACKUP_RENAME_FAILED'		=>	__a("Operation aborted. Could not rename the current folder to make a backup.",true),
		'UPDATER_ADDON_INSTALLED'			=>	__a("The package was successfully downloaded and installed. Reload the page to see the addon menu on the left column.",true),
		'UPDATER_COMPONENT_INSTALLED'		=>	__a("The package was successfully downloaded and extracted. Performing update ...",true),
		'UPDATER_PACKAGE_INSTALL_FAILED'	=>	__a("There was a problem extracting the package.",true),
		'UPDATER_PACKAGE_DOWNLOAD_FAILED'	=>	__a("Something went wrong downloading the update files. Quitting.",true),
		'UPDATER_UNZIP_FAILED'				=>	__a("There was a problem extracting the package.",true),

		// Media
		'MEDIA_DELETE_CONFIRM'        =>    __a("Are you sure you want to delete this file?",true),
		'MEDIA_UPLOAD_DISALLOWED'     =>    __a("%s upload failed. You don't have permission to upload %s.",true),
		'MEDIA_UPLOAD_INVALID_EXT'    =>    __a("%s has an invalid extension.",true),
		'MEDIA_UPLOAD_INVALID_EXT_LIST'=>   __a("File has an invalid extension, it should be one of %s.",true),
		'MEDIA_UPLOAD_PHOTO_LIMIT'    =>    __a("Photo upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_VIDEO_LIMIT'    =>    __a("Video upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_ATTACHMENT_LIMIT'=>   __a("Attachment upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_AUDIO_LIMIT'    =>    __a("Audio upload limit (%s) reached.",true),
		'MEDIA_UPLOAD_NOT_WRITABLE'   =>    __a("Upload directory is not writable.",true),
		'MEDIA_UPLOAD_NOT_UPLOADED'   =>    __a("No files were uploaded.",true),
		'MEDIA_UPLOAD_ZERO_SIZE'      =>    __a("%s is empty.",true),
		'MEDIA_UPLOAD_MAX_SIZE'       =>    __a("%s is too large. The maximum allowed size is %sMB.",true),
		'MEDIA_EMBED_VIDEO_NOT_FOUND' =>    __a("A video was not found at the specified url.",true),
		'MEDIA_DOWNLOAD_NOT_FOUND'    =>    __a("File not found",true),
		'MEDIA_UPLOAD_VIDEO_INCOMPLETE_SETUP' =>  __a("Cannot complete video upload because remote storage and video encoding setup is incomplete in Media Settings.",true),
		'MEDIA_UPLOAD_TYPE_ERROR'		=>	__a("{file} has an invalid extension. Allowed extensions: {extensions}.",true),
		'MEDIA_UPLOAD_SIZE_ERROR'		=>	__a("{file} is too large, maximum file size is {sizeLimit}.",true),
		'MEDIA_UPLOAD_EMPTY_ERROR'		=>	__a("{file} is empty.",true),
		'MEDIA_UPLOAD_NOFILE_ERROR'		=>	__a("No files to upload.",true),
		'MEDIA_UPLOAD_ONLEAVE'			=>	__a("The files are being uploaded, if you leave now the upload will be cancelled.",true),
		'MEDIA_UPLOAD_URL_NOLINKING'	=>	__a("The remote site rejected the connection to download the file.",true),
		'MEDIA_UPLOAD_URL_INVALID'		=>	__a("The upload URL is not valid, please try again.",true),

		//Everywhere
		'EVERYWHERE_VALIDATE_LISTING_TYPE'	=> __a("You need to select a Listing Type",true),
		'EVERYWHERE_VALIDATE_CATEGORY'	=>	__a("You need to select at least one category",true),

		// Geomaps
		'GEOMAPS_MAPIT'               =>    __a("Map it",true),
		'GEOMAPS_CLEAR_COORDINATES'   =>    __a("Clear coordinates",true),
		'GEOMAPS_CANNOT_GEOCODE'      =>    __a("Address could not be geocoded. Modify the address and click on the Geocode Address button to try again.",true),
		'GEOMAPS_DRAG_MARKER'         =>    __a("Drag the marker to fine-tune the geographic location on the map.",true),
		'GEOMAPS_GEOCODE_ADDRESS'     =>    __a("Geocode Address",true),
		'GEOMAPS_ADDRESS_EMPTY'       =>    __a("The address is empty",true),
		'GEOMAPS_ADDRESS_SKIPPED'     =>    __a("The address was skipped",true),
		'GEOMAPS_INVALID_FIELD'       =>    __a("The field doesn't exist",true)
		);

		if($key != '') {

			return $langArray[$key];
		}

		return $langArray;
	}
}