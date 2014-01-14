<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ConfigComponent extends S2Component
{
	var $version = null;
//general tab
    var $libraries_jquery = 0;
    var $libraries_jqueryui = 0;
    var $libraries_scripts_minified = 1;
    var $libraries_scripts_loader = 0;
	var $name_choice = "realname";
	var $template = "default";
    var $mobile_theme = "default";
    var $fallback_theme = "default";
	var $template_path = "/components/com_jreviews";
	var $display_list_limit = 0;
	var $url_param_joomla = 1;
	var $paginator_mid_range = 5;
	var $user_registration_guest = 1;

// cron
	var $cron_secret;
	var $cron_site_visits = 1;
	var $cache_cleanup = "12";
    var $ranks_rebuild_interval = 2;
    var $ranks_rebuild_last = 0;
	var $media_likes_rebuild_interval = 0.5;
	var $media_likes_rebuild_last = 0;

// breadcrumb
    var $dir_show_breadcrumb = "1"; // JReviews
    var $breadcrumb_detail_override = "1";
    var $breadcrumb_detail_directory = "0";
    var $breadcrumb_detail_category = "1";
// 3rd party integration
    var $joomfish_plugin = false;

// Troubleshooting
    var $debug_enable = false;
    var $debug_theme_enable = false;
    var $debug_ipaddress = '';
    var $debug_overrides_disable = false;
    var $ajaxuri_lang_segment = true;

// Community
    var $community = null;
    var $social_sharing_detail = array();
    var $social_sharing_count_position = 'vertical';
    // Jomsocial integration
    var $jomsocial_tnmode =  'crop';
    var $jomsocial_tnsize =  65;
    var $jomsocial_listings =  1;
    var $jomsocial_reviews =  1;
    var $jomsocial_discussions =  1;
    var $jomsocial_favorites =  1;
    var $jomsocial_votes =  1;
    var $jomsocial_media = 1;
    //Twitter integration
    var $twitter_enable = 0;
    var $twitter_oauth = '';
    var $twitter_listings = 0;
    var $twitter_reviews = 0;
    var $twitter_discussions = 0;
    var $bitly_user = '';
    var $bitly_key = '';
    var $bitly_history = 0;
    // Facebook
    var $facebook_enable = 0;
    var $facebook_send = 0;
    var $facebook_optout = 0;
    var $facebook_appid;
    var $facebook_admins;
    var $facebook_opengraph = 1;
    var $facebook_secret;
    var $facebook_listings = 0;
    var $facebook_reviews = 0;
    var $facebook_votes = 0;
    var $facebook_posts_trim = '';
    //access tab
	var $security_image = "1,2";
	var $moderation_item = "1,2";
	var $moderation_item_edit = "0";
	var $moderation_reviews = "1,2";
	var $moderation_editor_reviews = "0";
	var $editaccess = "7,8";
    var $listing_publish_access = "7,8";
    var $listing_delete_access = "7,8";
	var $editaccess_reviews = "7,8";
	var $moderation_review_edit = "0";
	var $moderation_editor_review_edit = "0";
	var $addnewaccess = "7,8";
	var $addnewaccess_reviews = "2,3,4,5,6,7,8";
	var $addnewwysiwyg = "7,8";
	var $addnewmeta = "7,8";
	var $user_vote_public = "1,2,3,4,5,6,7,8";
	var $user_owner_disable	= "0";
    var $addnewaccess_posts = "2,3,4,5,6,7,8";
    var $moderation_posts = "1,2";
    var $post_edit_access = "2,3,4,5,6,7,8";
    var $post_delete_access = "2,3,4,5,6,7,8";
    var $moderation_owner_replies = "2";
//directory tab
	var $dir_show_alphaindex = "1";
	var $dir_columns = "2";
	var $dir_cat_num_entries = "1";
	var $dir_cat_images = 'None';
    var $dir_cat_format = "1";
	var $dir_category_order = "1";
	var $dir_category_limit = "0";
	var $dir_category_hide_empty = "0";
    var $dir_category_levels = "2";
//item list tab
	var $list_display_type = "1";
	var $list_display_type_joomla = "blogjoomla_simple";
	var $list_show_addnew	= "1";
    var $list_addnew_menuid = "1"; // Keep current itemid in url
	var $list_show_searchbox = "1";
	var $list_show_orderselect = "1";
	var $list_order_default	= "alpha";
    var $list_order_field   = "";
	var $list_show_categories = "1"; // category list
    var $list_show_child_listings = "1"; // J16 only
	var $list_show_date = "1";
	var $list_show_author = "1";
	var $list_show_user_rating = "1";
	var $list_show_hits = "1";
	var $list_show_readmore = "1";
	var $list_show_readreviews = "1";
	var $list_show_newreview = "1";
	var $list_show_abstract = "1";
	var $list_abstract_trim = "30";
	var $list_new = "1";
	var $list_new_days = "10";
	var $list_hot = "1";
	var $list_hot_hits = "1000";
	var $list_featured = "1";
	var $cat_columns = "3";
	var $list_limit = "10";
    // listing comparison
    var $list_compare = 0;
    var $list_compare_columns = 3;
    var $list_compare_user_ratings = 0;
    var $list_compare_editor_ratings = 0;
    // listings tab
    var $favorites_enable = 1;
    var $claims_enable = 0;
    var $claims_enable_userids = '42';
    var $summary_desc_char_limit = 0;
    var $inquiry_recipient = 'owner';
    var $inquiry_enable = 0;
    var $inquiry_field = '';
    var $inquiry_bcc = '';
	//reviews tab
	var $location = "0";
	var $show_criteria_rating_count = "2";
    var $owner_replies = "1";
    var $review_discussions = "1";
    var $user_multiple_reviews  = "1";
    var $review_ipcheck_disable = 0;
    var $vote_ipcheck_disable = 0;
    var $user_review_order = 'rdate';
        //=> ratings
    var $rating_scale = "5";
    var $rating_increment = "1";
    var $rating_selector = "stars"; // stars
    var $rating_graph = "1";
    var $rating_default_na = "1";
    var $rating_hide_na = "0";
		//=> author reviews
	var $author_review = "0";
	var $authorids = "42";
	var $author_vote = "1";
	var $author_report = "1";
	var $author_forum = "";
	var $author_ratings = "1"; // detailed ratings box
	var $author_rank_link = "1";
	var $author_myreviews_link = "1";
    var $editor_rank_exclude = "0";
	var $editor_limit = "5";
	var $editor_review_char_limit = 0;
		// => user reviews
	var $viewallreviews_canonical = "0";
	var $user_reviews = "1";
	var $user_vote = "1";
	var $user_report = "1";
	var $user_forum = "";
	var $user_ratings = "1"; // detailed ratings box
	var $user_rank_link = "1";
	var $user_myreviews_link = "1";
	var $user_limit = "5";
	var $user_review_char_limit = 0;
	//standard fields tab
	var $content_title_duplicates = 'category';
    var $content_name = "required";
    var $content_email = "required";
	var $content_title = "1";
	var $content_summary = "required";
	var $content_description = "optional";
	var $content_pathway = "1";
	var $content_show_reviewform = "authors";
    var $reviewform_title = "required";
    var $reviewform_name = "required";
    var $reviewform_email = "required";
    var $reviewform_comment = "required";
    var $reviewform_optional = "1";
    var $discussform_name = "required";
    var $discussform_email = "required";
    var $mediaform_name = "required";
    var $mediaform_email = "required";
	//search tab
	var $search_itemid = "0";
	var $search_itemid_hc = "";
	var $search_display_type = "1";
    var $search_return_all = "0";
	var $search_tmpl_suffix = "";
	var $search_item_author = "0";
	var $search_field_conversion = "0";
    var $search_one_result = "1";
    var $search_cat_filter = "0";
	var $search_simple_query_type = 'all';
	//notification tab
	var $notify_review = "0";
	var $notify_content = "0";
	var $notify_report = "0";
	var $notify_review_post = "0";
	var $notify_review_emails;
	var $notify_review_post_emails;
	var $notify_content_emails;
    var $notify_report_emails;
    var $notify_user_listing = "0";
    var $notify_user_listing_emails;
    var $notify_owner_review = "0";
    var $notify_owner_review_claimed = 0;
    var $notify_owner_review_emails;
    var $notify_user_review = "0";
    var $notify_user_review_emails;
    var $notify_comment = "0";
    var $notify_comment_emails;
    var $notify_owner_reply = "0";
    var $notify_owner_reply_emails;
    var $notify_claim = "0";
    var $notify_claim_emails;
    var $notify_user_comment = "0";
    var $notify_user_comment_emails;
    var $notify_reviewer_comment = "0";
    var $notify_reviewer_comment_emails;
    var $notify_owner_comment = "0";
    var $notify_owner_comment_claimed = "0";
    var $notify_owner_comment_emails;
    var $notify_user_media_encoding = "0";
    var $notify_user_media_encoding_emails;
	//rss tab
	var $rss_enable = "0";
	var $rss_limit = "10";
	var $rss_title;
	var $rss_image;
	var $rss_description;
	var $rss_item_images= "0";
	var $rss_item_image_align = "right";
	//seo manager
	var $seo_title = "0";
	var $seo_description = "0";
	//seo manager
	var $cache_disable = "0";
	var $cache_query = "0";
	var $cache_expires = "6";
    var $cache_view = "0";
    var $cache_session = "1";
    // Remote updater
    var $updater_betas = "0";

	# MEDIA SETTINGS

	// General

	var $media_url_catchall = 1;

	var $media_general_default_image_path = 'components/com_jreviews/jreviews/views/themes/default/theme_images/nophoto.png';

	var $media_general_default_video_path = 'components/com_jreviews/jreviews/views/themes/default/theme_images/novideo.png';

	var $media_general_thumbnail_quality = 85;

	var $media_general_default_order = 'newest';

	var $media_general_default_order_listing = 'newest';

	var $media_general_tos = 1;

	// Indicators

	var $media_photo_show_count = 1;

	var $media_video_show_count = 1;

	var $media_attachment_show_count = 1;

	var $media_audio_show_count = 1;

	// Display

	var $media_list_thumbnail = 1;

	var $media_list_thumbnail_mode = 'crop';

	var $media_list_thumbnail_size = '120x120';

	var $media_list_category_image = 0;

	var $media_list_default_image = 0;

	var $media_detail_main = 1;

	var $media_detail_main_lightbox_disable = 0;

	var $media_detail_main_thumbnail_mode = 'scale';

	var $media_detail_main_thumbnail_alignment = 'right';

	var $media_detail_main_thumbnail_size = '300x300';

	var $media_detail_default = 0;

	var $media_detail_separate_media = 0;

	var $media_detail_photo_layout = 'contact_lightbox';

	var $media_photo_gallery_overlay = 0;

	var $media_detail_video_layout = 'film_linked';

	var $media_detail_audio_downloads = 0;

	var $media_detail_gallery_thumbnail_mode = 'crop';

	var $media_detail_gallery_thumbnail_size = '100x100';

	var $media_detail_photo_limit = 6;

	var $media_detail_video_limit = 6;

	var $media_review_gallery_thumbnail_mode = 'crop';

	var $media_review_gallery_thumbnail_size = '45x45';

	var $media_review_photo_limit = 3;

	var $media_review_video_limit = 3;

	var $media_list_layout = 'grid';

	var $media_media_thumbnail_mode = 'crop';

	var $media_media_thumbnail_size = '140x90';

	// Photos
	var $media_photo_filename_title = 0;

	var $media_photo_filename_listingid = 0;

	var $media_photo_max_uploads_listing = 10;

	var $media_photo_max_uploads_review = 3;

	var $media_photo_max_size = 1;

	var $media_photo_extensions = 'jpg,jpeg,gif,png';

	var $media_photo_resize = '800x800';

	var $media_photo_resize_quality = 90;

	// Videos
	var $media_video_filename_title = 0;

	var $media_video_filename_listingid = 0;

	var $media_video_upload_methods = 'all';

	var $media_video_link_sites = array('youtube','vimeo','dailymotion');

	var $media_video_max_uploads_listing = 5;

	var $media_video_max_uploads_review = 3;

	var $media_video_max_size = 10;

	var $media_video_extensions = 'avi,mp4,mpg,mpeg,mov,flv,ogg';

	// Attachments
	var $media_attachment_filename_title = 0;

	var $media_attachment_filename_listingid = 0;

	var $media_attachment_max_uploads_listing = 1;

	var $media_attachment_max_uploads_review = 1;

	var $media_attachment_max_size = 3;

	var $media_attachment_extensions = 'zip,pdf';

	// Audio
	var $media_audio_filename_title = 0;

	var $media_audio_filename_listingid = 0;

	var $media_audio_max_uploads_listing = 5;

	var $media_audio_max_uploads_review = 3;

	var $media_audio_max_size = 5;

	var $media_audio_extensions = 'm4a,mp3,pcm,aac,oga,webm';

	// Storage

	var $media_store_photo = 'local';

	var $media_store_video = 's3';

	var $media_store_video_embed = 'local';

	var $media_store_attachment = 'local';

	var $media_store_audio = 's3';

	var $media_store_local_path = 'media/reviews/';

	var $media_store_local_original_folder = 'original';

	var $media_store_local_thumbnail_folder = 'thumbnail';

	var $media_store_local_photo = 'photos';

	var $media_store_local_video = 'videos';

	var $media_store_local_attachment = 'attachments';

	var $media_store_local_audio = 'audio';

	var $media_store_amazons3_photo_cdn = 0;

	var $media_store_amazons3_video_cdn = 0;

	var $media_store_amazons3_attachment_cdn = 0;

	var $media_store_amazons3_audio_cdn = 0;

	// Encoding

	var $media_encode_service = '';

	var $media_encode_size = '854x480';

	var $media_encode_bitrate = '1200';

	var $media_encode_transfer_method = 'http';

	var $media_encode_transfer_host;

	var $media_encode_transfer_username;

	var $media_encode_transfer_password;

	var $media_encode_transfer_port;

	var $media_encode_transfer_tmp_path;

	# MEDIA ACCESS SETTINGS

	var $media_access_view_photo_listing = 1;

	var $media_access_view_video_listing = 1;

	var $media_access_view_attachment_listing = 1;

	var $media_access_view_audio_listing = 1;

	var $media_access_view_photo_review = 1;

	var $media_access_view_video_review = 1;

	var $media_access_view_attachment_review = 1;

	var $media_access_view_audio_review = 1;

	var $media_access_submit_photo_listing = "1,2,3,4,5,6,7,8";

	var $media_access_submit_photo_listing_owner = 1;

	var $media_access_submit_video_listing = "7,8";

	var $media_access_submit_video_listing_owner = 1;

	var $media_access_submit_attachment_listing = "7,8";

	var $media_access_submit_attachment_listing_owner = 1;

	var $media_access_submit_audio_listing = "7,8";

	var $media_access_submit_audio_listing_owner = 1;

	var $media_access_submit_photo_review = "7,8";

	var $media_access_submit_video_review = "7,8";

	var $media_access_submit_attachment_review = "7,8";

	var $media_access_submit_audio_review = "7,8";

	var $media_access_upload_url_listing = "7,8";

	var $media_access_upload_url_review = "7,8";

	var $media_access_moderate_photo = "1,2";

	var $media_access_moderate_video = "1,2";

	var $media_access_moderate_attachment = "1,2";

	var $media_access_moderate_audio = "1,2";

	var $media_access_moderate_edit = 0;

	var $media_access_like_photo = "1,2,3,4,5,6,7,8";

	var $media_access_like_video = "1,2,3,4,5,6,7,8";

	var $media_access_like_attachment = "1,2,3,4,5,6,7,8";

	var $media_access_like_audio = "1,2,3,4,5,6,7,8";

	var $media_access_edit = "1,2,3,4,5,6,7,8";

	var $media_access_delete = "1,2,3,4,5,6,7,8";

	var $media_access_publish = "1,2,3,4,5,6,7,8";


	function startup(&$controller = null)
	{
		$joomla_version = cmsFramework::getVersion();

		if($joomla_version >= 3) {

        	// Disable jQuery by default in J3.0
        	$this->libraries_jquery = 1;
        }

		if($Config = Configure::read('JreviewsSystem.Config'))
		{
			$this->merge($Config);
		}
		else {

			$cache_file = s2CacheKey('jreviews_config');

			$Config = S2Cache::read($cache_file, '_s2framework_core_');

			if(empty($Config)) {

				$Config = $this->load();

				S2Cache::write($cache_file,$Config, '_s2framework_core_');
			}

			$this->merge($Config);

			Configure::write('JreviewsSystem.Config',$Config);
		}

		($this->url_param_joomla == 1 && !defined('URL_PARAM_JOOMLA_STYLE')) and define('URL_PARAM_JOOMLA_STYLE',1);

		Configure::write('System.version',strip_tags($this->version));
		Configure::write('Theme.name',$this->template);
		Configure::write('Community.extension', $this->community);
		Configure::write('Cache.enable',true);
		Configure::write('Cache.disable',false);
		Configure::write('Cache.expires',$this->cache_expires*3600);
		Configure::write('Cache.query',(bool)$this->cache_query);
		Configure::write('Cache.view',(bool)$this->cache_view);
        Configure::write('Cache.session',!defined('MVC_FRAMEWORK_ADMIN') && (bool)$this->cache_session);
        Configure::write('Jreviews.editor_rank_exclude',(bool)$this->editor_rank_exclude);

        /**
         * Add support for Router/Actions specific view caching
         */
        $AppCacheKeys = preg_grep('/Cache\./',array_keys((array)$Config));

		if(defined('JREVIEWS_SEF_PLUGIN'))
		{
			$this->url_param_joomla = 1;

			$this->breadcrumb_detail_override = 0;
		}

        foreach($AppCacheKeys AS $key) {

            Configure::write($key,(bool) $Config->{$key});
        }

        // Set default cache duration using configuration value
 		S2Cache::config('_s2framework_core_', array('duration'=>86400,'engine' => 'File','path'=>S2Paths::get('jreviews','S2_CACHE') . 'core'));

		# DEFINES
		!defined('MEDIA_LOCAL_PATH') and define('MEDIA_LOCAL_PATH',Sanitize::getString($this,'media_store_local_path','media/reviews/'));

		!defined('MEDIA_ORIGINAL_FOLDER') and define('MEDIA_ORIGINAL_FOLDER', Sanitize::getString($this,'media_store_local_original_folder','original'));

		!defined('MEDIA_THUMBNAIL_FOLDER') and define('MEDIA_THUMBNAIL_FOLDER', Sanitize::getString($this,'media_store_local_thumbnail_folder','thumbnail'));
	}

	function load()
    {
		$Model = new MyModel();

		$Config = new stdClass();

		$Model->_db->setQuery("SELECT id, value FROM #__jreviews_config");

		$rows = $Model->_db->loadObjectList();

		if($rows)
		{
			foreach ($rows as $row)
			{
				if(!$row->id) continue;

				$prop = $row->id;

                $length = strlen($row->value)-1;

                if(substr($row->value,0,1)=='[' && substr($row->value,$length,1)==']')
                {
                    $row->value = json_decode($row->value);

                } else {
                    $row->value = stripcslashes($row->value);
                }

				$Config->$prop = $row->value;
			}
		}

        $Config->rss_title = @$Config->rss_title != '' ? $Config->rss_title : $Model->makeSafe(cmsFramework::getConfig('sitename'));

        $Config->rss_description = @$Config->rss_description != '' ? $Config->rss_description : $Model->makeSafe(cmsFramework::getConfig('MetaDesc'));

		# Get current version number
        include_once(PATH_ROOT . 'components' . DS . 'com_jreviews' . DS . 'jreviews.info.php' );

        if(isset($package_info))
        {
            $version = $package_info['jreviews']['version'];
            $Config->version = $version;
        }

        return $Config;
	}

	function merge(&$Config)
    {
		foreach($Config AS $key=>$value) {
			$this->{$key} = $value;
		}
	}

	function store($arr = null, $arrayToJson = true)
	{
		$MyModel = ClassRegistry::getClass('MyModel');

        if(is_null($arr))
        {
            $arr = get_object_vars($this);
        }

		while (list($prop, $val) = each($arr))
		{
			if($prop != 'c')
			{
                if(is_array($val) && $arrayToJson){
                    $val = json_encode($val);
                }
                elseif(is_array($val) && !$arrayToJson){
                    $val = implode(",",$val);
                }
                else
                {
                    // Fixes an issue where an Array string is added to some values
                    $val = str_replace(',Array','',$val);
                }

				$val = trim($val); //Remove extra spaces

				$query = "
					INSERT INTO
						#__jreviews_config (id, value)
					VALUES
						(" . $MyModel->Quote($prop) . ", " . $MyModel->Quote($val) . ")
					ON DUPLICATE KEY UPDATE
						value = " . $MyModel->Quote($val) . "

				";

				$MyModel->query($query);
            }
		}

        if(defined('MVC_FRAMEWORK_ADMIN')) {
            // Forces clear cache when config settings are modified in the administration
            clearCache('','views');
            clearCache('','__data');
            clearCache('','core');
        }

		// Push updates to the cached file
		$cache_file = S2CacheKey('jreviews_config');

		$Config = $this->load();

		S2Cache::write($cache_file,$Config, '_s2framework_core_');
    }

	function bindRequest($request)
	{
		$arr = get_object_vars($this);
		while (list($prop, $val) = each($arr))
			$this->$prop = Sanitize::getVar($request, $prop, $val);
	} // bindRequest

    function override($config_overrides, $return = false)
    {
        if(empty($config_overrides)) return;

        if(!is_array($config_overrides)) $config_overrides = json_decode($config_overrides,true);

		$Config = Configure::read('JreviewsSystem.Config');

		$Config = (object) $Config;

		$override_ban = Configure::read('JreviewsSystem.OverrideBan',array()); /* For now only used in paidlistings*/

		foreach($config_overrides AS $key=>$value)
        {
			if(is_array($value) && count($value) == 1 && (int) $value[0] == -1) {

				$value = -1;
			}

            if(!in_array($key,$override_ban) && (int) $value != -1) {

            	if($return) {

                	$Config->{$key} = $value;
            	}
            	else {

                	$this->{$key} = $Config->{$key} = $value;
            	}
            }
        }

        if($return) {

        	return $Config;
        }

		Configure::write('JreviewsSystem.Config',$Config);
    }

    function getOverride($var,$config)
    {
        $value = Sanitize::getVar($config,$var,-1);

		if(is_array($value) && count($value) == 1 && (int) $value[0] == -1) {
			$value = -1;
		}

        return $value != -1 && $value != '' ? $value : $this->{$var};
    }
}
