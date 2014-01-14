<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaController extends MyController
{
    var $uses = array('menu','media','review','user','media_encoding','media_like','registration');

    var $helpers = array('cache','libraries','html','text','assets','paginator','jreviews','widgets','form','routes','time','community','media');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    var $denyAccess = false;

	var $layout = 'media';

	private $formTokenKeysEdit = array('media_id'=>'media_id','listing_id'=>'listing_id','review_id'=>'review_id','extension'=>'extension','user_id'=>'user_id');

	/*
	 * Pass page related params from one controller action to another one.
	 */
	private $page_params = array();

    function getPluginModel()
    {
        if(in_array($this->action,array('_like','_dislike'))) {

        	return $this->MediaLike;
        }
        else {

        	return $this->Media;
        }
    }

	function beforeFilter()
    {
		$this->Access->init($this->Config);

        parent::beforeFilter();
	}

    function getEverywhereModel() {

        return $this->Media;
    }

	function _like()
	{
		$media_id = Sanitize::getInt($this->data, 'media_id');

		if($media_id > 0 && $this->MediaLike->vote($media_id, 1)) {

			return cmsFramework::jsonResponse(array('success'=>true));
		}

		return cmsFramework::jsonResponse(array('success'=>false));
	}

	function _dislike()
	{
		$media_id = Sanitize::getInt($this->data, 'media_id');

		if($media_id > 0 && $this->MediaLike->vote($media_id, 0)) {

			return cmsFramework::jsonResponse(array('success'=>true));
		}

		return cmsFramework::jsonResponse(array('success'=>false));
	}

	/**
	 * Displays list of media
	 */
	function mediaList($params = array(), $conditions = array())
	{
        if($this->_user->id === 0)
        {
	            $this->cacheAction = Configure::read('Cache.expires');
        }

		$page = array();

		$this->EverywhereAfterFind = true;

		$media = array();

		$count = 0;

        $menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));

		$user_id = Sanitize::getInt($this->params,'user', $this->_user->id);

		$media_type = Sanitize::getString($this->params,'type',Sanitize::getString($this->data,'media_type'));

		$extension = Sanitize::getString($this->data,'extension');

		$sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort != '';

		if($sort == '') {
			$sort = Sanitize::getString($this->data,'order',Sanitize::getString($this->Config,'media_general_default_order'));
		}

		// Determine which media states to show depending on the action
		switch($this->action) {
			// Show only published and approved media for guests, everything to managers and above
			case 'mediaList':
			case 'listing':
				if(!$this->Access->isManager()) {
					$conditions[] = 'Media.published = 1';
					$conditions[] = 'Media.approved = 1';
				}
				break;
			case 'myMedia':
				if($this->_user->id != $user_id && !$this->Access->isManager()) {
					$conditions[] = 'Media.published = 1';
					$conditions[] = 'Media.approved = 1';
				}
				break;
		}

        $total_special = Sanitize::getInt($this->data,'total_special');

        if($total_special > 0) {

            $total_special <= $this->limit and $this->limit = $total_special;
        }

		$queryData = array(
			'conditions'=>$conditions,
			'offset'=>$this->offset,
			'limit'=>$this->limit
		);

		if(in_array($media_type,array('video','photo','attachment','audio'))) {
			$queryData['conditions'][] = 'Media.media_type = ' . $this->Quote($media_type);
		}

		$extension != '' and $conditions[] = 'Media.extension = ' . $this->Quote($extension);

		$this->Media->processSorting($sort);

        // Makes sure only media for published listings is shown
        $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Media.listing_id','extension'=>'Media.extension'));

		$media = $this->Media->findAll($queryData,array('afterFind','plgAfterFind'));

		$count = $this->Media->findCount($queryData);

        if($total_special > 0 && $total_special < $count)
        {
            $count = Sanitize::getInt($this->data,'total_special');
        }

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

        /******************************************************************
        * Generate SEO canonical tags for sorted pages
        *******************************************************************/
        if($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl('order');
        }

        $menuParams = $this->Menu->getMenuParams($menu_id);

		$page['show_description'] = Sanitize::getInt($menuParams,'show_description');

		$page['description'] = Sanitize::getString($menuParams,'description');

		$page['pageclass_sfx'] = Sanitize::getString($menuParams,'pageclass_sfx');

		$page['show_media_type_filter'] = Sanitize::getInt($menuParams,'show_media_type_filter',Sanitize::getInt($this->page_params,'show_media_type_filter',0));

		$page['show_order_list'] = Sanitize::getInt($menuParams,'show_order_list',Sanitize::getInt($this->page_params,'show_order_list',0));

		$this->set(array(
			'media'=>$media,
			'page'=>$page,
			'pagination'=>array(
				'total'=>$count,
				'offset'=>($this->page-1)*$this->limit
			),
			'User'=>$this->_user,
			'formTokenKeysEdit'=>$this->formTokenKeysEdit
		));

		return $this->render('media','list');
	}

	/**
	 * Displays list of media for a specific listing with configurable self-management actions
	 * like edit, delete, add, etc.
	 */
	function listing()
	{
		// Check for edit permissions

		$listing_id = Sanitize::getInt($this->params,'id');

		$extension = Sanitize::getString($this->params,'extension','com_content');

		$media_type = Sanitize::getString($this->params,'type',Sanitize::getString($this->data,'media_type'));

        $page = $media = array();

		$this->EverywhereAfterFind = true;

		$count = 0;

		if(!$listing_id) {
			cmsFramework::noAccess();
            $this->autoRender = false;
			return;
		}

		$this->Listing->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder'));

		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)),array('afterFind'));

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        if (!$this->Access->canEditListing($listing['Listing']['user_id'])) {
            cmsFramework::noAccess();
            $this->autoRender = false;
            return;
        }

		$separate_owner_media = $this->Config->media_detail_separate_media;

		if($separate_owner_media) {

			$owner_id = $listing['Listing']['user_id'];

			$this->Media->fields[] = "
				IF(media_type = 'photo' OR media_type = 'video',CONCAT(media_type,IF(user_id = {$owner_id}, '_owner', '_user')),media_type) AS `media_type_by`
			";

			$order = "media_type, FIELD(user_id, {$owner_id}) DESC, user_id, ordering";

			$joins = array('JOIN (SELECT @media_type_user := "") r');
		}
		else {

			$this->Media->fields[] = "media_type AS media_type_by";

			$order ='`media_type_by`, Media.ordering';
		}

		$conditions = array(
			'Media.listing_id = ' . $listing_id,
			'Media.extension = ' . $this->Quote($extension)
		);

		if(!$this->Access->isManager()) {
			$conditions[] = '(Media.published = 1 AND Media.approved = 1 || Media.user_id = ' . $this->_user->id . ')';
		}

		$queryData = array(
			'conditions'=>$conditions,
			// 'offset'=>$this->offset,
			// 'limit'=>$this->limit,
			'order'=>$order
		);

		if(in_array($media_type,array('video','photo','attachment','audio'))) {
			$queryData['conditions'][] = 'Media.media_type = ' . $this->Quote($media_type);
		}

		$extension != '' and $conditions[] = 'Media.extension = ' . $this->Quote($extension);

		$media = $this->Media->findAll($queryData,array('afterFind','plgAfterFind'));

		$page['title'] = $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LISTING_MEDIA_TITLE_SEO'), $listing['Listing']['title']);

		$page['show_title'] = 1;

		$this->set(array(
			'media'=>$media,
			'listing'=>$listing,
			'page'=>$page,
			'User'=>$this->_user,
			'formTokenKeysEdit'=>$this->formTokenKeysEdit
		));

		return $this->render('media','list_manage');

	}

	/**
	 * Displays list of media for a specific user with configurable self-management actions
	 * like edit, delete, add, etc.
	 */
	function myMedia()
	{
		// Get the user id from the url first, otherwise use the session
		$user_id_param = Sanitize::getInt($this->params,'user');

		$user_id_session = $this->_user->id;

		// No user id info so we show the login form
        if(!$user_id_session && !$user_id_param)
        {
            echo $this->render('elements','login');
            return;
        }

        $name_choice = ($this->Config->name_choice == 'alias' ? 'username' : 'name');

		$media_type = Sanitize::getString($this->params,'type',Sanitize::getString($this->data,'media_type'));

		$media_type == '' and $media_type = 'all';

		if($user_id_param > 0)
		{
			$user_id = $user_id_param;

			$this->User->fields = array();

			$user_name = $this->User->findOne(
				array(
					'fields'=>array('User.' . $name_choice. ' AS `User.name`'),
					'conditions'=>array('User.id = ' . $user_id)
				)
			);
		}
		elseif($user_id_session > 0)
		{
			$user_id = $user_id_session;

			$user_name = $this->_user->{$name_choice};
		}

		$conditions = array(
			'Media.user_id = ' . $user_id
		);

		$this->page_params['title'] = sprintf(JreviewsLocale::getPHP('MEDIA_MYMEDIA_'.strtoupper($media_type)),$user_name);

		$this->page_params['show_title'] = 1;

		return $this->mediaList($this->params, $conditions);
	}


	/**
	 * Delivers file to user for download
	 */
	function download()
	{
		$no_access = '<script type="text/javascript">window.parent.jQuery.jrAlert(jreviews.__t("ACCESS_DENIED"));</script>';

		$file_not_found = '<script type="text/javascript">window.parent.jQuery.jrAlert(jreviews.__t("MEDIA_DOWNLOAD_NOT_FOUND"));</script>';

		$media_id = Sanitize::getString($this->params,'media_id');

		if(!$media_id) return;

		$this->EverywhereAfterFind = true;

		// Update view count
		$this->Media->views($media_id);

		$media = $this->Media->findRow(array('conditions'=>array(
			'Media.media_id = ' . $media_id,
			'Media.media_type = "attachment"',
			'Media.published = 1',
			'Media.approved = 1'
		)));

		# Stop form data tampering
		$formToken = cmsFramework::getCustomToken($media['Media']['media_id'],$media['Media']['media_type'],$media['Media']['filename'], $media['Media']['created']);

		if (!$this->__validateToken($formToken))
		{
			die($invalid_token);
		}

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

		if(!$this->Access->isAuthorized($media['Media']['access'])) {

			die($no_access);
		}

		$res = $this->MediaStorage->download($media);

		if($res === false) {

			die($file_not_found);
		}
	}


	/**
	 * Displays the attachments for a specific listing
	 */
	function attachments()
	{
        if($this->_user->id === 0)
        {
	            $this->cacheAction = Configure::read('Cache.expires');
        }

		$this->EverywhereAfterFind = true;

		$listing_id = 0;

		$extension = '';

		$listing = array();

		$media_id = Sanitize::getString($this->params,'media_id');

		if($media_id && $listing_info = $this->Media->getListingInfo($media_id)) {

			extract($listing_info);
		}
		else {

			$id = explode(':',base64_decode(urldecode(Sanitize::getString($this->params,'id'))));

			$listing_id = (int) array_shift($id);

			$extension = array_shift($id);

		}

		if(!$listing_id) {
			return cmsFramework::noAccess();
		}

		$sort = Sanitize::getString($this->params,'order',Sanitize::getString($this->Config,'attachment_order_default'));

		$this->Media->processSorting($sort);

		$conditions = array(
			'Media.listing_id = ' . $listing_id,
			'Media.media_type = "attachment"'
		);

		if(!$this->Access->isManager()) {
			$conditions[] = 'Media.published = 1';
			$conditions[] = 'Media.approved = 1';
		}

		$attachments = $this->Media->findAll(array('conditions'=>$conditions));

		$this->set(array(
			'media_id'=>$media_id,
			'listing_id'=>$listing_id,
			'attachments'=>$attachments
		));

		return $this->render('media','attachments');
	}

	function audioList()
	{
		prx($this->data);
	}

	/**
	 * Re-orders media for a specific media type
	 */
	function reorder()
	{
        # Validate form token
		if($this->invalidToken) {
			return cmsFramework::jsonResponse(array('success'=>false));
        }

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $extension = Sanitize::getString($this->data,'extension');

        $media_type = Sanitize::getString($this->data,'media_type');

		# Stop form data tampering
		$formToken = cmsFramework::getCustomToken($listing_id.$extension,false);

		$this->Listing->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder'));

		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)),array('afterFind'));

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        if (!$this->Access->canEditListing($listing['Listing']['user_id'])  || !$this->__validateToken($formToken)) {
			return cmsFramework::jsonResponse(array('success'=>false));
        }

		$ordering = Sanitize::getVar($this->data,'ordering');

		if($this->Media->reorderMedia($ordering)) {

			return cmsFramework::jsonResponse(array('success'=>true));

		}

		return cmsFramework::jsonResponse(array('success'=>false));
	}

	/**
	 * Displays the photos for a specific listing
	 */
	function photoGallery()
	{
        if($this->_user->id === 0)
        {
			$this->cacheAction = Configure::read('Cache.expires');
        }

		$listing_id = 0;

		$extension = '';

		$owner_id = null;

		$page = array();

		$media_id = Sanitize::getString($this->params,'media_id');

		$submitted_by = Sanitize::getString($this->params,'by');

		if($media_id && $listing_info = $this->Media->getListingInfo($media_id)) {

			extract($listing_info);
		}
		else {

			$id = explode(':',base64_decode(urldecode(Sanitize::getString($this->params,'id'))));

			$listing_id = (int) array_shift($id);

			$extension = array_shift($id);
		}

		if(!$listing_id) {
			return cmsFramework::noAccess();
		}

		$this->EverywhereAfterFind = true;

		$sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort != '';

		if($sort == '') {
			$sort = Sanitize::getString($this->Config,'media_general_default_order_listing');
		}

		$this->Media->processSorting($sort);

		$conditions = array(
			'Media.listing_id = ' . $listing_id,
			'Media.media_type = "photo"',
			'Media.extension = ' . $this->Quote($extension)
		);

		if($extension == 'com_content' && in_array($submitted_by,array('owner','users','reviewer')) && $owner_id > 0) {

			switch($submitted_by) {

				case 'owner':

					$conditions[] = 'Media.user_id = ' . $owner_id;
				break;

				case 'users':
					$conditions[] = 'Media.user_id != ' . $owner_id;
				break;

				case 'reviewer':
					$conditions[] = 'Media.review_id = ' . $review_id;
				break;
			}
		}

		if(!$this->Access->isManager()) {
			$conditions[] = 'Media.published = 1';
			$conditions[] = 'Media.approved = 1';
		}

		$photos = $this->Media->findAll(array(
			'conditions'=>$conditions
		));

		if($listing_id > 0) {

			$listing = reset($photos);

			$this->Config->override($listing['ListingType']['config']);

			switch($submitted_by) {

				case 'owner':

					$page['title_seo'] = sprintf(JreviewsLocale::getPHP('MEDIA_OWNER_PHOTOS_FOR_LISTING'),$listing['Listing']['title']);
				break;

				case 'users':
					$page['title_seo'] = sprintf(JreviewsLocale::getPHP('MEDIA_USER_PHOTOS_FOR_LISTING'),$listing['Listing']['title']);
				break;

				case 'reviewer':
					$page['title_seo'] = sprintf(JreviewsLocale::getPHP('MEDIA_REVIEWER_PHOTOS_FOR_LISTING'),$listing['Listing']['title']);
				break;

				default:
					$page['title_seo'] = sprintf(JreviewsLocale::getPHP('MEDIA_PHOTOS_FOR_LISTING'),$listing['Listing']['title']);
				break;
			}
		}

        /******************************************************************
        * Generate SEO canonical tags for sorted pages
        *******************************************************************/
        if($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl('order');
        }

		$this->set(array(
			'media_id'=>$media_id,
			'listing_id'=>$listing_id,
			'photos'=>$photos,
			'page'=>$page
		));

		return $this->render('media','photo_gallery');
	}

	/**
	 * Updates media views count
	 */
	function _increaseView()
	{
		$media_id = Sanitize::getInt($this->params,'media_id');

		if(!$media_id) return;

		// Update view count
		$this->Media->views($media_id);
	}

	/**
	 * Displays the videos for a specific listing
	 */
	function videoGallery()
	{
        if($this->_user->id === 0)
        {
	            $this->cacheAction = Configure::read('Cache.expires');
        }

		$page = array();

		$listing_id = 0;

		$owner_id = null;

		$extension = '';

		$canonical = false;

		$m = Sanitize::getString($this->params,'m');

		$media_id = Sanitize::getString($this->params,'media_id');

		$submitted_by = Sanitize::getString($this->params,'by');

		$lightbox = Sanitize::getString($this->params,'lightbox');

		if($lightbox) {
			$this->layout = '';
		}

		if($media_id && $listing_info = $this->Media->getListingInfo($media_id)) {

			extract($listing_info);
		}
		else {

			$id = explode(':',base64_decode(urldecode(Sanitize::getString($this->params,'id'))));

			$listing_id = (int) array_shift($id);

			$extension = array_shift($id);

			$canonical = true;
		}

		if(!$listing_id) {

			// Listing not found
			cmsFramework::redirect(cmsFramework::route('index.php?option=com_jreviews&url=404'));
			exit;
		}

		// Update view count
		$this->Media->views($media_id);

		$this->EverywhereAfterFind = true;

		$sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort != '';

		if($sort == '') {
			$sort = Sanitize::getString($this->Config,'media_general_default_order_listing');
		}

		$this->Media->processSorting($sort);

		$conditions = array(
			'Media.listing_id = ' . $listing_id,
			'Media.media_type = "video"',
			'Media.extension = ' . $this->Quote($extension)
		);

		if($extension == 'com_content' && in_array($submitted_by,array('owner','users','reviewer')) && $owner_id > 0) {

			switch($submitted_by) {

				case 'owner':

					$conditions[] = 'Media.user_id = ' . $owner_id;
				break;

				case 'users':
					$conditions[] = 'Media.user_id != ' . $owner_id;
				break;

				case 'reviewer':
					$conditions[] = 'Media.review_id = ' . $review_id;
				break;
			}
		}

		if(!$this->Access->isManager()) {

			$conditions[] = 'Media.published = 1';

			$conditions[] = 'Media.approved = 1';
		}

		$videos = $this->Media->findAll(array(
			'conditions'=>$conditions
		));

		if($listing_id > 0) {

			$listing = reset($videos);

			$this->Config->override($listing['ListingType']['config']);
		}

		$video = $media_id && isset($videos[$media_id]) ? $videos[$media_id] : reset($videos);

		$media_id = $video['Media']['media_id'];

		if($canonical) {

			$RoutesHelper = ClassRegistry::getClass('RoutesHelper');

			$RoutesHelper->name = 'media';

			$RoutesHelper->action = 'videoGallery';

			$RoutesHelper->params = $this->params;

			$RoutesHelper->Config = $this->Config;

			$media = array('Media'=>array(
				'video'=>array('media_id'=>$media_id)
				));

			$media_by = Sanitize::getString($this->params,'by');

			$page['canonical'] = $RoutesHelper->mediaDetail('',array('media_by'=>$media_by,'media'=>$video),array('return_url'=>true));
		}

		S2App::import('Helper','community','jreviews');

		$page['title_seo'] = $video['Media']['title'] != '' ? $video['Media']['title'] . ' - ' . $listing['Listing']['title'] : sprintf(JreviewsLocale::getPHP('MEDIA_VIDEO_FOR_LISTING'),$listing['Listing']['title']);

		$page['keywords'] = 'video,'.$listing['Listing']['title'];

		$page['description'] = $video['Media']['description'];

		$this->set(array(
			'm'=>$m,
			'media_id'=>$media_id,
			'listing_id'=>$listing_id,
			'videos'=>$videos,
			'page'=>$page
		));

		return $this->render('media',$lightbox ? 'video_gallery_detail' : 'video_gallery');
	}

	function setMainMedia()
	{
		$response = array('success'=>false,'str'=>array());

		$media_id = Sanitize::getInt($this->params,'media_id');

		$listing_id = Sanitize::getInt($this->params,'listing_id');

		$media = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

		if(!cmsFramework::isAdmin())
		{
			# Stop form data tampering
			$formToken = cmsFramework::formIntegrityToken($media['Media'],array_keys($this->formTokenKeysEdit),false);

			if (!$this->__validateToken($formToken)) {

				$response['str'][] = 'ACCESS_DENIED';

				return cmsFramework::jsonResponse($response);
			}
		}

		if($this->Media->setMainMedia($listing_id, $media_id)) {

			$response['success'] = true;

			return cmsFramework::jsonResponse($response);
		}

		return cmsFramework::jsonResponse($response);
	}

	function _edit()
	{
		$media_id = Sanitize::getInt($this->params,'media_id');

		$mediaEncoding = array();

		if($media_id) {

			$media = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

			$listingOwner = $this->Listing->getListingOwner($media['Media']['listing_id']);

			if(in_array($media['Media']['media_type'],array('video','audio')) && $media['Media']['embed'] == '') {

				$mediaEncoding = $this->MediaEncoding->findRow(array(
					'conditions'=>array('MediaEncoding.media_id = ' . $media_id)
					));

			}

			$this->set(array(
				'media'=>$media,
				'listing_owner_id'=>$listingOwner['user_id'],
                'formTokenKeys'=>$this->formTokenKeysEdit,
				'mediaEncoding'=>$mediaEncoding
			));

			return $this->render('media','edit_form');
		}
	}

	function _saveEdit()
	{
        $response = array('success'=>false,'str'=>array());

		$guest_session_valid = false;

        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security'/*,'notifications'*/);

        $this->__initComponents();

		$media_id = Sanitize::getInt($this->data['Media'],'media_id');

        # Validate form token
		if($this->invalidToken)
		{
			$response['str'][] = 'INVALID_TOKEN';

			return cmsFramework::jsonResponse($response);
        }

		$currMedia = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

		$listingOwner = $this->Listing->getListingOwner($currMedia['Media']['listing_id']);

		# Stop form data tampering
		$formToken = cmsFramework::formIntegrityToken($currMedia['Media'],array_keys($this->formTokenKeysEdit),false);

		if($this->_user->id == 0) {

			$guest_session_valid = $this->Registration->validateGuestSession(array('media_id'=>$media_id));
		}

		$canEditMedia = $this->Access->canEditMedia($currMedia['Media']['media_type'],$currMedia['User']['user_id'],$listingOwner['user_id']);

		if (!defined('MVC_FRAMEWORK_ADMIN') && ((!$canEditMedia && !$guest_session_valid) || !$this->__validateToken($formToken))) {

			$response['str'][] = 'ACCESS_DENIED';

			return cmsFramework::jsonResponse($response);
		}

		# If changing published state and Paid Listing enforce media limits
		if(isset($this->PaidListings) && $currMedia['Media']['published'] == 0 && Sanitize::getInt($this->data['Media'],'published') == 1) {

			$publish_check = $this->PaidListings->isWithinMediaLimits($currMedia['Media']['listing_id'],$currMedia['Media']['review_id'],$currMedia['Media']['extension'],$currMedia['Media']['media_type']);

			if(!$publish_check) {

				$response['str'][] = 'MEDIA_'.strtoupper($currMedia['Media']['media_type']).'_PUBLISH_LIMIT';

				return cmsFramework::jsonResponse($response);
			}
		}

		# Build data array with only the necessary info to avoid modification of other columns
		$data = array();

		$data['Media']['media_id'] = $media_id;

		$data['Media']['listing_id'] = $currMedia['Media']['listing_id'];

		$data['Media']['review_id'] = $currMedia['Media']['review_id'];

		$data['Media']['title'] = Sanitize::html($this->data['Media'],'title','',true);

		$data['Media']['description'] = Sanitize::html($this->data['Media'],'description','',true);

		$data['Media']['modified'] = _CURRENT_SERVER_TIME;

		if(isset($this->data['Media']['published']) && $this->Access->canPublishMedia($currMedia['Media']['media_type'],$currMedia['User']['user_id'],$listingOwner['user_id']))
		{
			$data['Media']['published'] = Sanitize::getInt($this->data['Media'],'published');
		}

		if(isset($this->data['Media']['approved']) && $this->Access->canApproveMedia() === true)
		{
			$data['Media']['approved'] = Sanitize::getInt($this->data['Media'],'approved');
		}
		elseif(!isset($this->data['Media']['approved'])
				&& !$this->Access->canApproveMedia()
				&& Sanitize::getString($this->data,'referrer') != 'create'
				&& Sanitize::getInt($this->Config,'media_access_moderate_edit'))
		{
			$data['Media']['approved'] = 0;
		}

		// Make sure only editor and above can edit certain inputs if displayed in the form
		if($this->Access->isEditor()) {

			if(isset($this->data['Media']['duration'])) {

				$data['Media']['duration'] = Sanitize::getInt($this->data['Media'],'duration');
			}

			if(isset($this->data['Media']['user_id'])) {

				$data['Media']['user_id'] = Sanitize::getInt($this->data['Media'],'user_id');
			}
		}

		if($this->Media->store($data)) {

			$response['success'] = true;

			$response['title'] = $data['Media']['title'];

			return cmsFramework::jsonResponse($response);
		}

		$response['str'][] = 'DB_ERROR';

		return cmsFramework::jsonResponse($response);
	}

	function _publish()
	{
        $response = array('success'=>false,'str'=>array());

		$guest_session_valid = false;

		$media_id = Sanitize::getInt($this->params,'media_id');

		$currMedia = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

		$listingOwner = $this->Listing->getListingOwner($currMedia['Media']['listing_id']);

		# Stop form data tampering
		$formToken = cmsFramework::formIntegrityToken($currMedia['Media'],array_keys($this->formTokenKeysEdit),false);

		if($this->_user->id == 0) {

			$guest_session_valid = $this->Registration->validateGuestSession(array('media_id'=>$media_id));
		}

		$canPublishMedia = $this->Access->canPublishMedia($currMedia['Media']['media_type'],$currMedia['User']['user_id'],$listingOwner['user_id']);

		if (!defined('MVC_FRAMEWORK_ADMIN') && ((!$canPublishMedia && !$guest_session_valid) || !$this->__validateToken($formToken))) {

			$response['str'][] = 'ACCESS_DENIED';

			return cmsFramework::jsonResponse($response);
		}

		# Build data array with only the necessary info to avoid modification of other columns
		$data = array();

		$data['Media']['media_id'] = $media_id;

		$data['Media']['listing_id'] = $currMedia['Media']['listing_id'];

		$data['Media']['review_id'] = $currMedia['Media']['review_id'];

		$data['Media']['extension'] = $currMedia['Media']['extension'];

		$data['Media']['published'] = (int) !$currMedia['Media']['published'];

		# If changing published state and Paid Listing enforce media limits
		if(isset($this->PaidListings) && $currMedia['Media']['published'] == 0 && Sanitize::getInt($data['Media'],'published') == 1) {

			$publish_check = $this->PaidListings->isWithinMediaLimits($currMedia['Media']['listing_id'],$currMedia['Media']['review_id'],$currMedia['Media']['extension'],$currMedia['Media']['media_type']);

			if(!$publish_check) {

				$response['str'][] = 'MEDIA_'.strtoupper($currMedia['Media']['media_type']).'_PUBLISH_LIMIT';

				return cmsFramework::jsonResponse($response);
			}
		}

		if($this->Media->store($data)) {

			$response['success'] = true;

			$response['state'] = $data['Media']['published'];

			return cmsFramework::jsonResponse($response);
		}

		$response['str'][] = 'DB_ERROR';

		return cmsFramework::jsonResponse($response);
	}

	function _delete()
	{
		$response = array();

		$response['success'] = false;

		if(isset($this->data['Media']) && $media_id = Sanitize::getInt($this->data['Media'],'media_id'))
		{
			$currMedia = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

			$listingOwner = $this->Listing->getListingOwner($currMedia['Media']['listing_id']);

			$guest_session_valid = $this->_user->id ? true : $this->Registration->validateGuestSession(array('media_id'=>$media_id));

			# Stop form data tampering
			$formToken = cmsFramework::formIntegrityToken($currMedia['Media'],array_keys($this->formTokenKeysEdit),false);

			if(!defined('MVC_FRAMEWORK_ADMIN') &&
				(
					(
						!$this->Access->canDeleteMedia($currMedia['Media']['media_type'],$currMedia['User']['user_id'],$listingOwner['user_id']) &&
						!$guest_session_valid
					)
					|| !$this->__validateToken($formToken)
				)

			) {
				$response['str'] = 'ACCESS_DENIED';

				return cmsFramework::jsonResponse($response);
			}

			if($this->Media->del($media_id))
			{
				$response['success'] = true;
			}

			return cmsFramework::jsonResponse($response);
		}
	}

	function _deleteThumb()
	{
		$response = array('success'=>false,'str'=>array());

        $media_id = Sanitize::getVar($this->params,'id');

        $size = Sanitize::getVar($this->params,'size');

        if(empty($media_id)) {

            return cmsFramework::jsonResponse($response);
        }

		$currMedia = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

		# Stop form data tampering
		$formToken = cmsFramework::formIntegrityToken($currMedia['Media'],array_keys($this->formTokenKeysEdit),false);

		if(!$this->Access->isEditor() || !$this->__validateToken($formToken)) {

			$response['str'][] = 'ACCESS_DENIED';

			return cmsFramework::jsonResponse($response);
		}

		# Delete media thumb
		$deleted = $this->Media->delThumb($media_id, $size);

        if ($deleted) {
        	$response['success'] = true;
		}

		return cmsFramework::jsonResponse($response);
	}
}
