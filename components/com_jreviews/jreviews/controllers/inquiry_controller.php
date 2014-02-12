<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InquiryController extends MyController {

    var $uses = array('menu','inquiry','captcha','criteria'/*for config overrides*/);

    var $helpers = array('form');

    var $components = array('access','config','everywhere');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter(){
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function create()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $listing = array();

        $listing_id = Sanitize::getInt($this->params, 'id');

        $listing['Listing']['listing_id'] = $listing_id;

        $User = cmsFramework::getUser();

        $this->set(array(
            'User'=>$User,
            'listing'=>$listing
            ));

        return $this->render('inquiries','create');
    }

    function _send()
    {
        $recipient = '';

        $response = array('success'=>false,'str'=>array());

        $validation = array();

        $this->components = array('security');

        $this->__initComponents();

        if($this->invalidToken){

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

		$listing_id = Sanitize::getInt($this->data['Inquiry'],'listing_id');

        $from_email = Sanitize::getString($this->data['Inquiry'],'from_email');

        $from_name = Sanitize::getString($this->data['Inquiry'],'from_name');

        $message = Sanitize::getString($this->data['Inquiry'],'message');

        $message = nl2br($message);

		$overrides = $this->Criteria->getListingTypeOverridesByListingId($listing_id);

        if(!$listing_id
            // Commented to allow inquiry form embed in detail pages
            /* || !$this->Config->getOverride('inquiry_enable',$overrides) */ )
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
		}

		// Required fields
        $inputs  = array('from_name','from_email','message');

//        $inputs = array('from_name','from_email','phone','message');

        foreach($inputs AS $key=>$input)
        {
            if($this->data['Inquiry'][$input] != '')
            {
                unset($inputs[$key]);
            }
        }

        # Validate user's email
        if(!in_array('from_email',$inputs)) {
            $this->Listing->validateInput($from_email, "from_email", "email", 'VALIDATE_EMAIL', 1);
        }

        # Validate security code
        if ($this->Access->showCaptcha())
        {
            $captcha = Sanitize::getString($this->data['Captcha'],'code');

            if($captcha == '') {

                $this->Listing->validateSetError("code", 'VALID_CAPTCHA');
            }
            elseif (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) {

                $this->Listing->validateSetError("code", 'VALID_CAPTCHA_INVALID');
            }
         }

        # Process validation errors
        $validation = $this->Listing->validateGetErrorArray();

        if(!empty($validation) || !empty($inputs))
        {
            if($this->Access->showCaptcha())
            {
                // Replace captcha with new instance
                $captcha = $this->Captcha->displayCode();

                $response['captcha'] = $captcha['src'];
            }

            $response['success'] = false;

            $response['inputs'] = $inputs;

            $response['str'] = $validation;

            return cmsFramework::jsonResponse($response);
        }

        Configure::write('Cache.query',false);

        $this->Listing->addStopAfterFindModel(array('Favorite','Media','PaidOrder'));

        $listing = $this->Listing->findRow(array(
            'fields'=>array('User.email AS `Listing.email`'),
            'conditions'=>array('Listing.id = ' . (int)$this->data['Inquiry']['listing_id'])
        ));

        $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

        $link = '<a href="'.$url.'">'.$listing['Listing']['title'].'</a>';

        switch($this->Config->inquiry_recipient)
        {
            case 'owner':
                $recipient = Sanitize::getString($listing['Listing'],'email');
            break;
            case 'field':
                if(isset($listing['Field']['pairs'][$this->Config->inquiry_field]))
                {
                    $recipient = $listing['Field']['pairs'][$this->Config->inquiry_field]['value'][0];
                }
            break;
            case 'admin':
            default:
                $recipient = cmsFramework::getConfig('mailfrom');;
            break;
        }

        $to_email = $recipient;

        $mail = cmsFramework::getMail();

        $mail->ClearReplyTos();

        $mail->AddReplyTo(array($from_email, $from_name));

        $mail->AddAddress($recipient);

        $mail->Subject = sprintf(JreviewsLocale::getPHP('INQUIRY_TITLE'), $listing['Listing']['title']);

        $mail->Body = sprintf(JreviewsLocale::getPHP('INQUIRY_FROM'),$from_name) . "<br /><br />";

        $mail->Body .= sprintf(JreviewsLocale::getPHP('INQUIRY_EMAIL'),$from_email) . "<br /><br />";

//        $mail->Body .= sprintf(JreviewsLocale::getPHP('INQUIRY_PHONE'),Sanitize::getString($this->data['Inquiry'],'phone')) . "<br />";

        $mail->Body .= sprintf(JreviewsLocale::getPHP('INQUIRY_LISTING'),$listing['Listing']['title']) . "<br /><br />";

        $mail->Body .= sprintf(JreviewsLocale::getPHP('INQUIRY_LISTING_LINK'),$link) . "<br /><br />";

        $mail->Body .= $message;

        if(!$mail->Send()){

            unset($mail);

            $response['str'][] = 'PROCESS_REQUEST_ERROR';

            return cmsFramework::jsonResponse($response);
        }

        $mail->ClearAddresses();

        $bccAdmin = $this->Config->inquiry_bcc;

        if($bccAdmin!='' && $bccAdmin!=$recipient)
        {
            $mail->AddAddress($bccAdmin);

            $mail->Send();
        }

        unset($mail);

        $extra_fields = array_diff_key($this->data['Inquiry'],array_flip(array('listing_id','from_email','from_name','message')));

        $User = cmsFramework::getUser();

        $data = array('Inquiry'=>array(
                'listing_id'=>$listing_id,
                'created'=>_CURRENT_SERVER_TIME,
                'from_email'=>$from_email,
                'from_name'=>$from_name,
                'to_email'=>$recipient,
                'user_id'=>$User->id,
                'message'=>$message,
                'extra_fields'=>json_encode($extra_fields),
                'ipaddress'=>ip2long($this->ipaddress)
            ));

        $this->Inquiry->store($data);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);

    }
}