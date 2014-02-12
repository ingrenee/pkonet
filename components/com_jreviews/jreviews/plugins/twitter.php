<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class TwitterComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'twitter';

    var $published = false;

/**
* Plugin configuration
*/
    var $tweet_new_listing = false;
    var $tweet_new_review = false;
    var $tweet_new_discussion = false;



/**
* Bit.ly Short URLs configuration.
* You must open an account and get an API Key
* http://bit.ly/account/register?rd=/ then go to account menu to get the API key.
*/
    var $bitly_user = '';
    var $bitly_key = '';
    var $bitly_history = 0; // Enabling this stores the urls in your Bit.ly account
    var $activities = array();

    function startup(&$controller)
    {
        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        $this->published = Sanitize::getBool($controller->Config,'twitter_enable');
        $this->tweet_new_listing = Sanitize::getBool($controller->Config,'twitter_listings');
        $this->tweet_new_review = Sanitize::getBool($controller->Config,'twitter_reviews');
        $this->tweet_new_discussion = Sanitize::getBool($controller->Config,'twitter_discussions');
        $this->bitly_user = trim(Sanitize::getString($controller->Config,'bitly_user'));
        $this->bitly_key = trim(Sanitize::getString($controller->Config,'bitly_key'));
        $this->bitly_history = Sanitize::getBool($controller->Config,'bitly_history');;
        S2App::import('Helper',array('routes','html'),'jreviews');
        $this->Routes = ClassRegistry::getClass('RoutesHelper');
        $this->Html = ClassRegistry::getClass('HtmlHelper');
        $this->c = & $controller;

        /**
        * Tweets configuration
        * You can customize the strings below for the Twitter messages
        */
        $this->activities = array(
            'listing_new'=>__t("Listing: %1\$s. %2\$s",true), //#1 category title, #2 listing title
            'review_new'=>__t("Review for: %1\$s. %2\$s",true), //#1 listing title, #2 review title
            'comment_new'=>__t("Discussion on: %1\$s. %2\$s",true) //#1 listing title, #2 comment
        );
    }

    function plgAfterSave(&$model)
    {
        if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save'))) {
            return;
        }

        switch($model->name)
        {
            case 'Discussion':
                if(Sanitize::getInt($model->data['Discussion'],'approved') && $this->tweet_new_discussion)
                {
                    $this->_plgDiscussionAfterSave($model);
                }
            break;
            case 'Listing':
                if(Sanitize::getInt($model->data['Listing'],'state') && $this->tweet_new_listing)
                {
                    $this->_plgListingAfterSave($model);
                }
            break;
            case 'Review':
                if(Sanitize::getInt($model->data['Review'],'published') == 1 && $this->tweet_new_review)
                {
                    $this->_plgReviewAfterSave($model);
                }
            break;
        }
    }

    function _plgListingAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $listing = $this->_getListing($model);

        // Treat moderated listings as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        // Limit running this for new listings. Not deletion of images or other listing actions.
        /**
        * Publish activity to Twitter
        */

        if(isset($model->isNew) && $model->isNew && $listing['Listing']['state'] == 1 && $listing['Listing']['modified'] == NULL_DATE)
        {
            $tweet = sprintf(__t($this->activities['listing_new'],true),$listing['Category']['title'],$listing['Listing']['title']);
            $url = $this->Routes->content('',$listing,array('return_url'=>true));
            $url = $this->shortenUrl($url);
            if($tweet!='') $this->sendTweet($this->truncateTweet($tweet,$url));
        }
    }

    function _plgReviewAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $review = $this->_getReview($model);

        // Treat moderated reviews as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        /**
        * Publish activity to Twitter
        */
        $extension = $review['Listing']['extension'];

        if(isset($model->isNew) &&
            $model->isNew && $review['Review']['published'] == 1
            && $review['Review']['modified'] == NULL_DATE
            && ($extension != 'com_content' || ($extension == 'com_content' && $review['Listing']['state'] == 1)))
        {
            $tweet = sprintf(__t($this->activities['review_new'],true),$review['Listing']['title'],$review['Review']['title']);

            $url = $this->Html->sefLink($review['Listing']['title'],$review['Listing']['url'],array('return_url'=>true));

            $url = $this->shortenUrl($url);

            if($tweet!='') $this->sendTweet($this->truncateTweet($tweet,$url));
        }
    }

    function _plgDiscussionAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $post = $this->_getReviewPost($model);

        $listing = $this->_getListingEverywhere($post['Listing']['listing_id'],$post['Listing']['extension']);

        // Treat moderated reviews as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        /**
        * Publish activity to Twitter
        */
        if(isset($model->isNew) && $model->isNew && $post['Discussion']['approved'] == 1)
        {
            $tweet = sprintf(__t($this->activities['comment_new'],true),$listing['Listing']['title'],$post['Discussion']['text']);

            $url = $this->Routes->reviewDiscuss(__t("review",true),$post,array('return_url'=>true));

            $url = $this->shortenUrl($url);

            if($tweet!='') $this->sendTweet($this->truncateTweet($tweet,$url));
        }
    }

    /*
    * Function is not tested yet. May need to update the twitter library used
    */

    function sendMediaTweet($media)
    {
        $twitter_oauth = json_decode($this->c->Config->twitter_oauth,true);

        if(!class_exists('TwitterOAuth'))
        {
            S2App::import('Vendor','twitter' . DS . 'twitteroauth');
        }
        $connection = new TwitterOAuth($twitter_oauth['key'],$twitter_oauth['secret'],$twitter_oauth['token'],$twitter_oauth['tokensecret']);

        $connection->host = 'upload.twitter.com';

        $connection->post('statuses/update_with_media', array(
            'status' => $message,
            'media[]'  => "@{$image};type=image/jpeg;filename={$image}",
        ));

        if ($connection->http_code != 200) {
            // There was an error
            return false;
        }

        return true;
    }

    function sendTweet($message)
    {
        $twitter_oauth = json_decode($this->c->Config->twitter_oauth,true);

        if(!class_exists('TwitterOAuth'))
        {
            S2App::import('Vendor','twitter' . DS . 'twitteroauth');
        }

        $connection = new TwitterOAuth($twitter_oauth['key'],$twitter_oauth['secret'],$twitter_oauth['token'],$twitter_oauth['tokensecret']);

        $connection->post('statuses/update', array('status' => $message));

        if ($connection->http_code != 200) {
            // There was an error
            return false;
        }

        return true;
    }

    function shortenUrl($url)
    {
        $url = cmsFramework::makeAbsUrl($url,array('sef'=>false,'ampreplace'=>true));

        //create the URL
        $version = '2.0.1';
        $format = 'json';
        $bitly = 'http://api.bit.ly/shorten';
        $param = 'version='.$version.'&longUrl='.urlencode($url).'&login='.$this->bitly_user.'&apiKey='.$this->bitly_key.'&format='.$format.'&history='.$this->bitly_history;

        //get the url
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bitly . "?" . $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200) {
            // There was an error
            return false;
        }

        $json = @json_decode($response,true);
        return $json['results'][$url]['shortUrl'];
    }

    function truncateTweet($text,$url)
    {
        $max_chars = 140;
        $truncate = strlen($url)+1; // +1 for space in between text and url
        S2App::import('Helper','text');
        $TextHelper = ClassRegistry::getClass('TextHelper');
        return $TextHelper->truncate($text,$max_chars - $truncate) . ' ' . $url;
    }

    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing']))
        {
            $listing = $this->c->viewVars['listing'];
        }
        else
        {
            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;
            $listing_id = isset($this->c->data['Listing']) ? Sanitize::getInt($this->c->data['Listing'],'id') : false;
            !$listing_id and $listing_id = Sanitize::getInt($this->c->data,'listing_id');

            if(!$listing_id) return false;
            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = '. $listing_id)),array('afterFind' /* Only need menu id */));
            $this->c->set('listing',$listing);
        }

        if(Sanitize::getInt($model->data['Listing'],'state'))
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

                $ListingModel->addRunAfterFindModel(array('Media'));

                $listing = $ListingModel->findRow(array('conditions'=>array('Listing.'.$ListingModel->realKey.' = ' . $listing_id)));

                $this->c->set('listing_'.$extension,$listing);
            }
        }
        return $listing;
    }

    function _getReview(&$model)
    {
        $fields = $joins = array();

        !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

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

            if(!isset($model->joins['Listing']) && isset($this->c->Listing)) {

                $joins = $this->c->Listing->joinsReviews;
            }

             // Triggers the afterFind in the Observer Model
            $this->c->EverywhereAfterFind = true;

            $review = $model->findRow(array(
                'fields'=>$fields,
                'conditions'=>'Review.id = ' . $model->data['Review']['id'],
                'joins'=>$joins
                ), array('plgAfterFind' /* limit callbacks */)
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
