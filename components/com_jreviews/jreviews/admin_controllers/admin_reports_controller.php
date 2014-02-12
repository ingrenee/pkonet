<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminReportsController extends MyController {

    var $uses = array('menu','criteria','report','discussion','review','media');
    var $helpers = array('html','time','admin/admin_routes','routes','rating','custom_fields','media');
    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;
    var $autoLayout = true;

    var $response = array();

    function &getEverywhereModel(){
        return $this->Review;
    }

    function beforeFilter()
    {
		$this->Access->init($this->Config);

        parent::beforeFilter();
    }

    function moderation()
    {
		$media = $reviews = $discussions = array();

        $this->limit = 10;

        $processed = Sanitize::getInt($this->params,'processed');

        $this->offset = $this->offset - $processed;

        $conditions = array(
            "Report.approved = 0",
            "(Report.listing_id > 0 AND (Report.review_id > 0 || Report.review_id > 0 || Report.post_id > 0 || Report.media_id > 0))"
        );

        $reports = $this->Report->findAll(array(
            'fields'=>array('Report.*'),
            'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('Report.report_id DESC')
        ));

        $total = $this->Report->findCount(array('conditions'=>$conditions));

		# Get IDs for each type of object being reported so we can query for additional details
		# Object types are reviews, comments, media

	    $review_ids = $post_ids = $media_ids = array();

		foreach($reports AS $key=>$report)
        {
            if(!empty($report['Report']['media_id'])) {
				$media_ids[] = $report['Report']['media_id'];
			}
			elseif(!empty($report['Report']['post_id'])) {
				$post_ids[] = $report['Report']['post_id'];
			}
			elseif(!empty($report['Report']['review_id'])) {
				$review_ids[] = $report['Report']['review_id'];
			}
        }

        $media_ids = array_unique($media_ids);

        $post_ids = array_unique($post_ids);

        $review_ids = array_unique($review_ids);

        $this->Review->runProcessRatings = false;

        $this->EverywhereAfterFind = false; // Triggers the afterFind in the Observer Model

		if(!empty($media_ids))
		{
			$media = $this->Media->findAll(array(
				'conditions'=>array('Media.media_id IN ('.implode(',',$media_ids).')')
			));
		}
		if(!empty($post_ids))
		{
			$discussions = $this->Discussion->findAll(array(
				'conditions'=>array('Discussion.discussion_id IN ('.implode(',',$post_ids).')')
			),array()/*no callbacks*/);
		}

		if(!empty($review_ids))
		{
			$reviews = $this->Review->findAll(array(
				'conditions'=>array('Review.id IN ('.implode(',',$review_ids).')')
			),array()/*no callbacks*/);
		}

        // Now we merge the report and reported object arrays
        foreach($reports AS $key=>$report)
        {
            if(isset($media[$report['Report']['media_id']]))
			{
				$reports[$key] = array_merge($reports[$key],$media[$report['Report']['media_id']]);
			}
			elseif(isset($discussions[$report['Report']['post_id']]))
			{
				$reports[$key] = array_merge($reports[$key],$discussions[$report['Report']['post_id']]);
			}
			elseif(isset($reviews[$report['Report']['review_id']])) {
				$reports[$key] = array_merge($reports[$key],$reviews[$report['Report']['review_id']]);
			}
        }

		$this->set(array(
            'processed'=>$processed,
            'reports'=>$reports,
            'total'=>$total
        ));

        return $this->render('reports','reports');
    }

    function _save()
    {
        $response = array();

        if($this->data['Report']['approved']==-2) {

            $this->Report->delete('report_id',$this->data['Report']['report_id']);
        }

        $this->Report->store($this->data);

        $this->response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid', Sanitize::getInt($this->params,'id'));

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $deleted = $this->Report->delete('report_id',$ids);

        if($deleted) {

            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }

	/**
	 * Returns front-end url for reported issue
	 */
	function _getSiteUrl()
	{
		$url = '';
		$listing_id = Sanitize::getInt($this->params,'listing_id');
		$review_id = Sanitize::getInt($this->params,'review_id');
		$post_id = Sanitize::getInt($this->params,'post_id');
		$media_id = Sanitize::getInt($this->params,'media_id');
		$extension = Sanitize::getInt($this->params,'extension');

		$Routes = ClassRegistry::getClass('RoutesHelper');

		$Routes->Config = & $this->Config;

		if($media_id)
		{
			$media = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

			$url = $Routes->mediaDetail('',array('media'=>$media),array('sef'=>false,'return_url'=>true));
		}
		elseif(($post_id && $review_id) || $review_id)
		{
			$this->Review->runProcessRatings = false;

			$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

			$review = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)));

			$url = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'sef'=>false,'return_url'=>true));
		}

		if($url) {
			$url = cmsFramework::route($url);
			$url .= strstr($url,'?') ? '&tmpl=component' : '?tmpl=component';
		}

		return $url;
	}
}