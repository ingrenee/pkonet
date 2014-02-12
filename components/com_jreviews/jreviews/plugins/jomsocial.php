<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JomsocialComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'jomsocial';

    var $type = 'user';

    var $published = true;

    var $points = false;

    var $activities = array(); // Defined below to use the translation function

    var $inAdmin = false;

    var $trim_words = 75;

    var $tn_mode;

    var $tn_size;

    function startup(&$controller)
    {
        $version = Configure::read('Community.version');

        $path = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_community' . DS . 'community.xml';

        if(defined('MVC_FRAMEWORK_ADMIN') && file_exists($path))
        {
            $xml = JFactory::getXML($path);

            $version = (string) $xml->version;

            $version_parts = explode('.', $version);

            $version = (int) array_shift($version_parts);
        }

        if($version < 3)
        {
            $this->published = false;

            return;
        }

        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        $this->c = & $controller;

        $path = PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php';

        $this->tn_mode = $this->c->Config->jomsocial_tnmode;

        $this->tn_size = $this->c->Config->jomsocial_tnsize;

        if(file_exists($path))
        {
            if(file_exists(PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'userpoints.php'))
            {
                $this->points = true;
            }

            S2App::import('Helper',array('routes','html','media','text'),'jreviews');

            $this->Routes = ClassRegistry::getClass('RoutesHelper');

            $this->Routes->app = 'jreviews';

            isset($controller->Config) and $this->Routes->Config = $controller->Config;;

            $this->Html = ClassRegistry::getClass('HtmlHelper');

            $this->Html->app = 'jreviews';

            $this->Media = ClassRegistry::getClass('MediaHelper');

            isset($controller->Config) and $this->Media->Config = $controller->Config;

            $this->Media->app = 'jreviews';

            $this->Media->name = $controller->name;

            $this->Media->action = $controller->action;

            $this->Text = ClassRegistry::getClass('TextHelper');
        }
        else {

            $this->published = false;
        }
    }

    function plgAfterSave(&$model)
    {
        appLogMessage('**** BEGIN JomSocial Plugin AfterSave', 'database');

        if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save'))) {

            return;
        }

        include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

        if($this->points)
        {
            include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'userpoints.php');
        }

        switch($model->name)
        {
            case 'Discussion':
                $this->_plgDiscussionAfterSave($model);
            break;

            case 'Favorite':
                $this->_plgFavoriteAfterSave($model);
            break;

            case 'Listing':
                $this->_plgListingAfterSave($model);
            break;

            case 'Media':
                $this->_plgMediaAfterSave($model);
            break;

            case 'MediaLike':
                $this->_plgMediaLikeAfterSave($model);
            break;

            case 'Review':
                $this->_plgReviewAfterSave($model);
            break;

            case 'Vote':
                $this->_plgVoteAfterSave($model);
            break;
        }
    }

    function plgBeforeDelete(&$model)
    {
        include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');
        if($this->points)
            {
                include_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'userpoints.php');
            }

        switch($model->name)
        {
            case 'Discussion':
                $this->_plgDiscussionBeforeDelete($model);
            break;

            case 'Listing':
                $this->_plgListingBeforeDelete($model);
            break;

            case 'Media':
                // $this->_plgMediaBeforeDelete($model);
            break;

            case 'Review':
                $this->_plgReviewBeforeDelete($model);
            break;
        }
    }

    function _plgDiscussionBeforeDelete(&$model)
    {
       $post_id = Sanitize::getInt($model->data,'post_id');

       // Get the post before deleting to make the info available in plugin callback functions
       $post = $model->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $post_id)),array());

        // Begin deduct points
        if($this->points && $post['Discussion']['user_id'] > 0  && $post['Discussion']['approved'] == 1)
        {
            CuserPoints::assignPoint('jreviews.discussion.delete',$post['Discussion']['user_id']);
        }
    }

    function _plgDiscussionAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_discussions');

        if($stream || $this->points)
        {
            $post = $this->_getReviewPost($model);
        }

        if($stream)
        {
            // Treat moderated reviews as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $post['Discussion']['modified'] != NULL_DATE) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $post['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            if(isset($model->isNew) && $post['Discussion']['approved'] == 1)
            {
                $listing = $this->_getListingEverywhere($post['Listing']['listing_id'],$post['Listing']['extension']);

                $extension = $post['Listing']['extension'];

                $review_url = $this->Routes->reviewDiscuss('',$post,array('listing'=>$listing,'return_url'=>true));

                $thumb_src = $this->getActivityThumb($listing);

                $act = array(
                    'cmd'=>'jreviews.discussion.'.$extension,
                    'app'=>'jreviews.discussion.'.$extension,
                    'cid'=>$post['Discussion']['discussion_id'],
                    'actor'=>$post['User']['user_id'],
                    'target'=>0,
                    'title'=>'',
                    'content'=>$post['Discussion']['text']
                );

                $params = array(
                    'action'=>$model->isNew && $post['Discussion']['modified'] == NULL_DATE ? 'new' : 'edit',
                    'discussion_id'=>$post['Discussion']['discussion_id'],
                    'listing_id'=>$listing['Listing']['listing_id'],
                    'review_id'=>$post['Discussion']['review_id'],
                    'extension'=>$extension,
                    'listing_title'=>$listing['Listing']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'review_url'=>$review_url,
                    'thumb_src'=>$thumb_src,
                    'likes'=>1,
                    'comments'=>1
                );

                $this->streamPost($act, $params);
            }
        }

        // Begin add points
        if($this->points && $model->isNew && $post['Discussion']['approved'] == 1)
        {
            CuserPoints::assignPoint('jreviews.discussion.add',$post['User']['user_id']);
        }
    }

    function _plgFavoriteAfterSave(&$model)
    {
        if($stream = Sanitize::getInt($this->c->Config,'jomsocial_favorites'))
        {
            $listing = $this->_getListing($model);

            $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

            $thumb_src = $this->getActivityThumb($listing);

           if($stream == 1 && $this->c->action == '_favoritesDelete') return; // Don't run for removals

            $act = array(
                'cmd'=>'jreviews.favorite',
                'app'=>'jreviews.favorite',
                'cid'=>$listing['Listing']['listing_id'],
                'actor'=>$this->c->_user->id,
                'target'=>0,
                'title'=>$listing['Listing']['title'],
                'content'=>$listing['Listing']['summary'] . ' ' . $listing['Listing']['description']
            );

            $params = array(
                'action'=>$this->c->action == '_favoritesDelete' ? 'remove' : 'add',
                'listing_id'=>$listing['Listing']['listing_id'],
                'listing_url'=>$listing['Listing']['url'],
                'thumb_src'=>$thumb_src,
                'likes'=>1,
                'comments'=>1
            );

            $this->streamPost($act, $params);
        }
    }

    function _plgListingBeforeDelete(&$model)
    {
        if($listing = $this->_getListing($model))
        {
			if($this->points && $listing['Listing']['user_id'] > 0 && $listing['Listing']['state'] == 1 && !$this->_isPaidListing($listing))
            {
                // Begin deduct points
                CuserPoints::assignPoint('jreviews.listing.delete',$listing['Listing']['user_id']);
            }
        }
    }

    function _plgListingAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_listings');

        if($stream || $this->points)
        {
            $listing = $this->_getListing($model);
        }

        if($stream)
        {
            // Treat moderated listings as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $listing['Listing']['modified'] != NULL_DATE) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $listing['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            if(isset($model->isNew) && $listing['Listing']['state'] == 1)
            {
                $thumb_src = $this->getActivityThumb($listing);

                $act = array(
                    'cmd'=>'jreviews.listing',
                    'app'=>'jreviews.listing',
                    'cid'=>$listing['Listing']['listing_id'],
                    'actor'=>$listing['User']['user_id'],
                    'target'=>0,
                    'title'=>$listing['Listing']['title'],
                    'content'=>$listing['Listing']['summary'] . ' ' . $listing['Listing']['description']
                );

                $params = array(
                    'action'=>$model->isNew && $listing['Listing']['modified'] == NULL_DATE ? 'new' : 'edit',
                    'cat_title'=>$listing['Category']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'thumb_src'=>$thumb_src,
                    'likes'=>1,
                    'comments'=>1
                );

                $this->streamPost($act, $params);
            }
        }

		if($this->points && isset($model->isNew) && $model->isNew && $listing['Listing']['state'] == 1 && !$this->_isPaidListing($listing))
        {
            // Begin add points
            CuserPoints::assignPoint('jreviews.listing.add',$listing['User']['user_id']);
        }
    }

    function _plgMediaLikeAfterSave(&$model)
    {
        $conditions = array();

        $stream = Sanitize::getInt($this->c->Config,'jomsocial_media');

        if(!$stream) return false;

        if(!$this->inAdmin)
        {
            $media_id = Sanitize::getInt($model->data,'media_id');

            $vote = Sanitize::getInt($model->data,'vote');

            $MediaModel = ClassRegistry::getClass('MediaModel');

            $media = $MediaModel->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array('afterFind','plgAfterFind'));

            $user_id = Sanitize::getInt($media['Media'],'user_id');

            $listing_id = $media['Media']['listing_id'];

            $review_id = $media['Media']['review_id'];

            $extension = $media['Media']['extension'];

            $listing = $this->_getListingEverywhere($listing_id,$extension);

            if(!$listing['Listing']['state']) return;

            $m = $media;

            $activity_thumb = $this->Media->thumb($m,array(
                'thumbnailer'=>'api',
                'size'=>$this->tn_size,
                'mode'=>$this->tn_mode,
                'return_thumburl'=>true,
                'return_url'=>true
                ));

            $m['ListingType'] = $listing['ListingType'];

            $media_url = $this->Routes->mediaDetail($activity_thumb, array('media'=>$m, 'listing'=>$listing), array('return_url'=>true));

            $mediaArray[$m['Media']['media_id']] = array(
                'media_id'=>$m['Media']['media_id'],
                'title'=>$m['Media']['title'],
                'description'=>$m['Media']['description'],
                'orig_src'=>$m['Media']['media_info']['image']['url'],
                'thumb_src'=>$activity_thumb,
                'media_url'=>$media_url
            );

            $media_type = $media['Media']['media_type'];

            if($media_type == 'video')
            {
                $mediaArray[$m['Media']['media_id']]['duration'] = $m['Media']['duration'];
            }

            $act = array(
                'cmd'=>'jreviews.medialike',
                'app'=>'jreviews.medialike',
                'cid'=>$media_id,
                'actor'=>$this->c->_user->id,
                'target'=>$media['Media']['user_id'],
                'title'=>$media['Media']['title'],
                'content'=>$media['Media']['description']
            );

            $params = array(
                'action'=>$vote ? 'yes' : 'no',
                'listing_id'=>$listing_id,
                'review_id'=>$review_id,
                'media_id'=>$media_id,
                'extension'=>$extension,
                'media_type'=>$media_type,
                'media_url'=>$media_url,
                'listing_title'=>$listing['Listing']['title'],
                'listing_url'=>$listing['Listing']['url'],
                'media'=>$mediaArray,
                'likes'=>1,
                'comments'=>1
            );

            $this->streamPost($act, $params);
        }
    }

    function _plgMediaAfterSave(&$model)
    {
        $conditions = array();

        $stream = Sanitize::getInt($this->c->Config,'jomsocial_media');

        if(!$stream) return false;

        if(!$this->inAdmin || ($this->inAdmin && Sanitize::getBool($model->data,'moderation')))
        {

            if(Sanitize::getBool($model->data,'finished')
                && Sanitize::getInt($model->data['Media'],'published') == 1
                && Sanitize::getInt($model->data['Media'],'approved') == 1)
            {
                $media_id = Sanitize::getInt($model->data['Media'],'media_id');

                $listing_id = Sanitize::getInt($model->data['Media'],'listing_id');

                $review_id = Sanitize::getInt($model->data['Media'],'review_id');

                $extension = Sanitize::getString($model->data['Media'],'extension');

                $media_type = Sanitize::getString($model->data['Media'],'media_type');

                $media = $model->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

                $user_id = Sanitize::getInt($media['Media'],'user_id');

                $listing = $this->_getListingEverywhere($listing_id,$extension);

                if(!$listing['Listing']['state']) return;

                $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

                $conditions[] = 'Media.listing_id = ' . $listing_id;
                // $conditions[] = 'Media.review_id = ' . $review_id;
                $conditions[] = 'Media.extension = ' . $model->Quote($extension);
                $conditions[] = 'Media.user_id = ' . $user_id;
                $conditions[] = 'Media.published = 1';
                $conditions[] = 'Media.approved = 1';
                $conditions[] = 'Media.media_type = ' . $model->Quote($media_type);

                if($user_id == 0) {
                    $conditions[] = 'Media.media_id = '. $media_id;
                }
                else {
                    $conditions[] = '(
                            DATE(Media.created) = "'._TODAY.'"
                                || DATE(Media.modified) = "'._TODAY.'"
                                || Media.media_id = '. $media_id .'
                        )';
                }

                $queryData = array(
                    'conditions'=>$conditions,
                    'limit'=>10 // The number of photos displayed on the stream per user will be limtied to 10
                    );

                $media = $model->findAll($queryData,array('afterFind','plgAfterFind'));

                if(!$media) return;

                if(!in_array($media_type,array('attachment','audio')))
                {
                    $mediaArray = array();

                    foreach($media AS $m)
                    {

                        $activity_thumb = $this->Media->thumb($m,array(

                            'thumbnailer'=>'api',
                            'size'=>$this->tn_size,
                            'mode'=>$this->tn_mode,
                            'return_thumburl'=>true,
                            'return_url'=>true
                            ));

                        $m['ListingType'] = $listing['ListingType'];

                        $media_url = $this->Routes->mediaDetail($activity_thumb, array('media'=>$m, 'listing'=>$listing), array('return_url'=>true));

                        $mediaArray[$m['Media']['media_id']] = array(
                            'media_id'=>$m['Media']['media_id'],
                            'title'=>$m['Media']['title'],
                            'description'=>$m['Media']['description'],
                            'orig_src'=>$m['Media']['media_info']['image']['url'],
                            'thumb_src'=>$activity_thumb,
                            'media_url'=>$media_url
                        );

                        if($media_type == 'video')
                        {
                            $mediaArray[$m['Media']['media_id']]['duration'] = $m['Media']['duration'];
                        }
                    }
                }

                $act = array(
                    'cmd'=>'jreviews.'.$media_type.'.'.($review_id ? 'review' : 'listing').'.'.$extension,
                    'app'=>'jreviews.'.$media_type.'.'.($review_id ? 'review' : 'listing').'.'.$extension,
                    'cid'=>$review_id ? $review_id : $listing_id,
                    'actor'=>$user_id,
                    'target'=>0,
                    'title'=>$media['Media']['title'],
                    'content'=>$content
                );

                $params = array(
                    'listing_id'=>$listing_id,
                    'review_id'=>$review_id,
                    'media_id'=>$media_id,
                    'extension'=>$extension,
                    'media_type'=>$media_type,
                    'listing_title'=>$listing['Listing']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'media'=>$mediaArray,
                    'likes'=>1,
                    'comments'=>1
                );

                // Remove duplicate activities because we want to display multiple uploads in the same activity

                if($act['actor'] > 0 && count($mediaArray) > 0)
                {
                    $query  = "
                        DELETE
                            FROM #__community_activities
                        WHERE
                            app = " . $this->c->Quote( $act['app'] ) . "
                            AND cid = " . $act['cid'] . "
                            AND actor = " . $act['actor'] . "
                            AND created BETWEEN SUBTIME(" . $this->c->Quote( _CURRENT_SERVER_TIME ) . ", " . $this->c->Quote('00:02:00') . ")
                            AND ADDTIME(" . $this->c->Quote( _CURRENT_SERVER_TIME ) . ", " . $this->c->Quote('00:02:00') . ")"
                    ;

                    $this->c->_db->setQuery($query);

                    $this->c->_db->query();
                }

                $this->streamPost($act, $params);
            }
        }
    }

    function _plgReviewBeforeDelete(&$model)
    {
        $review_id = Sanitize::getInt($model->data['Review'],'id');

        $review = $model->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

        // Begin deduct points
        if($this->points && $review['Review']['published'] == 1 && $review['User']['user_id'] > 0)
        {
            CuserPoints::assignPoint('jreviews.review.delete',$review['User']['user_id']);
        }
    }

    function _plgReviewAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_reviews');

        /**
        * Check if there's something to do and run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        if($stream || $this->points)
        {
            $review = $this->_getReview($model);
        }

        /**
        * Publish activity to JomSocial stream
        */
        if($stream)
        {
            // Treat moderated reviews as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $review['Review']['modified'] != NULL_DATE) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $review['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            if(isset($model->isNew) && $review['Review']['published'] == 1)
            {
                $thumb_src= $this->getActivityThumb($review);

                $act = array(
                    'cmd'=>'jreviews.review.'.$review['Review']['extension'],
                    'app'=>'jreviews.review.'.$review['Review']['extension'],
                    'cid'=>$review['Review']['review_id'],
                    'actor'=>$review['User']['user_id'],
                    'target'=>$review['Review']['user_id'],
                    'title'=>$review['Review']['title'],
                    'content'=>$review['Review']['comments']
                );

                $params = array(
                    'action'=>isset($model->isNew) && $model->isNew  && $review['Review']['modified'] == NULL_DATE ? 'new' : 'edit',
                    'listing_id'=>$review['Review']['listing_id'],
                    'review_id'=>$review['Review']['review_id'],
                    'extension'=>$review['Review']['extension'],
                    'listing_title'=>$review['Listing']['title'],
                    'listing_url'=>$review['Listing']['url'],
                    'thumb_src'=>$thumb_src,
                    'scale'=>$this->c->Config->rating_scale,
                    'editor_review'=>$review['Review']['editor'],
                    'average_rating'=>$review['Rating']['average_rating'],
                    'likes'=>1,
                    'comments'=>1
                );

                $this->streamPost($act, $params);
            }
        }

        if($this->points)
        {
            if(isset($model->isNew) && $model->isNew && $review['Review']['published'] == 1)
            {
                // Begin add points
                CuserPoints::assignPoint('jreviews.review.add',$review['User']['user_id']);
            }
        }
    }

    function _plgVoteAfterSave(&$model)
    {
        if($stream = Sanitize::getInt($this->c->Config,'jomsocial_votes'))
        {
            if($stream == 1 && !$model->data['Vote']['vote_yes']) return; // Yes votes only

            !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

            $ReviewModel = ClassRegistry::getClass('ReviewModel');

            $review_id = $model->data['Vote']['review_id'];

            $review = $ReviewModel->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

            $listing = $this->_getListingEverywhere($review['Review']['listing_id'],$review['Review']['extension']);

            $review_url = $this->Routes->reviewDiscuss('',$review,array('listing'=>$listing,'return_url'=>true));

            $thumb_src = $this->getActivityThumb($listing);

            $act = array(
                'cmd'=>'jreviews.vote.'.$review['Review']['extension'],
                'app'=>'jreviews.vote.'.$review['Review']['extension'],
                'cid'=>$listing_id,
                'actor'=>$model->data['Vote']['user_id'],
                'target'=>$review['User']['user_id'],
                'title'=>$review['Review']['title'],
                'content'=>$review['Review']['comments']
            );

            $params = array(
                'action'=>$model->data['Vote']['vote_yes'] == 1 ? 'yes' : 'no',
                'listing_id'=>$review['Review']['pid'],
                'review_id'=>$review['Review']['review_id'],
                'extension'=>$review['Review']['extension'],
                'listing_title'=>$listing['Listing']['title'],
                'listing_url'=>$listing['Listing']['url'],
                'review_url'=>$review_url,
                'thumb_src'=>$thumb_src,
                'likes'=>0,
                'comments'=>0
            );

            $this->streamPost($act, $params);
        }
    }

    function streamPost($act, $params)
    {
        CFactory::load('libraries', 'activities');

        $act = (object) $act;

        $act->params = json_encode($params);

        CActivityStream::add($act);
    }

    function getActivityThumb($data)
    {
        if(!empty($data['MainMedia']))
        {

            $activity_thumb = $this->Media->thumb(Sanitize::getVar($data,'MainMedia'),array(
                'thumbnailer'=>'api',
                'listing'=>$data,
                'size'=>$this->tn_size,
                'mode'=>$this->tn_mode,
                'return_thumburl'=>true,
                'return_src'=>true,
                ));

            return $activity_thumb;
        }

        return false;
    }

    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing'])
            && count($this->c->viewVars['listing']['Listing']) > 3 /* Need to make sure that the whole listing array is there and not just a few keys */
            )
        {
            $listing = $this->c->viewVars['listing'];
        }
        else
        {
            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;

            !$listing_id and $listing_id = isset($this->c->data['Listing']) ? Sanitize::getInt($this->c->data['Listing'],'id') : false;

            !$listing_id and $listing_id = Sanitize::getInt($this->c->data,'listing_id');

            if(!$listing_id) return false;

            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = '. $listing_id)),array('afterFind' /* Only need menu id */));

			$this->c->set('listing',$listing);
        }

        if(isset($model->data['Listing']) && Sanitize::getInt($model->data['Listing'],'state'))
        {
            $listing['Listing']['state'] =  $model->data['Listing']['state'];
        }

        return $listing;
    }

    function _getListingEverywhere($listing_id,$extension)
    {
        if(isset($this->c->viewVars['listing_'.$extension]))
        {
           $listing = $this->c->viewVars['listing_'.$extension];
        }
        else
        {
            // Automagically load and initialize Everywhere Model
            S2App::import('Model','everywhere_'.$extension,'jreviews');

            $class_name = inflector::camelize('everywhere_'.$extension).'Model';

            if(class_exists($class_name)) {
                $ListingModel = new $class_name();

                // No need to add all this extra stuff just to get the listing url
                $ListingModel->addStopAfterFindModel(array('Favorite','Field','PaidOrder'));

                $ListingModel->addRunAfterFindModel(array('Media'));

                $listing = $ListingModel->findRow(array('conditions'=>array('Listing.'.$ListingModel->realKey.' = ' . $listing_id)));

                $this->c->set('listing_'.$extension,$listing);
            }
        }

        return $listing;
    }

	function _isPaidListing($listing)
	{
		if(Configure::read('PaidListings.enabled'))
		{
			S2App::import('Model','paid_plan_category');
			$PaidPlanCategory = ClassRegistry::getClass('PaidPlanCategoryModel');
			return $PaidPlanCategory->isInPaidCategory($listing['Listing']['cat_id']);
		}
		return false;
	}

    function _getReview(&$model)
    {
        $fields = $joins = array();

        if(isset($this->c->viewVars['review']))
        {
            $review = $this->c->viewVars['review'];
        }
        elseif(isset($this->c->viewVars['reviews']))
        {
            $review = current($this->c->viewVars['reviews']);
        }
        else
        {
            if($this->inAdmin and !Sanitize::getBool($model->data,'moderation')) {

                // Get updated review info for non-moderated actions and plugin callback
                $fields = array(
                    'Criteria.id AS `Criteria.criteria_id`',
                    'Criteria.criteria AS `Criteria.criteria`',
                    'Criteria.state AS `Criteria.state`',
                    'Criteria.tooltips AS `Criteria.tooltips`',
                    'Criteria.weights AS `Criteria.weights`'
                );

            }

            $joins = array();

            if(!isset($model->joins['Listing'])) {

                $joins = $this->c->Listing->joinsReviews;
            }

             // Triggers the afterFind in the Observer Model
            $this->c->EverywhereAfterFind = true;

            $review = $model->findRow(array(
                'fields'=>$fields,
                'conditions'=>'Review.id = ' . $model->data['Review']['id'],
                'joins'=>$joins
                ), array('afterFind','plgAfterFind' /* limit callbacks */)
            );

            $this->c->set('review',$review);
        }

        return $review;
    }

    function _getReviewPost(&$model)
    {
        if(isset($this->c->viewVars['post']))
        {
            $post = $this->c->viewVars['post'];
        }
        else
        {
            $post = $model->findRow(array(
                'conditions'=>array(
                    'Discussion.type = "review"',
                    'Discussion.discussion_id = ' . $model->data['Discussion']['discussion_id']
                    ))
            );
            $this->c->set('post',$post);
        }
        return $post;
    }
}
