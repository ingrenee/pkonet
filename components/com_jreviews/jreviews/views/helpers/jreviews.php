<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsHelper extends MyHelper
{
	var $helpers = array('html','form','time','routes');

    static function orderingOptions()
    {
        $order_options_array = array (
            'featured'        =>__t("Featured",true),
            'alpha'            =>__t("Title",true),
//            'alias'            =>__t("Alias",true),
//            'ralpha'        =>__t("Title DESC",true),
            'rdate'            =>__t("Most recent",true),
            'updated'            =>__t("Last updated",true),
//            'date'            =>__t("Oldest",true),
            'rhits'            =>__t("Most popular",true),
//            'hits'            =>__t("Least popular",true),
            'rating'        =>__t("Highest user rating",true),
            'rrating'        =>__t("Lowest user rating",true),
            'editor_rating'    =>__t("Highest editor rating",true),
            'reditor_rating'=>__t("Lowest editor rating",true),
            'reviews'        =>__t("Most reviews",true),
            'author'        =>__t("Author",true)
        );
        return $order_options_array;
    }

	function orderingList($selected, $fields = array(), $return = false)
	{
        $orderingList = self::orderingOptions();

		if(Configure::read('geomaps.enabled')==true) {
			$orderingList['distance'] = __t("Distance",true);
            if($selected=='') $selected = 'distance';
		}

		if(!$this->Config->user_reviews) {
			unset($orderingList['reviews']);
			unset($orderingList['rating']);
			unset($orderingList['rrating']);
		}

		if(!$this->Config->author_review) {
			unset($orderingList['editor_rating']);
			unset($orderingList['reditor_rating']);
		}

		if(!empty($fields))
		{
			foreach($fields AS $field)
			{
                if($this->Access->in_groups($field['access'])) {
                    $orderingList[$field['value']] = $field['text'] . ' ' . __t("ASC",true);
                    $orderingList['r' . $field['value']] = $field['text'] . ' ' .  __t("DESC",true);
                }
			}
		}

		if($return) {
			return $orderingList;
		}

		$attributes = array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}

	function orderingListReviews($selected, $params = false) {

		$options_array = array(
			'rdate'			=>__t("Most recent",true),
			'date'			=>__t("Oldest",true),
            'updated'       =>__t("Last updated",true),
			'rating'		=>__t("Highest user rating",true),
			'rrating'		=>__t("Lowest user rating",true),
			'helpful'		=>__t("Most helpful",true),
			'rhelpful'		=>__t("Least helpful",true),
            'discussed'     =>__t("Most discussed",true)
		);

        $orderingList = $options_array;

        if(Sanitize::getBool($params,'return')) return $orderingList;

		$attributes = array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}

    function orderingListPosts($selected, $options = array()) {

        $options_array = array(
            'date'            =>__t("Oldest",true),
            'rdate'            =>__t("Most recent",true),
//            'helpful'        =>__t("Most helpful",true),
//            'rhelpful'        =>__t("Least helpful",true)
        );

        if(!empty($options)) {
            foreach($options AS $key) {
                if(isset($options_array[$key])) {
                    $orderingList[$key] = $options_array[$key];
                }
            }

        } else {
            $orderingList = $options_array;
        }

        $attributes = array(
            'size'=>'1',
            'onchange'=>"window.location=this.value;return false;"
        );

        return $this->generateFormSelect($orderingList,$selected,$attributes);
    }

	function generateFormSelect($orderingList,$selected,$attributes) {

		# Construct new route
		$new_route_page_1 = '';

		$new_route = cmsFramework::constructRoute($this->passedArgs,array('lang','order','page'));

		if(Sanitize::getInt($this->params,'page',1) == 1
			&& preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$new_route,$matches)
		) {
			// Remove menu segment from url if page 1 and it' a menu
				$new_route_page_1 = $matches[1];
		}
        else {

            $new_route_page_1 = $new_route;
        }

		$selectList = array();

		foreach($orderingList AS $value=>$text)
		{
			$default_order = Sanitize::getString($this->params,'default_order');

			// Default order takes user back to the first page
			if($value == $default_order) {

				$selectList[] = array('value'=>cmsFramework::route($new_route_page_1),'text'=>$text);

			}
			else {

				$selectList[] = array('value'=>cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $value),'text'=>$text);
			}

		}

		if($selected == $default_order)
		{

			$selected = cmsFramework::route($new_route_page_1);

		}
		else {

			$selected = cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $selected);

		}


		return $this->Form->select('order',$selectList,$selected,$attributes);
	}

	function newIndicator($days, $date) {
		return $this->Time->wasWithinLast($days . ' days', $date);
	}



	function userRank($rank) {

		switch ($rank) {
			 case ($rank==1): $toprank = __t("#1 Reviewer",true); break;
			 case ($rank<=10 && $rank>0): $toprank = __t("Top 10 Reviewer",true); break;
			 case ($rank<=50 && $rank>10): $toprank = __t("Top 50 Reviewer",true); break;
			 case ($rank<=100 && $rank>50): $toprank = __t("Top 100 Reviewer",true); break;
			 case ($rank<=500 && $rank>100): $toprank = __t("Top 500 Reviewer",true); break;
			 case ($rank<=1000 && $rank>500): $toprank = __t("Top 1000 Reviewer",true); break;
			 default: $toprank = '';
		}

		return $toprank;

	}

    function listingDetailBreadcrumb($crumbs) {

        if(!$this->Config->breadcrumb_detail_category) {

            $crumbs = array_slice($crumbs,count($crumbs)-2);
        }

        if($this->Config->dir_show_breadcrumb && !empty($crumbs)):?>

        <div class="jrPathway">

            <?php foreach($crumbs AS $crumb):?>

                <?php if($crumb['link']!=''):?>

                    <a href="<?php echo $crumb['link'];?>"><?php echo $crumb['name'];?></a>

                <?php else:?>

                    <?php echo $crumb['name'];?>

                <?php endif;?>

            <?php endforeach;?>

        </div>

        <div class="jrClear"></div>

        <?php endif;

    }

    function listingInfoIcons($listing) {

        $media_photo_show_count = $this->Config->getOverride('media_photo_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_photo_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_photo_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;

        $media_video_show_count = $this->Config->getOverride('media_video_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_video_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_video_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        $media_attachment_show_count = $this->Config->getOverride('media_attachment_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_attachment_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_attachment_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        $media_audio_show_count = $this->Config->getOverride('media_audio_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_audio_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_audio_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        ?>

        <span class="jrListingStatus">

            <?php if($this->Config->getOverride('list_show_hits',$listing['ListingType']['config'])):?>

                <span title="<?php __t("Views");?>"><span class="jrIconGraph"></span><?php echo $listing['Listing']['hits']?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_video_show_count):?>

                <span title="<?php __t("Video count");?>"><span class="jrIconVideo"></span><?php echo (int)$listing['Listing']['video_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_photo_show_count):?>

                <span title="<?php __t("Photo count");?>"><span class="jrIconPhoto"></span><?php echo (int)$listing['Listing']['photo_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_audio_show_count):?>

                <span title="<?php __t("Audio count");?>"><span class="jrIconAudio"></span><?php echo (int)$listing['Listing']['audio_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_attachment_show_count):?>

                <span title="<?php __t("Attachment count");?>"><span class="jrIconAttachment"></span><?php echo (int)$listing['Listing']['attachment_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($this->Config->getOverride('favorites_enable',$listing['ListingType']['config'])):?>

                <span title="<?php __t("Favorite count");?>"><span class="jrIconFavorite"></span><span class="jr-favorite-<?php echo  $listing['Listing']['listing_id']; ?>"><?php echo (int)$listing['Favorite']['favored'];?></span></span>

            <?php endif;?>

        </span>

        <?php
    }

    function listingStatusLabels($listing) {

        $com_content = Sanitize::getString($listing['Listing'],'extension') == 'com_content';

        if(!$com_content) return '';

        $unpublished = $com_content && $listing['Listing']['state'] < 1;

        $expired = $com_content && $listing['Listing']['publish_down'] != NULL_DATE && strtotime($listing['Listing']['publish_down']) < time();
        ?>

        <span class="jrStatusIndicators">

            <?php if($expired || $unpublished):?>

                <?php if($unpublished):?><span class="jrStatusLabel jrOrange"><?php __t("Pending Moderation");?></span><?php endif;?>

                <?php if($expired):?><span class="jrStatusLabel jrRed"><?php __t("Expired");?></span><?php endif;?>

            <?php else:?>

                <?php if($this->Config->getOverride('list_featured',$listing['ListingType']['config']) && $listing['Listing']['featured']):?>

                    <span class="jrStatusFeatured"><?php __t("Featured");?></span>

                <?php endif;?>

                <?php if($this->Config->getOverride('list_new',$listing['ListingType']['config']) && $this->newIndicator($this->Config->getOverride('list_new_days',$listing['ListingType']['config']),$listing['Listing']['created'])):?>

                    <span class="jrStatusNew"><?php __t("New");?></span>

                <?php endif;?>

                <?php if($this->Config->getOverride('list_hot',$listing['ListingType']['config']) && $this->Config->getOverride('list_hot_hits',$listing['ListingType']['config']) <= $listing['Listing']['hits']):?>

                    <span class="jrStatusHot"><?php __t("Hot");?></span>

                <?php endif;?>

            <?php endif;?>

        </span>

        <?php
    }

    function listingDetailFeed($listing) {

        if($listing['Criteria']['state'] && $this->Config->rss_enable):?>

            <div class="jrRSS"><ul class="jrFeeds"><li><?php echo $this->Routes->rssListing($listing);?></li></ul></div>

        <?php endif;
    }

    function loadModulePosition($position, $container = 'div', $style = 'xhtml') {

        $document   = JFactory::getDocument();
        $renderer   = $document->loadRenderer('module');
        $params     = array('style'=>$style);
        $modules    = JModuleHelper::getModules($position);

        $contents = '';

        foreach ($modules as $module)  {
            $contents .= $renderer->render($module, $params);
        }

        if ($container == 'tr' && $contents != '') {

            echo '<tr class="jrCustomModule"><td colspan="3">'.$contents.'</td></tr>';

        } else if ($contents != '') {

            echo '<div class="jrCustomModule jrClear">'.$contents.'</div>';

        }
    }
}
