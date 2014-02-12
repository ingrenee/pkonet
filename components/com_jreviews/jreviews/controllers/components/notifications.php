<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class NotificationsComponent extends S2Component {

    var $name = 'notifications';

    var $notifyModel = null;

    var $published = true;

    var $validObserverModels = array(
        'Listing','Review','Report','OwnerReply','Discussion','Claim','Media'
        );

    function startup(&$controller)
    {
        $this->c = & $controller;

        if(method_exists($this->c,'getNotifyModel'))
        {
            $this->notifyModel = $controller->getNotifyModel();

            if(method_exists($this->c,'getNotifyModel')
                && in_array($this->notifyModel->name,$this->validObserverModels))
            {

                $this->notifyModel->addObserver('plgAfterSave',$this);
            }
        }
    }

    function plgAfterSave(&$model)
    {
        appLogMessage('**** BEGIN Notifications Plugin AfterSave', 'database');

        $mail = cmsFramework::getMail();

        $isNew = isset($model->data['insertid']);

        # In this observer model we just use the existing data to send the email notification
        switch($this->notifyModel->name)
        {
            # Notification for video encoding
            case 'Media':

                $media_id = Sanitize::getInt($model->data['Media'],'media_id');

                if($this->c->MediaEncoding->alreadyNotified($media_id)
                    || $model->data['Media']['published'] != 1
                    || !$this->c->Config->notify_user_media_encoding) {

                    return;
                }

                $this->c->autoRender = false;

                $media = $this->_getMedia($model);

                if(($this->c->_user->id == $media['User']['user_id'] || Sanitize::getBool($this->c->params,'remote') == 1) && $media['User']['user_id'] > 0 && $media['User']['email'] != '')
                {
                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'media'=>$media
                    ));

                    $this->_clearAddresses($mail);

                    # Process configuration emails
                    $this->_AddBCC($mail, 'notify_user_media_encoding_emails');

                    trim($media['User']['email']) != '' and $mail->AddAddress(trim($media['User']['email']));

                    $subject = JreviewsLocale::getPHP('NOTIFY_UPLOAD_PROCESSING_DONE');

                    $message = $this->c->render('email_templates','user_media_encoding_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                            "User media encoding message was not sent.",
                            "Mailer error: " . $mail->ErrorInfo),
                            'notifications'
                        );
                    }
                }

            break;

            # Notification for new/edited listings
            case 'Listing':

                if ($this->c->Config->notify_content
                    || $this->c->Config->notify_user_listing
                )
                {
                    $this->c->autoRender = false;

                    $listing = $this->_getListing($model);

                    $this->c->set(array(
                        'isNew'=>$isNew,
                        'User'=>$this->c->_user,
                        'listing'=>$listing
                    ));
                }
                else
                {
                    return;
                }

                // Admin listing email
                if ($this->c->Config->notify_content)
                {
                    $this->_clearAddresses($mail);

                    # Process configuration emails
                    if($this->c->Config->notify_content_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_content_emails);

                        foreach($recipient AS $to)
                        {
                            trim($to)!='' and $mail->AddAddress(trim($to));
                        }
                    }

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_LISTING'),$listing['Listing']['title']) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_LISTING'),$listing['Listing']['title']);

                    $guest = (!$this->c->_user->id ? ' (Guest)' : " ({$this->c->_user->id})");
                    $author = ($this->c->_user->id ? $this->c->_user->name : 'Guest');

                    $message = $this->c->render('email_templates','admin_listing_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                            "Admin listing message was not sent.",
                            "Mailer error: " . $mail->ErrorInfo),
                            'notifications'
                        );
                    }
                } // End admin listing email

                // User listing email - to user submitting the listing as long as he is also the owner of the listing
                if ($this->c->Config->notify_user_listing)
                {
                    $this->_clearAddresses($mail);

                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->c->_user->id == $listing['User']['user_id'])
                    {
                        if($listing['User']['user_id'] == 0 && isset($model->data['Field'])) {

                            $listing['User']['email'] = Sanitize::getString($model->data['Field']['Listing'],'email');
                        }

                       // Process configuration emails
                        $this->_AddBCC($mail, 'notify_user_listing_emails');

                        trim($listing['User']['email']) != '' and $mail->AddAddress(trim($listing['User']['email']));

                        $subject = $isNew ?
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_LISTING'),$listing['Listing']['title']) :
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_LISTING'),$listing['Listing']['title']);

                        $guest = (!$this->c->_user->id ? ' (Guest)' : " ({$this->c->_user->id})");
                        $author = ($this->c->_user->id ? $this->c->_user->name : 'Guest');

                        $message = $this->c->render('email_templates','user_listing_notification');

                        $mail->Subject = $subject;

                        $mail->Body = $message;
                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "User listing message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                               );
                        }
                    }
                } // End user listing email
                break;

            # Notification for new/edited reviews
            case 'Review':
                // Perform common actions for all review notifications
                if($this->c->Config->notify_review
                    ||
                    $this->c->Config->notify_user_review
                    ||
                    $this->c->Config->notify_owner_review
                ) {
                    $extension = $model->data['Review']['mode'];

                    $review = $this->_getReview($model);

                    $listing = $review;

                    $entry_title = $listing['Listing']['title'];

                    $this->c->autoRender = false;

                    $this->c->set(array(
                        'isNew'=>$isNew,
                        'extension'=>$extension,
                        'listing'=>$listing,
                        'User'=>$this->c->_user,
                        'review'=>$review
                    ));
                }
                else
                {
                    return;
                }

                // Admin review email
                if ($this->c->Config->notify_review)
                {
                    $this->_clearAddresses($mail);

                    # Process configuration emails
                    if($this->c->Config->notify_review_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_review_emails);
                        foreach($recipient AS $to)
                        {
                            trim($to)!='' and $mail->AddAddress(trim($to));
                        }
                    }

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW'),$entry_title) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW'),$entry_title);

                    $message = $this->c->render('email_templates','admin_review_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                            "Admin review message was not sent.",
                            "Mailer error: " . $mail->ErrorInfo),
                            'notifications'
                        );
                    }
                }

                // User review email - sent to review submitter
                if(
                    $this->c->Config->notify_user_review
                    &&
                    $this->c->_user->id == $review['User']['user_id']
                    &&
                    !empty($review['User']['email'])
                ) {
                    $this->_clearAddresses($mail);

                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->c->_user->id == $review['User']['user_id'])
                    {
                        // Process configuration emails
                        $this->_AddBCC($mail, 'notify_user_review_emails');

                        trim($review['User']['email']) != '' and $mail->AddAddress(trim($review['User']['email']));

                        $subject = $isNew ?
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW'),$entry_title) :
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW'),$entry_title);

                        $message = $this->c->render('email_templates','user_review_notification');

                        $mail->Subject = $subject;

                        $mail->Body = $message;

                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "User review message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                               );
                        }
                    }
                }

                // Listing owner review email
                $claimed_listing_check = ($this->c->Config->notify_owner_review_claimed &&
                                    isset($listing['Claim']) &&
                                    Sanitize::getBool($listing['Claim'],'approved')
                                    ) ||
                                    !$this->c->Config->notify_owner_review_claimed;

                if (
                    $this->c->Config->notify_owner_review
                    &&
                    isset($listing['ListingUser']['email'])
                    &&
                    !empty($listing['ListingUser']['email'])
                    &&
                    $claimed_listing_check
                )
                {
                    $this->_clearAddresses($mail);

                    // Process configuration emails
                    $this->_AddBCC($mail, 'notify_owner_review_emails');

                    trim($listing['ListingUser']['email']) != '' and $mail->AddAddress(trim($listing['ListingUser']['email']));

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW'),$entry_title) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW'),$entry_title);

                    $message = $this->c->render('email_templates','owner_review_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Listing owner review message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }
                break;

               # Notification for new owner replies to user reviews
            case 'OwnerReply':

                if ( $this->c->Config->notify_owner_reply ) {

                    # Process configuration emails
                    if($this->c->Config->notify_owner_reply_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_owner_reply_emails);

                        foreach($recipient AS $to)
                        {
                            trim($to) != '' and $mail->AddAddress(trim($to));
                        }
                    }

                     # Get review data
                    $this->c->Review->runProcessRatings = false;
                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['OwnerReply']['id'])
                    ));

                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    S2App::import('Model',$name,'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();

                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));

                    $subject = sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_OWNER_REPLY'), $listing['Listing']['title']);

                    $this->c->autoRender = false;

                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'reply'=>$model->data,
                        'review'=>$review,
                        'listing'=>$listing
                    ));

                    $message = $this->c->render('email_templates','admin_owner_reply_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Owner reply message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }
                break;

            # Notification for new review reports
            case 'Report':

                if ( $this->c->Config->notify_report ) {

                    # Process configuration emails
                    if($this->c->Config->notify_review_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_review_emails);

                        foreach($recipient AS $to)
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
                        }
                    }

                    # Get review data
                    $this->c->Review->runProcessRatings = false;

                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['Report']['review_id'])
                    ),array());

                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    S2App::import('Model',$name,'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();

                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));

                    $subject = JreviewsLocale::getPHP('NOTIFY_NEW_REPORT');

                    $this->c->autoRender = false;

                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'report'=>$model->data,
                        'review'=>$review,
                        'listing'=>$listing
                    ));

                    $message = $this->c->render('email_templates','admin_report_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                            "Review report message was not sent.",
                            "Mailer error: " . $mail->ErrorInfo),
                            'notifications'
                        );
                    }
                }
                break;

            # Notification for review comments
            case 'Discussion':

                // Perform common actions for all review notifications
                if(Sanitize::getBool($this->c->Config,'notify_comment') // admin
                    ||
                    Sanitize::getBool($this->c->Config,'notify_user_comment') // to submitted
                    ||
                    Sanitize::getBool($this->c->Config,'notify_reviewer_comment') // to reviewer
                    ||
                    Sanitize::getBool($this->c->Config,'notify_owner_comment') // to listing owner
                ) {
                    $this->c->Review->runProcessRatings = false;

                    $post = $this->_getReviewPost($model);

                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['Discussion']['review_id'])
                    ));

                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model

                    $name =  'everywhere_' . $extension;

                    S2App::import('Model',$name,'jreviews');

                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';

                    $EverywhereListingModel = new $class_name();

                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));

                    $entry_title = $listing['Listing']['title']
                                    . ($review['Review']['title'] != '' ? ' (' . $review['Review']['title'] . ')' : '');

                    $this->c->autoRender = false;

                    $this->c->set(array(
                        'isNew'=>$isNew,
                        'User'=>$this->c->_user,
                        'post'=>$post,
                        'review'=>$review,
                        'listing'=>$listing
                    ));
                }
                else
                {
                    return;
                }

                // Admin email
                if(Sanitize::getBool($this->c->Config,'notify_comment'))
                {
                    $this->_clearAddresses($mail);

                    # Process configuration emails
                    if($this->c->Config->notify_comment_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_comment_emails);

                        foreach($recipient AS $to)
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
                        }
                    }

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW_COMMENT'), $entry_title) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW_COMMENT'), $entry_title)
                    ;

                    $message = $this->c->render('email_templates','admin_review_discussion_post');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Review comment message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }

                // User commment email - sent to comment submitter
                if(
                    Sanitize::getBool($this->c->Config,'notify_user_comment')
                    &&
                    $this->c->_user->id == $post['User']['user_id']
                    &&
                    !empty($post['User']['email'])
                ) {
                    $this->_clearAddresses($mail);

                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->c->_user->id == $post['User']['user_id'])
                    {
                        // Process configuration emails
                        $this->_AddBCC($mail, 'notify_user_comment_emails');

                        trim($post['User']['email']) != '' and $mail->AddAddress(trim($post['User']['email']));

                        $subject = $isNew ?
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW_COMMENT'),$entry_title) :
                                    sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW_COMMENT'),$entry_title);

                        $message = $this->c->render('email_templates','user_comment_notification');

                        $mail->Subject = $subject;

                        $mail->Body = $message;

                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "User comment message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                               );
                        }
                    }
                }

                // User commment email - sent to reviewer
                if(
                    $this->c->Config->notify_reviewer_comment
                    &&
                    isset($review['User']['email'])
                    &&
                    !empty($review['User']['email'])
                ) {
                    $this->_clearAddresses($mail);

                    // Process configuration emails
                    $this->_AddBCC($mail, 'notify_reviewer_comment_emails');

                    trim($review['User']['email']) != '' and $mail->AddAddress(trim($review['User']['email']));

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW_COMMENT'),$entry_title) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW_COMMENT'),$entry_title);

                    $message = $this->c->render('email_templates','reviewer_comment_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Reviewer comment message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }

                // User commment email - sent to Listing Owner

                $claimed_listing_check = (Sanitize::getBool($this->c->Config,'notify_owner_comment') &&
                                        isset($listing['Claim']) &&
                                        Sanitize::getBool($listing['Claim'],'approved')
                                        )
                                        || !Sanitize::getBool($this->c->Config,'notify_owner_comment_claimed');

                $userArray = isset($listing['ListingUser']) ? $listing['ListingUser'] : $listing['User'];

                if(
                    Sanitize::getBool($this->c->Config,'notify_owner_comment')
                    &&
                    isset($userArray['email'])
                    &&
                    !empty($userArray['email'])
                    &&
                    $claimed_listing_check
                ) {
                    $this->_clearAddresses($mail);

                    // Process configuration emails
                    $this->_AddBCC($mail, 'notify_owner_comment_emails');

                    trim($userArray['email']) != '' and $mail->AddAddress(trim($userArray['email']));

                    $subject = $isNew ?
                                sprintf(JreviewsLocale::getPHP('NOTIFY_NEW_REVIEW_COMMENT'),$entry_title) :
                                sprintf(JreviewsLocale::getPHP('NOTIFY_EDITED_REVIEW_COMMENT'),$entry_title);

                    $message = $this->c->render('email_templates','owner_comment_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Owner comment message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }

                break;

            case 'Claim':

                if ($this->c->Config->notify_claim ) {

                    # Process configuration emails
                    if($this->c->Config->notify_claim_emails == '')
                    {
                        $mail->AddAddress($configMailFrom);
                    }
                    else
                    {
                        $recipient = explode("\n",$this->c->Config->notify_claim_emails);

                        foreach($recipient AS $to)
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
                        }
                    }

                     # Get claim data
                    $callbacks = array();

                    $listing = $this->c->Listing->findRow(
                        array(
                            'conditions'=>array('Listing.id = ' . (int) $model->data['Claim']['listing_id'])
                        ),
                        $callbacks
                    );

                    $subject = sprintf(JreviewsLocale::getPHP('NOTIFY_LISTING_CLAIM'), $listing['Listing']['title']);

                    $this->c->autoRender = false;

                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'claim'=>$model->data['Claim'],
                        'listing'=>$listing
                    ));

                    $message = $this->c->render('email_templates','admin_listing_claim');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Listing claim message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }
                break;
        }

        $this->published = false; // Run once. With paid listings it is possible for a plugin to run a 2nd time when the order is processed together with the listing (free)

        return true;
    }

    function _clearAddresses(&$mail)
    {
        $mail->ClearAddresses();

        $mail->ClearAllRecipients();

        $mail->ClearBCCs();
    }

    function _AddBCC(&$mail, $setting)
    {

        $emails = Sanitize::getString($this->c->Config,$setting);

        if($emails != '') {

            $recipient = explode("\n",$emails);

            foreach($recipient AS $bcc)
            {
                if(trim($bcc)!='')
                {
                    $mail->AddBCC(trim($bcc));
                }
            }
        }
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

    function _getMedia(&$model)
    {
        if(isset($this->c->viewVars['media']))
        {
            $media = $this->c->viewVars['media'];
        }
        else
        {
            $media_id = isset($model->data['Media']) ? Sanitize::getInt($model->data['Media'],'media_id') : false;
            !$media_id and $media_id = Sanitize::getInt($this->c->data,'media_id');
            if(!$media_id) return false;
            $media = $this->c->Media->findRow(array('conditions'=>array('Media.media_id = '. $media_id)),array());
            $this->c->set('media',$media);
        }

        if(Sanitize::getInt($model->data['Media'],'published'))
        {
            $media['Media']['published'] =  $model->data['Media']['published'];
        }

        return $media;
    }


    function _getReview(&$model)
    {
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
            // Get updated review info for non-moderated actions and plugin callback
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'
            );

            $joins = $this->c->Listing->joinsReviews;

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
