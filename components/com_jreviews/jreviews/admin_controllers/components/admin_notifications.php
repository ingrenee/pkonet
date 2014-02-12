<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminNotificationsComponent extends S2Component {

	var $notifyModel = null;

    function startup(&$controller) {

        $this->controller = & $controller;

        if(method_exists($this->controller,'getNotifyModel'))
        {
            $this->notifyModel = $controller->getNotifyModel();
        	$this->notifyModel->addObserver('plgAfterSave',$this);
        }
    }

    function plgAfterSave(&$model)
    {
        if(!isset($model->data['Email']) || !Sanitize::getInt($model->data['Email'],'send')) {return false;}

        $mail = cmsFramework::getMail();

        $model->data['Email']['body'] = urldecode($model->data['__raw']['Email']['body']); // Send html email

		# In this observer model we just use the existing data to send the email notification
		switch($this->notifyModel->name)
		{
            // Notification for claims moderation
            case 'Claim':

                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);
                    $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject);
                }
                else
                {
                    switch($model->data['Claim']['approved'])
                    {
                        case 1:
                            $subject = JreviewsLocale::getPHP('CLAIM_APPROVED');
                        break;
                        case -1:
                            $subject = JreviewsLocale::getPHP('CLAIM_REJECTED');
                        break;
                        case 0:
                            $subject = JreviewsLocale::getPHP('CLAIM_HELD');
                        break;
                    }
                }

                // Get permalink
                $listing_id = $model->data['Listing']['id'];
                $listing = $this->controller->Listing->findRow(array(
                    'conditions'=>'Listing.id = ' . $listing_id
                ),array('afterFind'));

                $permalink = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

                $message = $model->data['Email']['body'];
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);
                $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message);

				$this->SendNotification($mail, $model->data, $subject, $message);

            break;

			# Notification for discussion post moderation
			case 'Discussion':

                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);
                    $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);
                }
                else
                {
                    switch($model->data['Discussion']['approved'])
                    {
                        case 1:
                            $subject = JreviewsLocale::getPHP('COMMENT_APPROVED');
                        break;
                        case -1:
                            $subject = JreviewsLocale::getPHP('COMMENT_REJECTED');
                        break;
                    }
                }

                // Get permalink
                $this->controller->EverywhereAfterFind = true;
                $this->controller->Review->runProcessRatings = false;

                $review = $this->controller->Review->findRow(array(
                    'conditions'=>array('Review.id = ' . $model->data['Discussion']['review_id'])
                ));

                $this->controller->viewVars['review'] = $review; // Make it available to other plugins

                S2App::import('helper','routes','jreviews');
                $Routes = ClassRegistry::getClass('RoutesHelper');

                $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));

                $permalink = cmsFramework::makeAbsUrl($permalink);

				$message = $model->data['Email']['body'];
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message);
                $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message);

				$this->SendNotification($mail, $model->data, $subject, $message);

    	    break;

            // Notification for media moderation
            case 'Media':

                if(Sanitize::getInt($model->data,'moderation'))
                {
                    if($model->data['Email']['subject']!=''){

                        $subject = $model->data['Email']['subject'];

						$subject = str_ireplace(
							array(
								'{name}',
								'{listing_title}',
								'{review_title}',
								'{media_title}'),
							array(
								$model->data['Email']['name'],
								$model->data['Email']['listing_title'],
								$model->data['Email']['review_title'],
								$model->data['Email']['media_title']
							),
							$subject
						);
                    }
                    else
                    {
                        switch($model->data['Media']['approved'])
                        {
                            case 1:
                                $subject = JreviewsLocale::getPHP('MEDIA_APPROVED');
                            break;
                            case -1:
                                $subject = JreviewsLocale::getPHP('MEDIA_REJECTED');
                            break;
                            case 0:
                                $subject = JreviewsLocale::getPHP('MEDIA_HELD');
                            break;
                        }
                    }

                    // Get permalink
					$media_id = $model->data['Media']['media_id'];

					$this->controller->EverywhereAfterFind = true;

                    $media = $this->controller->Media->findRow(array(
                        'conditions'=>array('Media.media_id = ' . $media_id)
                    ));

                    $this->controller->viewVars['media'] = $media; // Make it available to other plugins

                    S2App::import('helper','routes','jreviews');

                    $Routes = ClassRegistry::getClass('RoutesHelper');

					$Routes->Config = $this->controller->Config;

					$Routes->Access = $this->controller->Access;

					$permalink = $Routes->mediaDetail('',array('listing'=>$media,'media'=>$media),array('return_url'=>true));

                    $permalink = cmsFramework::makeAbsUrl($permalink);

                    $message = $model->data['Email']['body'];

					$message = str_ireplace(
						array(
							'{name}',
							'{link}',
							'{url}',
							'{listing_title}',
							'{review_title}',
							'{media_title}'
						),
						array(
							$model->data['Email']['name'],
							$permalink,
							$permalink,
							$model->data['Email']['listing_title'],
							$model->data['Email']['review_title'],
							$model->data['Email']['media_title']
						),
						$message
					);

					$this->SendNotification($mail, $model->data, $subject, $message);
                }
            break;

            // Notification for listing moderation
            case 'Listing':

                if(Sanitize::getInt($model->data,'moderation'))
                {
                    if($model->data['Email']['subject']!='')
                    {
                        $subject = $model->data['Email']['subject'];
                        $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);
                        $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject);
                    }
                    else
                    {
                        switch($model->data['Listing']['state'])
                        {
                            case 1:
                                $subject = JreviewsLocale::getPHP('LISTING_APPROVED');
                            break;
                            case -2:
                                $subject = JreviewsLocale::getPHP('LISTING_REJECTED');
                            break;
                            case 0:
                                $subject = JreviewsLocale::getPHP('LISTING_HELD');
                            break;
                        }
                    }

                    // Get permalink
                    $listing_id = $model->data['Listing']['id'];
                    $listing = $this->controller->Listing->findRow(array(
                        'conditions'=>'Listing.id = ' . $listing_id
                    ),array('afterFind'));

                    $permalink = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

                    $message = $model->data['Email']['body'];
                    $message = str_ireplace('{name}',$model->data['Email']['name'],$message);
                    $message = str_ireplace(array('{link}','{url}'),$permalink,$message);
                    $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);

					$this->SendNotification($mail, $model->data, $subject, $message);

                }
            break;

            // Notification for reviews moderation
            case 'Review':

                if(Sanitize::getInt($model->data,'moderation'))
                {
                    if($model->data['Email']['subject']!=''){
                        $subject = $model->data['Email']['subject'];
                        $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);
                        $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject);
                        $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);
                    }
                    else
                    {
                        switch($model->data['Review']['published'])
                        {
                            case 1:
                                $subject = JreviewsLocale::getPHP('REVIEW_APPROVED');
                            break;
                            case -1:
                                $subject = JreviewsLocale::getPHP('REVIEW_REJECTED');
                            break;
                            case 0:
                                $subject = JreviewsLocale::getPHP('REVIEW_HELD');
                            break;
                        }
                    }

                    // Get permalink
                    $this->controller->EverywhereAfterFind = true;
                    $this->controller->Review->runProcessRatings = false;

                    $review_id = $model->data['Review']['id'];
                    $review = $this->controller->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . $review_id)
                    ));

                    $this->controller->viewVars['review'] = $review; // Make it available to other plugins

                    S2App::import('helper','routes','jreviews');
                    $Routes = ClassRegistry::getClass('RoutesHelper');
                    $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));
                    $permalink = cmsFramework::makeAbsUrl($permalink);

                    $message = $model->data['Email']['body'];
                    $message = str_ireplace('{name}',$model->data['Email']['name'],$message);
                    $message = str_ireplace(array('{link}','{url}'),$permalink,$message);
                    $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);
                    $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message);

					$this->SendNotification($mail, $model->data, $subject, $message);

                }
            break;

            // Notification for owner reply to reviews moderation
            case 'OwnerReply':

                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);
                    $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject);
                    $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);
                }
                else
                {
                    switch($model->data['OwnerReply']['owner_reply_approved'])
                    {
                        case 1:
                            $subject = JreviewsLocale::getPHP('OWNER_REPLY_APPROVED');
                        break;
                        case -1:
                            $subject = JreviewsLocale::getPHP('OWNER_REPLY_REJECTED');
                        break;
                        case 0:
                            $subject = JreviewsLocale::getPHP('OWNER_REPLY_HELD');
                        break;
                    }
                }

                // Get permalink
                $this->controller->EverywhereAfterFind = true;
                $this->controller->Review->runProcessRatings = false;

                $review_id = $model->data['OwnerReply']['id'];

               $review = $this->controller->Review->findRow(array(
                    'conditions'=>array('Review.id = ' . $review_id)
                ));

                $this->controller->viewVars['review'] = $review; // Make it available to other plugins

                S2App::import('helper','routes','jreviews');
                $Routes = ClassRegistry::getClass('RoutesHelper');

                $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));
                $permalink = cmsFramework::makeAbsUrl($permalink);

                $message = $model->data['Email']['body'];
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message);
                $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);
                $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message);

				$this->SendNotification($mail, $model->data, $subject, $message);

            break;
        }

        unset($mail);
    	return true;
    }

	function sendNotification($mail, $data, $subject, $message)
	{
		if($message != '')
		{
			$mail->Subject = $subject;

			// Convert line breaks to br tags if html code not found on the message body
			$mail->Body = nl2br($message);
			$mail->AddAddress($data['Email']['email']);
			$bcc = trim(Sanitize::getString($data['Email'],'bcc'));

			if($bcc != '') {
				$mail->AddBCC($bcc);
			}

			if(!$mail->Send())
			{
			   appLogMessage(array(
					   "Admin moderation message was not sent.",
					   "Mailer error: " . $mail->ErrorInfo),
					   'notifications'
				);
			}
		}
	}
}