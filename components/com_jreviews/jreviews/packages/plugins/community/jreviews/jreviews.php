<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined('_JEXEC') or die;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once( JPATH_ROOT . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

class plgCommunityJreviews extends CApplications
{
    var $activities = array();

    var $_A_VISITOR = 'A visitor';

    var $JSConfig;

    var $JSVersion;

    protected function prx($var)
    {
        echo '<pre>'.print_r($var,true).'</pre>';
    }

    function plgCommunityJreviews($subject, $config) {

        $this->JSConfig = CFactory::getConfig();

        $xml = JFactory::getXML(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_community' . DS . 'community.xml' );

        $version = (string) $xml->version;

        $version_parts = explode('.', $version);

        $major_version = (int) array_shift($version_parts);

        $this->JSVersion = $major_version;

        $this->activities = array(
          "listing_new"=>       'PLG_JREVIEWS_ACTIVITY_LISTING_NEW',
          "listing_edit"=>      'PLG_JREVIEWS_ACTIVITY_LISTING_EDIT',
          "review_new"=>        'PLG_JREVIEWS_ACTIVITY_REVIEW_NEW',
          "review_edit"=>       'PLG_JREVIEWS_ACTIVITY_REVIEW_EDIT',
          "favorite_add"=>      'PLG_JREVIEWS_ACTIVITY_FAVORITE_ADD',
          "favorite_remove"=>   'PLG_JREVIEWS_ACTIVITY_FAVORITE_REMOVE',
          "vote_yes"=>          'PLG_JREVIEWS_ACTIVITY_VOTE_YES',
          "vote_no"=>           'PLG_JREVIEWS_ACTIVITY_VOTE_NO',
          "vote_yes_guest"=>    'PLG_JREVIEWS_ACTIVITY_VOTE_YES_GUEST',
          "vote_no_guest"=>     'PLG_JREVIEWS_ACTIVITY_VOTE_NO_GUEST',
          "discussion_new"=>    'PLG_JREVIEWS_ACTIVITY_DISCUSSION_NEW',
          "discussion_edit"=>   'PLG_JREVIEWS_ACTIVITY_DISCUSSION_EDIT',
          "media_like_photo_yes"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_YES',
          "media_like_photo_no"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_NO',
          "media_like_photo_yes_guest"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_YES_GUEST',
          "media_like_photo_no_guest"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_NO_GUEST',
          "media_like_video_yes"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_YES',
          "media_like_video_no"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_NO',
          "media_like_video_yes_guest"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_YES_GUEST',
          "media_like_video_no_guest"=>'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_NO_GUEST',
          "media_photo"=>        'PLG_JREVIEWS_ACTIVITY_MEDIA_PHOTO',
          "media_video"=>       'PLG_JREVIEWS_ACTIVITY_MEDIA_VIDEO',
          "media_audio"=>       'PLG_JREVIEWS_ACTIVITY_MEDIA_AUDIO',
          "media_attachment"=>  'PLG_JREVIEWS_ACTIVITY_MEDIA_ATTACHMENT'
        );

        parent::__construct($subject, $config);
    }

    public function onCommunityStreamRender($act)
    {
        if($this->JSVersion < 3) return;

        //Atach stylesheet

        $document   = JFactory::getDocument();

        $css        = JURI::base() . 'plugins/community/jreviews/jreviews/style.css';

        $document->addStyleSheet($css);

        // Load language file

        $test = JPlugin::loadLanguage('plg_community_jreviews', JPATH_ADMINISTRATOR ); // only use if theres any language file

        $app = $act->app;

        preg_match('/(?<activity>jreviews\.[a-z]*)/',$app,$matches);

        switch($matches['activity'])
        {
            case 'jreviews.discussion':

                return $this->discussionActivity($act);

            break;

            case 'jreviews.favorite':

                return $this->favoriteActivity($act);

            break;

            case 'jreviews.listing':

                return $this->listingActivity($act);

            break;

            case 'jreviews.medialike':

                return $this->mediaLikeActivity($act);

            break;

            case 'jreviews.review':

                return $this->reviewActivity($act);

            break;

            case 'jreviews.photo':
            case 'jreviews.video':

                return $this->mediaActivity($act);

            break;

            case 'jreviews.vote':

                return $this->voteActivity($act);

            break;
        }

        $actor = CFactory::getUser($act->actor);

        $actorLink = '<a class="cStream-Author" href="' .CUrlHelper::userLink($actor->id).'">'.$actor->getDisplayName().'</a>';

        if($act->target > 0)
        {
            $target = CFactory::getUser($act->target);

            $targetLink = '<a class="cStream-Author" href="' .CUrlHelper::userLink($target->id).'">'.$target->getDisplayName().'</a>';
        }
        else {

            $targetLink = '';
        }

        $act->title = CString::str_ireplace('{actor}', $actorLink, $act->title);

        $act->title = CString::str_ireplace('{target}', $targetLink, $act->title);

        $stream    = new stdClass();

        $stream->actor  = $actor;

        $stream->headline = 'TESTING*** ' . $act->title;

        $stream->message = $act->content;

        return $stream;
    }

    protected function discussionActivity($act)
    {
        $params = json_decode($act->params,true);

        // Override app to change the favicon

        $act->app = 'jreviews-discussion';

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$params['listing_title'].'</a>';

        $headline = JText::sprintf($this->activities['discussion_'.$params['action']], $actorLink, $listingLink, '<a href="'.$params['review_url'].'">','</a>');

        ob_start();
        ?>
            <div class="cStream-Discussion jrActivity">

                <?php if($params['thumb_src'] != ''):?>

                    <div class="jrActivityPhoto">

                        <a href="<?php echo $params['review_url'];?>" class="cPhoto-Thumb">

                            <img src="<?php echo $params['thumb_src'];?>" />

                        </a>

                    </div>

                <?php endif;?>

                <div>

                    <div class="jrActivityQuote"><?php echo trim(JHTML::_('string.truncate', $act->content , $this->JSConfig->getInt('streamcontentlength'),true, false ));?></div>

                </div>

            </div>
        <?php

        $message = ob_get_clean();

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        $stream->attachments = array();

        return $stream;
    }

   protected function favoriteActivity($act)
    {
        $params = json_decode($act->params,true);

        // Override app to change the favicon

        $act->app = 'jreviews-favorite';

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$act->title.'</a>';

        $headline = JText::sprintf($this->activities['favorite_'.$params['action']], $actorLink, $listingLink);

        ob_start();
        ?>
            <div class="cStream-Listing jrActivity">

                <?php if($params['thumb_src'] != ''):?>

                    <div class="jrActivityPhoto">

                        <a href="<?php echo JRoute::_($params['listing_url']);?>" class="cPhoto-Thumb">

                            <img src="<?php echo $params['thumb_src'];?>" />

                        </a>

                    </div>

                <?php endif;?>

                <div><?php echo JHTML::_('string.truncate', $act->content , $this->JSConfig->getInt('streamcontentlength'),true, false );?></div>

            </div>
        <?php

        $message = ob_get_clean();

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        return $stream;
    }

    protected function listingActivity($act)
    {
        $params = json_decode($act->params,true);

        // Override app to change the favicon

        $act->app = 'jreviews-listing';

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$act->title.'</a>';

        $headline = JText::sprintf($this->activities['listing_'.$params['action']], $actorLink, $listingLink, $params['cat_title']);

        ob_start();
        ?>
            <div class="cStream-Listing jrActivity">

                <?php if($params['thumb_src'] != ''):?>

                    <div class="jrActivityPhoto">

                        <a href="<?php echo JRoute::_($params['listing_url']);?>" class="cPhoto-Thumb">

                            <img src="<?php echo $params['thumb_src'];?>" />

                        </a>

                    </div>

                <?php endif;?>

                <div><?php echo JHTML::_('string.truncate', $act->content , $this->JSConfig->getInt('streamcontentlength'),true, false );?></div>

            </div>
        <?php

        $message = ob_get_clean();

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        return $stream;
    }

    protected function mediaActivity($act)
    {
        $params = json_decode($act->params,true);

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$params['listing_title'].'</a>';

        $media_type = $params['media_type'];

        // Override app to change the favicon

        $act->app = 'jreviews-'.$media_type;

        $media = $params['media'];

        $count = count($media);

        $main_media = array_shift($media);

        $headline = JText::sprintf($this->activities['media_'.$media_type], $actorLink, $listingLink);

        if($count == 1) {

            $headline = preg_replace('/^(.*)({single})(.*)({\/single})({multiple}.*{\/multiple})(.*)$/','$1$3$6',$headline);
        }
        else {

            $headline = preg_replace('/^(.*)({single}.*{\/single})({multiple})({count})(.*)({\/multiple})(.*)$/','$1$4$5$7',$headline);

            $headline = str_replace('{count}',$count,$headline);
        }

        switch($media_type)
        {
            case 'photo':

                $message = $this->renderPhotos($params);

            break;

            case 'video':

                $message = $this->renderVideos($params);

            break;
        }

        $stream->app = $media_type;

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        return $stream;
    }

    protected function mediaLikeActivity($act)
    {
        $params = json_decode($act->params,true);

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $target = CFactory::getUser($act->target);

        $targetLink = self::getProfileLink($target);

        $media_type = $params['media_type'];

        // Override app to change the favicon

        $act->app = 'jreviews-'.$media_type;

        $activity = 'media_like_'.$media_type.'_'.$params['action'] . (!$act->target ? '_guest' : '');

        $headline = JText::sprintf($this->activities[$activity], $actorLink, $targetLink);

        switch($media_type)
        {
            case 'photo':

                $message = $this->renderPhotos($params);

            break;

            case 'video':

                $message = $this->renderVideos($params);

            break;
        }

        $stream->app = $media_type;

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        return $stream;
    }

    protected function reviewActivity($act)
    {
        $params = json_decode($act->params,true);

        // Override app to change the favicon

        $act->app = 'jreviews-review';

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$params['listing_title'].'</a>';

        $rating = $params['average_rating']; // non-rounded average rating for the review

        $rating_type = $params['editor_review'] ? 'Editor' : 'User';

        $scale = $params['scale'];

        $ratingPercent = number_format(($rating/$scale)*100,0);

        $headline = JText::sprintf($this->activities['review_'.$params['action']], $actorLink, $listingLink);

        ob_start();
        ?>
            <div class="cStream-Review jrActivity">

                <?php if($params['thumb_src'] != ''):?>

                    <div class="jrActivityPhoto">

                            <a href="<?php echo JRoute::_($params['listing_url']);?>" class="cPhoto-Thumb">

                                <img src="<?php echo $params['thumb_src'];?>" />

                            </a>

                    </div>

                <?php endif;?>

                <div>

                    <?php if($act->title != ''):?>

                        <b class="cReview-Title"><a href="<?php echo JRoute::_($params['listing_url']);?>"><?php echo $act->title;?></a></b>

                    <?php endif;?>

                    <?php if($rating > 0):?>

                    <div class="jrRatingStars<?php echo $rating_type;?>"><div style="width:<?php echo $ratingPercent;?>%">&nbsp;</div></div>

                    <?php endif;?>

                    <div class="jrActivityQuote">

                        <?php echo trim(JHTML::_('string.truncate', $act->content , $this->JSConfig->getInt('streamcontentlength'),true, false ));?>

                    </div>

                </div>

            </div>
        <?php

        $message = ob_get_clean();

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        $stream->attachments = array();

        return $stream;
    }

    protected function voteActivity($act)
    {
        $params = json_decode($act->params,true);

        // Override app to change the favicon

        $act->app = 'jreviews-review';

        $stream = new stdClass();

        $actor = CFactory::getUser($act->actor);

        $actorLink = self::getProfileLink($actor);

        $target = CFactory::getUser($act->target);

        $targetLink = self::getProfileLink($target);

        $listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$params['listing_title'].'</a>';

        $activity = 'vote_'.$params['action'] . (!$act->target ? '_guest' : '');

        $headline = JText::sprintf($this->activities[$activity], $actorLink, $listingLink, $targetLink);

        ob_start();
        ?>
            <div class="cStream-Review jrActivity">

                <?php if($params['thumb_src'] != ''):?>

                    <div class="jrActivityPhoto">

                        <a href="<?php echo $params['review_url'];?>" class="cPhoto-Thumb">

                            <img src="<?php echo $params['thumb_src'];?>" />

                        </a>

                    </div>

                <?php endif;?>

                <div>

                    <?php if($act->title != ''):?>

                        <b class="cReview-Title"><a href="<?php echo $params['review_url'];?>"><?php echo $act->title;?></a></b>

                    <?php endif;?>

                    <div class="jrActivityQuote"><?php echo trim(JHTML::_('string.truncate', $act->content , $this->JSConfig->getInt('streamcontentlength'),true, false ));?></div>

                </div>

            </div>
        <?php

        $message = ob_get_clean();

        $stream->actor  = $actor;

        $stream->headline = $headline;

        $stream->message = $message;

        $stream->attachments = array();

        return $stream;
    }

    protected function getProfileLink($user)
    {
        $id = $user->_userid;

        if(!$id) return $this->_A_VISITOR;

        $profileLink = '<a class="cStream-Author" href="' .CUrlHelper::userLink($id).'">'.$user->getDisplayName().'</a>';

        return $profileLink;
    }

    protected function renderPhotos($params)
    {
        $photos = $params['media'];

        $main = array_shift($photos);

        ob_start();
        ?>
        <div class="cStream-Attachment jrActivity">

            <div class="js-stream-photos bottom-gap">

                <div class="row-fluid">

                    <div class="span12">

                        <a href="<?php echo $main['media_url'];?>" class="cPhoto-Thumb">

                            <img alt="<?php echo ($main['title'] != '' ? $main['title'] : $params['listing_title']);?>" src="<?php echo $main['orig_src'];?>">

                        </a>

                    </div>

                </div>

                <?php if(!empty($photos)):?>

                    <?php while($rows = array_splice($photos, 0, 4)):?>

                        <div class="row-fluid">

                        <?php foreach($rows AS $photo):?>

                            <div class="span3 top-gap">

                                <a href="<?php echo $photo['media_url'];?>" class="cPhoto-Thumb">

                                    <img alt="<?php echo ($photo['title'] != '' ? $photo['title'] : $params['listing_title']);?>" src="<?php echo $photo['thumb_src'];?>" />

                                </a>

                            </div>

                        <?php endforeach;?>

                        </div>

                    <?php endwhile;?>

                <?php endif;?>

            </div>

        </div>

        <?php

        $output = ob_get_clean();

        return $output;
    }

    protected function renderVideos($params)
    {
        $videos = $params['media'];

        ob_start();
        ?>
        <div class="cStream-Attachment jrActivity">

            <?php foreach($videos AS $video):?>

                <div class="cStream-Video">

                    <div class="jrActivityPhoto">

                        <a href="<?php echo $video['media_url'];?>" class="cVideo-Thumb">

                            <img alt="<?php echo CStringHelper::escape($video['title'] != '' ? $video['title'] : $params['listing_title']);?>" src="<?php echo $video['thumb_src'];?>" />

                            <b><?php echo CVideosHelper::toNiceHMS(CVideosHelper::formatDuration($video['duration']));?></b>

                        </a>

                    </div>

                    <div>

                        <?php if($video['title'] != ''):?>

                            <b class="cVideo-Title"><a href="<?php echo $video['media_url'];?>"><?php echo $video['title'];?></a></b>

                        <?php endif;?>

                        <div><?php echo JHTML::_('string.truncate', $video['description'], $this->JSConfig->getInt('streamcontentlength'), true, false);?></div>

                    </div>

                </div>

            <?php endforeach;?>

        </div>

        <?php

        $output = ob_get_clean();

        return $output;
    }
}
