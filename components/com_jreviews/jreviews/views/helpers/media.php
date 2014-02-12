<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaHelper extends HtmlHelper {

	var $helpers = array('form');

	var $orderingOptions = array();

    function __construct()
    {
        parent::__construct(); // Make parent class vars available here, like cmsVersion

		$this->orderingOptions = array(
			'newest'		=>__t("Newest",true),
			'oldest'		=>__t("Oldest",true),
            'popular'		=>__t("Most Popular",true),
			'liked'			=>__t("Most Liked",true),
			'ordering'		=>__t("Ordering",true)
		);
    }

	/*
	 * Returns the thumbnail url
	 */
	function makeAPICall($media, $options)
	{
		if(!isset($media['media_id'])) {
			return false;
		}

// 		prx('API CALLED: '.$media['media_id']);
//		prx($media['media_info']['image']['url']);
//		prx($options);
		$post = '';

		$size = $options['size'];

		$_APIKey = md5(cmsFramework::getConfig('secret'));

		$_CURL_OPTIONS = array(
			CURLOPT_NOBODY =>1,
			CURLOPT_HEADER=>0,
			CURLOPT_POST=>1,
			CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_REFERER =>  $_SERVER['SERVER_NAME']
		);


		$data = array(
			'api_key'=>$_APIKey,
			'size'=>$size,
			'mode'=>Sanitize::getString($options,'mode','scale'),
			'media_id'=>$media['media_id']
		);

		$post .= '&data[media] = ' . urlencode(json_encode($data));

		$api_url = WWW_ROOT . 'index.php?option=com_jreviews&format=raw&url=media_upload/generateThumb';

		$ch = curl_init(sprintf($api_url,$_APIKey));

		// Set transfer options
		curl_setopt_array($ch, $_CURL_OPTIONS);

		// Set post data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

		// Execute session and store returned results
		$out = curl_exec($ch);

		// prx($out); // Debug

		$info = curl_getinfo($ch);

		if($info['http_code'] == 200/* && $out['success'] == true*/) {

			$result = true;//json_decode($out,true);
		}
		else {

			$result = false;
		}

		curl_close($ch);

		return $result;
	}


	function defaultThumb($media, $options, $attributes)
	{
		// Don't run in detail pages
		if($this->action == 'com_content_view' && !Sanitize::getString($this->Config,'media_detail_default')) return false;

		$listing = Sanitize::getVar($options,'listing');

		// Read config settings
		$show_noimage = $this->Config->getOverride('media_list_default_image',Sanitize::getVar($listing['ListingType'],'config'));

		$default_image = $this->Config->getOverride('media_general_default_image_path',Sanitize::getVar($listing['ListingType'],'config'));

		$show_cat_image = $this->Config->getOverride('media_list_category_image',Sanitize::getVar($listing['ListingType'],'config'));

		$cat_image = Sanitize::getString($media,'category');

		$everywhere_image = Sanitize::getString($media,'everywhere');

		$css_size = Sanitize::getBool($options,'css_size');

		if(is_numeric($options['size'])) {
			$options['size'] .= 'x'.$options['size'];
		}

		$size = explode('x',low($options['size']));

		if(!isset($attributes['style']) && $css_size) {

			$attributes['style'] = "width:{$size[0]}px; height:{$size[1]}px;";
		}

		$title = Sanitize::getString($media,'title');

		$title != '' and $attributes['title'] = $title;

		$attributes['data-listingid'] = $listing['Listing']['listing_id'];

		$image_url = '';

		if($everywhere_image) {

			$image_url = strstr($everywhere_image,'http') ? $everywhere_image : WWW_ROOT . $everywhere_image;
		}

		if($show_cat_image && $cat_image != '') {

			$image_url = WWW_ROOT . $cat_image;
		}
		elseif($show_noimage) {

			$image_url = WWW_ROOT . $default_image;
		}

		if($image_url != '') {

			$listing = array('Listing'=>array('summary'=>'<img src="'.$image_url.'" />'));

			$tn_url = $this->embedThumb($listing, $options, $attributes);

			if(Sanitize::getBool($options,'return_url') || Sanitize::getBool($options,'return_src')) {
				return $tn_url;
			}

			return $this->image($tn_url,$attributes);
		}

		return false;
	}

	/**
	 *
	 * @param type $listing
	 * @param type $media
	 * @param type $options
	 * @param type $attributes
	 * @return string
	 */
    function thumb($media, $options = array(), $attributes = array())
	{
		$thumbnailer = Sanitize::getString($options,'thumbnailer','ajax'); // Skips running the thumbnailer for consecutive calls to the same thumb like for rich snippets

		$css_size = Sanitize::getBool($options,'css_size'); // Skips running the thumbnailer for consecutive calls to the same thumb like for rich snippets

		// Perform some checks on the input to get the correct media array

		// For audio and attachments in list/modules check if main media is set and it' a photo so we can use it' thumbnail
		if(isset($media['Media']))
		{
			if(
				isset($media['MainMedia'])

				&& isset($media['MainMedia']['media_info'])

				&& in_array(Sanitize::getString($media['MainMedia'],'media_type'),array('photo','video'))

				&& in_array(Sanitize::getString($media['Media'],'media_type'),array('audio','attachment'))
			) {

				$media = $media['MainMedia'];
			}
			else {

				$media = $media['Media'];

			}
		}

		do if(!isset($media['media_info'])) {

			if(isset($options['listing']) && $src = $this->embedThumb($options['listing'], $options, $attributes)) {

				// Force width on external images
				$size = Sanitize::getInt($options,'size',100);

				if(Sanitize::getBool($options,'return_src')) return $src;

				return '<img src="'.$src.'" style="width:'.$size.'px;height:auto;" />';
			}

			return $this->defaultThumb($media, $options, $attributes);

		} while(false);

		// For videos in the process of being encoded, show a temporary thumbnail
		if($media['published'] == 2 && $media['media_type'] == 'video') {

			$media['everywhere'] = Sanitize::getString($this->Config,'media_general_default_video_path');

			return $this->defaultThumb($media, $options, $attributes);
		}

		$sizeArray = array();

		$caption = htmlspecialchars(Sanitize::getString($media,'title'),ENT_QUOTES,'utf-8');

		$attributes = array_merge(array('border'=>0,'alt'=>$caption,'title'=>$caption,'class'=>'jrMedia'.Inflector::camelize($media['media_type'])),$attributes);

		$size = Sanitize::getString($options,'size');

		// Fix for settings where height was not required or is not specified
		if(is_numeric($size)) {

			$size .= 'x'.$size;

			$options['size'] = $size;
		}

		if($size != '') {

			$sizeArray = explode('x',low($size));
		}

		$file = null;

		switch($media['media_type'])
		{
			case 'attachment':

				switch($media['file_extension']) {
					case 'zip':
						$file = 'zip.png';
						break;
					case 'pdf':
						$file = 'pdf.png';
						break;
					default:
						$file = 'attachment.png';
						break;
				}

			break;

			case 'audio':
				$file = 'audio.png';
				break;
		}

		if($file)
		{
			if(isset($options['dimensions']) && !isset($attributes['style'])) {

				$css_size and $attributes['style'] = 'width:'.$sizeArray[0] .'px; height:' . $sizeArray[1] . 'px;';

				unset($options['dimensions']);
			}
			else {

				// Default size of media type images
				$attributes['style'] = defined('MVC_FRAMEWORK_ADMIN') ? 'width:45px; height:45px;' : 'width:128px; height:128px;';
			}

			return $this->image($this->viewImages . $file, $attributes);
		}

		$gotThumb = false;

		// Perform size and availability of thumbnail check, otherwise generate the thumbnail

		// Get original image url, then check for existing thumbs, otherwise create them
		$image_url = $this->mediaSrc($media);

		if(isset($options['skipthumb'])) {

			if(!empty($sizeArray)) {

				$attributes['width'] = $sizeArray[0];

				$attributes['height'] = $sizeArray[1];
			}
			else {

				$attributes['width'] = $media['media_info']['image']['width'];

				$attributes['height'] = $media['media_info']['image']['height'];
			}

			if(isset($options['lazyload'])) {

				$attributes['data-src'] = $image_url;

				$image_url = '#';
			}

			return $this->image($image_url,$attributes);
		}


		$image = Sanitize::getVar($media['media_info'],'image');

		$thumbnail = Sanitize::getVar($media['media_info'],'thumbnail');

		$SizeMode = $options['size'].$options['mode']{0};

		$thumb_in_array = isset($thumbnail[$SizeMode]);

		if($thumb_in_array)
		{
			$thumb_path = str_replace(WWW_ROOT,PATH_ROOT,$thumbnail[$SizeMode]['url']);

			$local_storage = $thumb_in_array ? strstr($thumbnail[$SizeMode]['url'],WWW_ROOT) : false;
		}
		else {

			$thumb_path = '';

			$local_storage = false;
		}

		// In local storage we check for the file to recreate it if not present
		// - this should be removed in favor of global reset function that works with remote storage as well
		if($thumb_in_array
			&& ($local_storage && file_exists($thumb_path)
				||
				!$local_storage
				)
		) {
			// No resizing is necessary
			$tn = $thumbnail[$SizeMode];
			$gotThumb = true;
		}
		else {

			if($thumbnailer == 'api') {

				$this->makeAPICall($media, $options);
			}

			// For API calls we display the original image just once to avoid an empty image while the thumbnail is generated
			// That is unless the return_thumburl option is set to override it
			// Useful for plugins and external posting of images (JomSocial, Facebook, Twitter) which don't require showing the
			// thumbnail right away

			if(Sanitize::getBool($options,'return_thumburl') !== true)
			{
				$thumbnailer == 'ajax' and $attributes['data-thumbnail'] = 1;

				$attributes['style'] = "width:{$sizeArray[0]}px;height:{$sizeArray[1]}px;";

				$attributes['data-media-id'] = $media['media_id'];

				$attributes['data-size'] = $options['size'];

				$attributes['data-mode'] = Sanitize::getString($options,'mode','scale');

				$tn['url'] =  $image_url;
			}

			$tn['width'] = $sizeArray[0];

			$tn['height'] = $sizeArray[1];
		}

		if(!isset($attributes['style']) && $css_size) {

			$attributes['style'] =  "width: {$tn['width']}px; height:{$tn['height']}px;";
		}

		if(!isset($tn['url']))
		{
			extract(parse_url($media['media_info']['image']['url'])); /* $scheme, $host, $path */

			$path = str_replace('/'.MEDIA_ORIGINAL_FOLDER.'/','/'.MEDIA_THUMBNAIL_FOLDER.'/' . $SizeMode .'/',$path);

			// Rebuild url
			$tn_url = $scheme . '://' . $host . $path;

		} else {
			$tn_url = $tn['url'];
		}

		if(Sanitize::getBool($options,'return_src') || Sanitize::getBool($options,'return_url')) {

			return $tn_url;
		}

		if(Sanitize::getBool($options,'lightbox')) {

			$rel = Sanitize::getString($options,'rel','gallery');

			$image = $this->image($tn_url,$attributes);

			return $this->link($image, $image_url, array('sef'=>false,'class'=>'fancybox','rel'=>$rel,'title'=>$caption));
		}

		if(isset($options['lazyload'])) {
			$attributes['data-src'] = $tn_url;
			$tn_url = '#';
		}

		return $this->image($tn_url,$attributes);
	}

	function mediaSrc($media)
	{
		if(isset($media['Media']))  {
			return $media['Media']['media_info']['image']['url'];
		}

		return $media['media_info']['image']['url'];
	}


	function orderingListListing($selected, $attributes = array())
	{
		if(isset($attributes['exclude'])) {
			foreach($attributes['exclude'] AS $key) {
				unset($options_array[$key]);
			}
		}

		$options_array = $this->orderingOptions;

		unset($attributes['exclude']);

		return $this->generateList($options_array, $selected, $attributes);
	}

	/**
	 * ORDERING AND FILTER LISTS
	 */
	function orderingList($selected, $attributes = array())
	{
		if(isset($attributes['exclude'])) {
			foreach($attributes['exclude'] AS $key) {
				unset($options_array[$key]);
			}
		}

		$options_array = $this->orderingOptions;

		unset($options_array['ordering']);

		unset($attributes['exclude']);

		return $this->generateList($options_array, $selected, $attributes);
	}

	function generateList($orderingList, $selected, $attributes)
	{
		$attributes = array_merge(array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
			)
			, $attributes
		);

		return $this->generateFormSelect('order', $orderingList, $selected, array('lang','order','page'), $attributes);
	}

	function mediaTypeFilter($selected)
	{
		$options_array = array(
			''			=>__t("All",true),
			'video'			=>__t("Video",true),
			'photo'			=>__t("Photo",true),
            'attachment'    =>__t("Attachment",true),
			'audio'			=>__t("Audio",true)
		);

        $orderingList = $options_array;

		$attributes = array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect('type', $orderingList, $selected, array('lang','type','page'), $attributes);
	}

	function generateFormSelect($key, $orderingList, $selected, $params, $attributes) {

		# Construct new route
		$args = $this->passedArgs;

		unset($args['mid']);

		$new_route = cmsFramework::constructRoute($args, $params);

		$selectList = array();

		foreach($orderingList AS $value=>$text)
		{
			if($value !='' && Sanitize::getString($attributes,'default') != $value)
			{
				$selectList[] = array('value'=>cmsFramework::route($new_route . '/' . $key . _PARAM_CHAR . $value),'text'=>$text);
			}
			else {
				$selectList[] = array('value'=>cmsFramework::route($new_route),'text'=>$text);			}
		}

		unset($attributes['default']);

		$selected = cmsFramework::route($new_route . '/' . $key . _PARAM_CHAR . $selected);

		return $this->Form->select($key,$selectList,$selected,$attributes);
	}

	/**
	 * Renders Like/Dislike and Reporting actions
	 */
	function mediaActions($media)
	{
		if(isset($media['Media'])) {
			extract($media['Media']);
		}
		else {
			extract($media);
		}
		?>
		<div class="jr-media-actions jrMediaActions" data-listing-id="<?php echo $listing_id;?>" data-review-id="<?php echo $review_id;?>" data-media-id="<?php echo s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));?>" data-extension="<?php echo $extension;?>">

			<div class="jr-media-actions-right jrRight" style="text-align:right;">

			  <span class="jr-media-views"><span class="jrIconGraph"></span><?php echo sprintf(__t("%s views",true),$views);?></span>

			</div>

			<?php if($this->Access->canVoteMedia('video')):?>
			<span class="jr-media-like-dislike jrMediaLikeDislike jrButtonGroup">

				<button  class="jr-media-like jrButton jrSmall" title="<?php __t("I like this",false,true);?>" data-like-action="_like">

					<span class="jrIconThumbUp"></span><span class="jr-count jrButtonText" style="color: green;"><?php echo $likes_up; ?></span>

				</button>

				<button class="jr-media-dislike jrButton jrSmall" title="<?php __t("I dislike this",false,true);?>" data-like-action="_dislike">

					<span class="jrIconThumbDown"></span><span class="jr-count jrButtonText" style="color: red;"><?php echo $likes_total - $likes_up; ?></span>

				</button>

			</span>
			<?php endif;?>

		    <button class="jr-report jrReport jrButton jrSmall"  data-listing-id="<?php echo $listing_id;?>" data-review-id="<?php echo $review_id;?>" data-media-id="<?php echo s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));?>" data-extension="<?php echo $extension;?>">
				<span class="jrIconWarning"></span><?php __t("Report as inappropriate");?>
		    </button>

		 </div>
		 <?php
	}


	/**** FUNCTIONS TO DEAL WITH IMAGES EMBEDDED IN SUMMARY - LEGACY JREVIEWS AND JOOMLA ARTICLE SUPPORT ****/
    function grabImgFromText($text)
    {
        /** Four scenarios for embedded images
        * 1) Located within current site
        * 2) Located in site within a folder of current site
        * 3) Located in a different domain with the same image path structure
        * 4) Located in a different domain with different path structure
        */
        $doc = new DOMDocument();
        @$doc->loadHTML($text);
        $imageTags = $doc->getElementsByTagName('img');
        if($imageTags->length > 0)
        {
            $src = ltrim($imageTags->item(0)->getAttribute('src'),'/');
			return $src;
        }

        return false;
    }

    function embedThumb(&$listing, $options = array(), $attributes = array())
    {
		$quality = Sanitize::getInt($this->Config,'media_general_thumbnail_quality',85);

		$summary = Sanitize::getString($listing['Listing'],'summary');

        if($summary != ''&& $src = $this->grabImgFromText($summary))
		{
			$listing['Listing']['summary'] = Sanitize::stripImages($summary);

		} else {
			return false;
		}

		$size = explode('x',low($options['size']));

		$SizeMode = $options['size'] . $options['mode']{0};

		// Get storage related config settings
		$store_local_path = Sanitize::getString($this->Config,'media_store_local_path');

		$store_thumbnail_folder = Sanitize::getString($this->Config,'media_store_local_thumbnail_folder');

		$store_photos_folder = Sanitize::getString($this->Config,'media_store_local_photo');

		$tn_basepath = PATH_ROOT . $store_local_path . $store_photos_folder . DS . $store_thumbnail_folder . DS . $SizeMode . DS;

        $tn_baseurl = WWW_ROOT . str_replace(DS, _DS, $store_local_path . $store_photos_folder . _DS . $store_thumbnail_folder) . _DS . $SizeMode . _DS;

		$is_absolute_url = substr($src,0,4) == 'http';

		$is_local_image = !$is_absolute_url || strstr($src,WWW_ROOT);

		if(!$is_local_image) {

			return $src;
		}

		if($is_absolute_url) {
			$src = str_replace(WWW_ROOT, '', $src);
		}

		$pathinfo = pathinfo($src);

		extract($pathinfo);

		$orig_path = PATH_ROOT . $src;

		$folder_hash = MediaStorageComponent::getFolderHash($filename);

		// $name = MediaStorageComponent::cleanFileName($filename, 'photo', $listing);

		// Need to use the same filename for thumbnails without any time or random modifiers.
		// Otherwise new thumbnails are generated on every page load
		$name = $filename;

		$tn_path = $tn_basepath . $folder_hash . $name . '.' . $extension;

		$tn_url = $tn_baseurl . str_replace(DS,_DS,$folder_hash) . $name . '.' . $extension;

		if(!file_exists($orig_path)) return false;

		$orig_size = getimagesize($orig_path);

		$thumbnail_exists = file_exists($tn_path);

		// If thumbnail doesn' exist, check orig dimensions vs. thumbnail dimensions to verify if thumbnailing is necessary
		if(!$thumbnail_exists && $orig_size[0] <= $size[0] && $orig_size[1] <= $size[1])
		{
			return WWW_ROOT . $src;
		}
		elseif($thumbnail_exists)
		{
			return $tn_url;
		}

		// Create new folder
		$Folder = new S2Folder($tn_basepath . $folder_hash, true, 0755);

		unset($Folder);

		if(!class_exists('PhpThumbFactory')) {
			S2App::import('Vendor', 'phpthumb' . DS . 'ThumbLib.inc');
		}

		ob_start();

		$Thumb = PhpThumbFactory::create($orig_path, array(
            'jpegQuality'=>$quality,
            'resizeUp'=>false
            ));

		if($options['mode'] == 'crop') {
			$Thumb->adaptiveResize($size[0],$size[1])->save($tn_path);
		}
		else {
			$Thumb->resize($size[0],$size[1])->save($tn_path);
		}

		ob_end_clean();

		if($Thumb->getHasError()) {
			appLogMessage($Thumb->getErrorMessage(), 'thumbnailer');
			return false;
		}

		$new_size = $Thumb->getCurrentDimensions();

		return $tn_url;
    }

	function checkUploadLimit($max, $count)
	{
		if($max == '0'){
			return '';

		}

		if($max == '') {
			return __t("no upload limit");
		}

		return $count >= $max ?

			sprintf(__t("upload limit reached (%s)",true),$max)

			:

			sprintf(__t("%1\$s remaining out of %2\$s",true),(int)$max - (int)$count, $max);
	}

	function getMediaKey($media_id)
	{
		return s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));
	}


	function formatFileSize($bytes)
	{

		if ($bytes > 0) {
			$unit = intval(log($bytes, 1024));
			$units = array('B', 'KB', 'MB', 'GB');

			if (array_key_exists($unit, $units) === true)
			{
				return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
			}
		}

		return $bytes;

	}

}