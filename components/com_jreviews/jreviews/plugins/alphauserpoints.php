<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/**  Usage
    You need to create the rules yourself in the AUP Rules page.

    Supported actions for points

        Listing submit and delete
        Review submit and delete
        Review comment submit and delete
        Creating rules for AUP points

    1. Go to the AUP admin and click on "Rules"
    2. Click "New" to create a new rule:
        For "Plugin type" use: com_jreviews
        For "Unique function name" use one of the following options:
            plgaup_jreviews_listing_add
            plgaup_jreviews_listing_delete
            plgaup_jreviews_discussion_add
            plgaup_jreviews_discussion_delete
            plgaup_jreviews_review_add
            plgaup_jreviews_review_delete
    3. For "Points" use positive or negative values depending on the action
    4. Set "Fixed points" to "yes"
    5. Set "Published" to "yes"
    6. Set "Auto approve" to "yes"
    7. Click "Save & Close"
*/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AlphauserpointsComponent extends S2Component {

	var $plugin_order = 100;

	var $name = 'alphauserpoints';

	var $published = true;

	var $inAdmin = false;

	function startup(&$controller)
	{
		$this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

		$this->c = & $controller;

		$api_AUP = PATH_ROOT . 'components' . DS . 'com_alphauserpoints' . DS . 'helper.php';
		if(file_exists($api_AUP))
		{
			require_once($api_AUP);
		}
		else
		{
			$this->published = false;
		}
	}

	function plgAfterSave(&$model)
	{
		if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save'))) {
			return;
		}

		switch($model->name)
		{
			case 'Discussion':
				$this->_plgDiscussionAfterSave($model);
				break;
			case 'Listing':
				$this->_plgListingAfterSave($model);
				break;
			case 'Review':
				$this->_plgReviewAfterSave($model);
				break;
		}
	}

	function plgBeforeDelete(&$model)
	{
		switch($model->name)
		{
			case 'Discussion':
				$this->_plgDiscussionBeforeDelete($model);
				break;
			case 'Listing':
				$this->_plgListingBeforeDelete($model);
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
		if($post['Discussion']['user_id'] > 0  && $post['Discussion']['approved'] == 1)
		{
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($post['Discussion']['user_id']);
			if($aupid)
			{
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_discussion_delete', $aupid);
			}
		}
	}

	function _plgListingBeforeDelete(&$model)
	{
		$listing = $this->_getListing($model);
		if($listing['Listing']['user_id'] > 0 && $listing['Listing']['state'] == 1 && !$this->_isPaidListing($listing))
		{
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($listing['Listing']['user_id']);
			if($aupid)
			{
				// Begin deduct points
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_listing_delete', $aupid);
			}
		}
	}

	function _plgReviewBeforeDelete(&$model)
	{
		$review_id = Sanitize::getInt($model->data,'review_id');

		$review = $model->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

		// Begin deduct points
		if($review['Review']['published'] == 1 && $review['User']['user_id'] > 0)
		{
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($review['User']['user_id']);
			if($aupid)
			{
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_review_delete', $aupid);
			}
		}
	}

	function _plgDiscussionAfterSave(&$model)
	{
		$post = $this->_getReviewPost($model);

		// Treat moderated reviews as new
		$this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

		// Begin add points
		if($model->isNew && $post['Discussion']['approved'] == 1)
		{
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($post['User']['user_id']);
			if($aupid)
			{
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_discussion_add', $aupid);
			}
		}
	}

	function _plgListingAfterSave(&$model)
	{
		$listing = $this->_getListing($model);

		// Treat moderated listings as new
		$this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

		if(isset($model->isNew) && $model->isNew && $listing['Listing']['state'] == 1 && !$this->_isPaidListing($listing))
		{
			// Begin add points
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($listing['User']['user_id']);

			if($aupid)
			{
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_listing_add', $aupid);
			}
		}
	}

	function _plgReviewAfterSave(&$model)
	{
		$review = $this->_getReview($model);

		// Treat moderated reviews as new
		$this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

		if(isset($model->isNew) && $model->isNew && $review['Review']['published'] == 1)
		{
			// Begin add points
			$aupid = AlphaUserPointsHelper::getAnyUserReferreID($review['User']['user_id']);
			if($aupid)
			{
				AlphaUserPointsHelper::newpoints('plgaup_jreviews_review_add', $aupid);
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
            $listing_id = isset($this->c->data['Listing']) ? Sanitize::getInt($this->c->data['Listing'],'id') : false;
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
						), array('plgAfterFind' /* limit callbacks */)
					);

			$this->c->set('review',$review);
		}

		return $review;
	}

}
