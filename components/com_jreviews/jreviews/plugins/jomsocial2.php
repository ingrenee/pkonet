<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class Jomsocial2Component extends S2Component {

    var $plugin_order = 100;

    var $name = 'jomsocial2';

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

        if($version >= 3)
        {
            $this->published = false;

            return;
        }

        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        $this->c = & $controller;

        $this->activities = array(
          "listing_new"=>__t("{actor} added new listing %1\$s in %2\$s.",true), // 1: listing title; 2: listing category
          "listing_edit"=>__t("{actor} updated listing %1\$s.",true),
          "review_new"=>__t("{actor} reviewed %1\$s.",true),
          "review_edit"=>__t("{actor} updated review for %1\$s.",true),
          "favorite_add"=>__t("{actor} added %1\$s to favorites.",true),
          "favorite_remove"=>__t("{actor} removed %1\$s from favorites.",true),
          "vote_yes"=>__t("{actor} voted as helpful a review, %1\$s, written by %2\$s.",true), // 1: review title; 2: reviewer
          "vote_no"=>__t("{actor} voted as not helpful a review, %1\$s, written by %2\$s.",true), // 1: review title; 2: reviewer
          "comment_new"=>__t("{actor} commented on a %1\$s for %2\$s.",true),
          "comment_edit"=>__t("{actor} updated comment on a %1\$s for %2\$s.",true),
          "media_photo"=>__t("{actor} added {single}a photo{/single}{multiple}{count} photos{/multiple} for %s",true),
          "media_video"=>__t("{actor} added {single}a video{/single}{multiple}{count} videos{/multiple} for %s",true),
          "media_audio"=>__t("{actor} added {single}a song{/single}{multiple}{count} songs{/multiple} for %s",true),
          "media_attachment"=>__t("{actor} added {single}a document{/single}{multiple}{count} documents{/multiple} for %s",true)
        );

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

                $this->c = & $controller;
            }
        else
            {
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
        $content = $activity_thumb = '';

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

                $review_link = $this->Routes->reviewDiscuss(__t("review",true),$post,array('listing'=>$listing));

                $HtmlHelper = ClassRegistry::getClass('HtmlHelper');

                $listing_link = $HtmlHelper->sefLink($listing['Listing']['title'],$listing['Listing']['url']);

                $activity_thumb = $this->getActivityThumb($listing);

                $thumb_link = ($activity_thumb) ? $HtmlHelper->sefLink($activity_thumb,$listing['Listing']['url']) : '';

                if($model->isNew && $post['Discussion']['modified'] == NULL_DATE)
                {
                    $title = sprintf($this->activities['comment_new'],$review_link, $listing_link);
                }
                 else
                {
                    $title = sprintf($this->activities['comment_edit'],$review_link, $listing_link);
                }

                if($activity_thumb || $listing['Listing']['summary'] != '' )
                {
                    $content = '<div class="cDetailList clrfix">';
                    $thumb_link and $content .=  '<div style="float:left;margin-right:10px;">'.$thumb_link.'</div>';
                    $thumb_link and $content .= '<div class="detailWrap">';
                    $post['Discussion']['text'] != '' and $content .= '<div class="newsfeed-quote">'.$this->Text->truncateWords($post['Discussion']['text'],$this->trim_words).'</div>';
                    $thumb_link and $content .= '</div>';
                    $content .='</div>';
                }

                //begin activity stream
                $options = array('command'=>'jreviews.discussion','likes'=>true,'comments'=>true);

                $this->wallPost($post['User']['user_id'],0,$title,$content,$options);
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
            $content = $activity_thumb = '';

            $listing = $this->_getListing($model);

            $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

            $activity_thumb = $this->getActivityThumb($listing);

            $thumb_link = ($activity_thumb) ? $this->Routes->content($activity_thumb,$listing) : '';

           if($stream == 1 && $this->c->action == '_favoritesDelete') return; // Don't run for removals

           if($this->c->action == '_favoritesDelete')
                {
                    $title = sprintf($this->activities['favorite_remove'],$listing_link);
                }
             else
                {
                    $title = sprintf($this->activities['favorite_add'],$listing_link);
                }

            if($activity_thumb || $listing['Listing']['summary'] != '' )
            {
                $content = '<div class="cDetailList clrfix">';
                $thumb_link and $content .=  '<div style="float:left;margin-right:10px;">'.$thumb_link.'</div>';
                $thumb_link and $content .= '<div class="detailWrap">';
                $listing['Listing']['summary'] != '' and $content .= $this->Text->truncateWords($listing['Listing']['summary'],$this->trim_words);
                $thumb_link and $content .= '</div>';
                $content .='</div>';
            }

            //begin activity stream
            $options = array('command'=>'jreviews.favorite','likes'=>true,'comments'=>true);

            $this->wallPost($this->c->_user->id,0,$title,$content,$options);
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
        $content = $activity_thumb = '';

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
                $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

                $activity_thumb = $this->getActivityThumb($listing);

                $thumb_link = $activity_thumb ? $this->Routes->content($activity_thumb,$listing) : '';

                if($model->isNew && $listing['Listing']['modified'] == NULL_DATE)
                    {
                        $title = sprintf($this->activities['listing_new'],$listing_link,$listing['Category']['title']);
                    }
                 else
                    {
                        $title = sprintf($this->activities['listing_edit'],$listing_link);
                    }

                if($activity_thumb || $listing['Listing']['summary'] != '' )
                {
                    $content = '<div class="cDetailList clrfix">';
                    $thumb_link and $content .=  '<div style="float:left;margin-right:10px;">'.$thumb_link.'</div>';
                    $thumb_link and $content .= '<div class="detailWrap">';
                    $listing['Listing']['summary'] != '' and $content .= $this->Text->truncateWords($listing['Listing']['summary'],$this->trim_words);
                    $thumb_link and $content .= '</div>';
                    $content .='</div>';
                }

                //begin activity stream
                $options = array('command'=>'jreviews.listing','likes'=>true,'comments'=>true);

                $this->wallPost($listing['User']['user_id'],0,$title,$content,$options);
            }
        }

		if($this->points && isset($model->isNew) && $model->isNew && $listing['Listing']['state'] == 1 && !$this->_isPaidListing($listing))
        {
            // Begin add points
            CuserPoints::assignPoint('jreviews.listing.add',$listing['User']['user_id']);
        }
    }

    function _plgMediaAfterSave(&$model)
    {
        $content = $activity_thumb = '';

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

                $title = sprintf($this->activities['media_'.$media_type],$listing_link);

                if(!in_array($media_type,array('attachment','audio')))
                {
                    $mediaThumbs = array();

                    foreach($media AS $m)
                    {
                        $activity_thumb = $this->Media->thumb($m,array(
                            'thumbnailer'=>'api',
                            'size'=>$this->tn_size,
                            'mode'=>$this->tn_mode,
                            'return_thumburl'=>true));

                        $m['ListingType'] = $listing['ListingType'];

                        $media_link = $this->Routes->mediaDetail($activity_thumb, array('media'=>$m, 'listing'=>$listing));

                        $mediaThumbs[] = '<div class="avatarWrap" style="display:inline-block;margin-right:10px;">'.$media_link.'</div>';
                    }

                    $content = '<div class="cDetailList clrfix">';
                    $content .= implode('',$mediaThumbs);
                    $content .= '</div>';
                }

                //begin activity stream
                $options = array('command'=>'jreviews.media','likes'=>true,'comments'=>true);

                $this->wallPost($user_id,0,$title,$content,$options);

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
        $content = $activity_thumb = '';

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
                $listing_link = $this->Html->sefLink($review['Listing']['title'],$review['Listing']['url']);

                $activity_thumb = $this->getActivityThumb($review);

                $thumb_link = ($activity_thumb) ? $this->Html->sefLink($activity_thumb ,$review['Listing']['url']) : '';

                if(isset($model->isNew) && $model->isNew  && $review['Review']['modified'] == NULL_DATE)
                {
                    $title = sprintf($this->activities['review_new'],$listing_link);
                }
                 else
                {
                    $title = sprintf($this->activities['review_edit'],$listing_link);
                }

                if($activity_thumb || $review['Review']['comments'] != '' )
                {
                    $content = '<div class="cDetailList clrfix">';
                    $thumb_link and $content .=  '<div style="float:left;margin-right:10px;">'.$thumb_link.'</div>';
                    $thumb_link and $content .= '<div class="detailWrap">';
                    $review['Review']['comments'] != '' and $content .= '<div class="newsfeed-quote">'.$this->Text->truncateWords($review['Review']['comments'],$this->trim_words).'</div>';
                    $thumb_link and $content .= '</div>';
                    $content .= '</div>';
                }

                //begin activity stream
                $options = array('command'=>'jreviews.review','likes'=>true,'comments'=>true);

                $this->wallPost($review['User']['user_id'],0,$title,$content,$options);
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

            $content = $activity_thumb = '';

            !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

            $ReviewModel = ClassRegistry::getClass('ReviewModel');

            $review_id = $model->data['Vote']['review_id'];

            $review = $ReviewModel->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

            $listing = $this->_getListingEverywhere($review['Review']['listing_id'],$review['Review']['extension']);

			$title = $review['Review']['title'] != '' ? $review['Review']['title'] : $listing['Listing']['title'];

            $review_link = $this->Routes->reviewDiscuss($title,$review,array('listing'=>$listing));

            $target = $review['User']['user_id'] == 0 ? __t("Guest",true) : '{target}';

            $activity_thumb = $this->getActivityThumb($listing);

            $thumb_link = ($activity_thumb) ? $this->Routes->reviewDiscuss($activity_thumb ,$review, array('listing'=>$listing)) : '';

            if($activity_thumb || $review['Review']['comments'] != '' )
            {
                $content = '<div class="cDetailList clrfix">';
                $thumb_link and $content .=  '<div style="float:left;margin-right:10px;">'.$thumb_link.'</div>';
                $thumb_link and $content .= '<div class="detailWrap">';
                $review['Review']['comments'] != '' and $content .= '<div class="newsfeed-quote">'.$this->Text->truncateWords($review['Review']['comments'],$this->trim_words).'</div>';
                $thumb_link and $content .= '</div>';
                $content .= '</div>';
            }

           if($model->data['Vote']['vote_yes'] == 1)
                {
                    $title = sprintf($this->activities['vote_yes'],$review_link,$target);
                }
             else
                {
                    $title = sprintf($this->activities['vote_no'],$review_link,$target);
                }

            //begin activity stream
            $options = array('command'=>'jreviews.vote','likes'=>false,'comments'=>false);

            $this->wallPost($model->data['Vote']['user_id'],$review['User']['user_id'],$title,$content,$options);
        }
    }

    /**
    * $options keys: command, likes, comments
    */
    function wallPost($actor, $target = 0, $title, $content, $options = array())
    {
        CFactory::load('libraries', 'activities');

        if($actor == 0) {

            $title= str_replace('{actor}',__t("A guest",true),$title);
        }

       //begin activity stream
        $act = new stdClass();
        $act->cmd       = 'wall.write';
        $act->title     = $title;
        $act->actor     = $actor;
        $act->target    = $target;
        $act->content   = $content;
        $act->app       = 'wall';
        $act->cid       =  0;

        if(!empty($options))
        {
            $command = Sanitize::getString($options,'command');

            $likes = Sanitize::getBool($options,'likes');

            $comments = Sanitize::getBool($options,'comments');

            # Comments & Likes

            if(defined('CActivities::COMMENT_SELF') && $comments)
            {
                // Enables comments in activity stream
                $act->comment_type  = $command;
                $act->comment_id    = CActivities::COMMENT_SELF;
            }

            if(defined('CActivities::LIKE_SELF') && $likes)
            {
                // Enables likes in activity stream
                $act->like_type     = $command;
                $act->like_id     = CActivities::LIKE_SELF;
            }
        }

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
                'return_thumburl'=>true));

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
