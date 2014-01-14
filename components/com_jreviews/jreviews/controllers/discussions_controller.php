<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DiscussionsController extends MyController {

    var $uses = array('menu','captcha','user','criteria','review','field','discussion','media');

    var $helpers = array('cache','routes','libraries','html','assets','form','time','jreviews','custom_fields','rating','paginator','community','widgets','media');

    var $components = array('config','access','everywhere','notifications','media_storage');

    var $autoRender = true;

    var $autoLayout = true;

    var $formTokenKeys = array('discussion_id','type','review_id');

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Discussion;
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->Discussion;
    }

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $this->Discussion->data = & $this->params;

        if($post_id = Sanitize::getInt($this->params,'id'))
        {
            $post = $this->Discussion->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $post_id)));

            $overrides = Sanitize::getVar($post['ListingType'],'config');

            $owner_id = $post['User']['user_id'];

            $token = Sanitize::getString($this->params,'token');

            if(!$this->Access->canDeletePost($owner_id, $owner_id) || 0 != strcmp($token,cmsFramework::getCustomToken($post_id)))
            {
                $response['str'][] = 'ACCESS_DENIED';

                return cmsFramework::jsonResponse($response);
            }

            if($this->Discussion->delete('discussion_id',$post_id))
            {
                $response['success'] = true;

                return cmsFramework::jsonResponse($response);
            }
        }

        $response['str'][] = 'PROCESS_REQUEST_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function edit()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $discussion_id = Sanitize::getInt($this->params,'discussion_id');

        if($discussion_id)
        {
            if($post = $this->Discussion->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $discussion_id))))
            {
                $overrides = Sanitize::getVar($post['ListingType'],'config');

                if(!$this->Access->canEditPost($post['Discussion']['user_id'], $overrides))
                {
                    return s2Messages::accessDenied();
                }

                $this->set(array(
                    'discussion_id'=>$discussion_id,
                    'review_id'=>$post['Discussion']['review_id'],
                    'User'=>$this->_user,
                    'post'=>$post,
                    'formTokenKeys'=>$this->formTokenKeys
                ));

                return $this->render('discussions','create');
            }
        }
    }

    function reply()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $discussion_id = Sanitize::getInt($this->params,'discussion_id');

        $review_id = Sanitize::getInt($this->params,'review_id');

        if($discussion_id && $review_id)
        {
            $post = array('Discussion'=>array(
                    'discussion_id'=>$discussion_id,
                    'review_id'=>$review_id,
                    'type'=>'review',
                    'text'=>''
                ));

            $this->set(array(
                'isNew'=>true,
                'discussion_id'=>$discussion_id,
                'review_id'=>$review_id,
                'post'=>$post,
                'User'=>$this->_user,
                'formTokenKeys'=>$this->formTokenKeys
            ));

            return $this->render('discussions','create');
        }
    }

    function _save()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $this->Discussion->isNew = true;

        $response = array('success'=>false,'str'=>array());

        $parent_id = Sanitize::getInt($this->data['Discussion'],'parent_post_id');

        $isNew = !Sanitize::getBool($this->data['Discussion'],'discussion_id');

        $isReply = $isNew && $parent_id ? true : false;

        $response['success'] = false;

        $response['is_new'] = $isNew;

        $response['reply'] = $isReply;

        $response['moderation'] = true;

        $response['html'] = '';

        # Load the notifications observer model component and initialize it.
        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security');

        $this->__initComponents();

        # Validate form token
        if($this->invalidToken) {

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
         }

        if(!$this->Config->review_discussions || !$this->Access->canAddPost()){

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $comment = Sanitize::getString($this->data['Discussion'],'text');

        if($comment == '')
        {
            $response['str'][] = 'DISCUSSION_VALIDATE_COMMENT';
        }

        # Validate input fields
        $username = Sanitize::getString($this->data,'username');

        $register_guests = Sanitize::getBool($this->viewVars,'register_guests');

        $this->Discussion->validateInput(Sanitize::getString($this->data['Discussion'],'name'), "name", "text", 'VALIDATE_NAME', !$this->_user->id && (($register_guests && $username) || $this->Config->discussform_name == 'required' ? true : false));

        $this->Discussion->validateInput(Sanitize::getString($this->data['Discussion'],'email'), "email", "email", 'VALIDATE_EMAIL', (($register_guests && $username) || $this->Config->discussform_email == 'required' ? true : false) && !$this->_user->id && $isNew);

        $this->Discussion->validateInput($this->data['Discussion']['text'], "text", "text", 'DISCUSSION_VALIDATE_COMMENT', true);

        # Validate security code
        if($isNew && $this->Access->showCaptcha())
        {
            if(!isset($this->data['Captcha']['code']))
            {
                $this->Discussion->validateSetError("code", 'VALID_CAPTCHA_INVALID');
            }
            elseif ($this->data['Captcha']['code'] == '') {

                $this->Discussion->validateInput($this->data['Captcha']['code'], "code", "text", 'VALID_CAPTCHA', 1);
            }
            else {

                if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress))
                {
                    $this->Discussion->validateSetError("code", 'VALID_CAPTCHA_INVALID');
                }
            }
         }

        $validation = $this->Discussion->validateGetErrorArray();

        if(!empty($validation))
        {
            if ($isNew && $this->Access->showCaptcha())
            {
                // Replace captcha with new instance
                $captcha = $this->Captcha->displayCode();

                $response['captcha'] = $captcha['src'];
            }

            $response['str'] = $validation;

            return cmsFramework::jsonResponse($response);
        }

        if($isNew) {

            $this->data['Discussion']['user_id'] = $this->_user->id;

            $this->data['Discussion']['ipaddress'] = $this->ipaddress;

            $this->data['Discussion']['created'] = date('Y-m-d H:i:s');

            $this->data['Discussion']['approved'] = (int)!$this->Access->moderatePost();

            if($this->_user->id)
            {
                $this->data['Discussion']['name'] = $this->_user->name;

                $this->data['Discussion']['username'] = $this->_user->username;

                $this->data['Discussion']['email'] = $this->_user->email;

            } else {

                $this->data['Discussion']['email'] = Sanitize::html($this->data['Discussion'],'email','',true);

                $this->data['Discussion']['name'] = $this->data['Discussion']['username'] = Sanitize::html($this->data['Discussion'],'name','',true);
            }
        }
        else {

            $this->data['Discussion']['modified'] = date('Y-m-d H:i:s');

            $this->data['Discussion']['approved'] = 1;

        }

        if($this->Discussion->store($this->data))
        {
            if(!$this->data['Discussion']['approved'])
            {
                $response['success'] = true;

                return cmsFramework::jsonResponse($response);
            }

            // Query post to get full info for instant refresh
            $discussion = $this->Discussion->findRow(array(
                'conditions'=>array(
                    'Discussion.type = "review"',
                    'Discussion.discussion_id = ' . $this->data['Discussion']['discussion_id']
                ))
            );

            $this->set(array(
                'isNew'=>$isNew,
                'User'=>$this->_user,
                'post'=>$discussion
            ));

            $response['success'] = true;

            $response['moderation'] = false;

            $response['html'] = $this->render('discussions','post_layout');

            return cmsFramework::jsonResponse($response);
          }
    }

    function getPost()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $post_id = Sanitize::getInt($this->params,'id');

        $post = $this->Discussion->findRow(array(
            'conditions'=>array(
                'Discussion.discussion_id = ' . $post_id,
                'Discussion.approved = 1'
        )));

        $this->set('post',$post);

        return $this->render('discussions','parent_popover');
    }

    function latest()
    {
		$menu_id = Sanitize::getInt($this->params,'Itemid');

        $sort_default = 'rdate';

        $sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort != '';

        $this->params['default_order'] = $sort_default;

        $sort = $this->params['order'] = Sanitize::getString($this->params,'order',$sort_default);

		$conditions = array(
				'Discussion.type = "review"',
				'Discussion.approved = 1',
				'Discussion.review_id > 0'
				);

		$joins[] = 'INNER JOIN #__jreviews_comments AS Review ON Review.id = Discussion.review_id AND Review.published = 1';

        $queryData = array(
            'conditions'=>$conditions,
            'joins'=>$joins,
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array(
                $this->Discussion->processSorting($sort)
                )
        );

		$posts = $this->Discussion->findAll($queryData);

		$count = $this->Discussion->findCount(array(
			'conditions'=>$conditions,
			'joins'=>$joins
		));

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

		$this->set(array(
			'User'=>$this->_user,
			'posts'=>$posts,
			'pagination'=>array(
				'total'=>$count,
				'offset'=>($this->page-1)*$this->limit
			),
			'page'=>$page
		));
    }

    // Review discussions
    function review()
    {
        $this->viewVarsAssets = array('listing');

        $this->limit = 10;

        $posts = array();

        $count = 0;

        $listing = array();

        $review = array();

        $sort = Sanitize::getString($this->params,'order');

        // generate canonical tag for urls with order param
        $canonical = Sanitize::getBool($this->Config,'url_param_joomla') && $sort != '';

        if($this->_user->id === 0)
        {
            $this->cacheAction = Configure::read('Cache.expires');
        }

        $review_id = Sanitize::getInt($this->params,'id');

        if($review_id)
        {
            $this->Review->runProcessRatings = false;

            $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

            $listing = $review = $this->Review->findRow(
                array(
                    'conditions'=>array('Review.id = ' . $review_id,'Review.published = 1')
                )
            );

            if($listing)
            {
                # Override global configuration
                isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

                # Set the theme suffix
                if($listing['Listing']['extension'] == 'com_content')
                {
                    $this->Theming->setSuffix(array('cat_id'=>$listing['Listing']['cat_id']));
                }

                $listing['User'] = isset($listing['ListingUser']) ? $listing['ListingUser'] : array();

                $listing['Community'] = isset($listing['ListingCommunity']) ? $listing['ListingCommunity'] : array();

                unset($listing['Field'],$listing['Vote'],$listing['ListingUser'],$listing['ListingCommunity']);

                unset($review['Listing'],$review['Category'],$review['Directory']);

                if($this->Config->review_discussions)
                {
                    $posts = $this->Discussion->findAll(array(
                        'conditions'=>array(
                            'Discussion.type = "review"',
                            'Discussion.review_id = ' . $review_id,
                            'Discussion.approved = 1'
                            ),
                        'offset'=>$this->offset,
                        'limit'=>$this->limit,
                        'order'=>array(
                            $this->Discussion->processSorting($sort)
                            )
                    ));

                    $count = $this->Discussion->findCount(array(
                        'conditions'=>array(
                            'Discussion.type = "review"',
                            'Discussion.review_id = ' . $review_id,
                            'Discussion.approved = 1')
                    ));

                }
            }

            $post = array('Discussion'=>array(
                    'discussion_id'=>'',
                    'review_id'=>$review['Review']['review_id'],
                    'type'=>'review',
                    'extension'=>$review['Review']['extension'],
                    'text'=>''
                ));

            $page = array(
                'title_seo'=>sprintf(JreviewsLocale::getPHP('REVIEW_DETAIL_TITLE_SEO'),$listing['Listing']['title'],$review['Review']['title']),
                'keywords'=>Sanitize::getString($listing['Listing'],'metakey'),
                'description'=>Sanitize::getString($listing['Listing'],'description')
            );

            /******************************************************************
            * Generate SEO canonical tags for sorted pages
            *******************************************************************/
            if($canonical) {

                $page['canonical'] = cmsFramework::getCurrentUrl('order');
            }

            $this->set(array(
                'User'=>$this->_user,
                'listing'=>$listing,
                'review'=>$review,
                'post'=>$post,
                'posts'=>$posts,
                'discussion_id'=>0,
                'extension'=>$review['Review']['extension'],
                'formTokenKeys'=>$this->formTokenKeys,
                'page'=>$page,
                'pagination'=>array(
                    'total'=>$count,
                    'offset'=>($this->page-1)*$this->limit
                )
            ));
        }
    }
}
