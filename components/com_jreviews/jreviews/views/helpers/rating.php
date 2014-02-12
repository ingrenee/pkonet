<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RatingHelper extends MyHelper
{
	var $no_rating_text = null; // Default no rating output
	var $rating_average_all = 0;
	var $rating_value = 0;
	var $review_count = 0;
	var $tmpl_suffix;

	function options($scale, $default = _JR_RATING_OPTIONS, $na=1)
    {
		$options = array();

		if($this->Config->rating_selector == 'select')
        {
			$options = array(''=>$default);
		}

		// recall 1 = Required ; 0 = Not Required = allow N/A
		if ($na == 0 )
		{
			$options['na'] = __t('No rating', true);
		}

        $inc = !$this->Config->rating_increment ? 1 : $this->Config->rating_increment;

        for($i=$inc;$i<=$scale;$i=$i+$inc)

        {
            $options[(string)$i] = (string)$i;
        }

		// You can customize the text of the options by commenting the code above and using the one below:
//      $options['na'] = 'N/A';
//		$options[1] = 'Terrible';
//		$options[2] = 'Not so bad';
//		$options[3] = 'Just ok';
//		$options[4] = 'Good';
//		$options[5] = 'Excellent';

		return $options;
	}

	function overallRatings($listing, $page, $type = '')
	{
        $editor_reviews = $this->Config->getOverride('author_review',$listing['ListingType']['config']);

        $user_reviews = $this->Config->getOverride('user_reviews',$listing['ListingType']['config']);

        if(!($listing['Criteria']['state'] == 1 && ($editor_reviews || $user_reviews))) {

            return '';
        }

        # For module/plugin/widget calls we check those settings instead of global ones
        isset($this->params['module']) and $page = 'module';

        switch($page) {

            case 'list':

                $show_user_rating = $this->Config->getOverride('list_show_user_rating',$listing['ListingType']['config']);

                $show_editor_rating = true;
            break;

            case 'module':

                $show_user_rating = Sanitize::getBool($this->params['module'],'user_rating',1);

                $show_editor_rating = Sanitize::getBool($this->params['module'],'editor_rating',1);
            break;

            default:

                $show_user_rating = true;

                $show_editor_rating = true;
            break;
        }

		$ratings = '<div class="jrOverallRatings">';

		// editor ratings
		if($show_editor_rating && $editor_reviews && $type != 'user') {

			$editor_rating = Sanitize::getVar($listing['Review'],'editor_rating');

            $editor_rating_count = Sanitize::getInt($listing['Review'],'editor_rating_count');

            $rating_stars = $this->drawStars($editor_rating, $this->Config->rating_scale, 'editor');

            $rating_value = $this->round($editor_rating,$this->Config->rating_scale);

            $rating_count = ($editor_rating_count > 1) ? ' <span class="rating_count">(<span class="count">' . $editor_rating_count . '</span>)</span>' : '';

			$ratings .= '<div class="jrOverallEditor" title="' . __t("Editor rating", true) . '">';

            $ratings .= '<span class="jrIconEditor jrRatingLabel"></span>';

            $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

            $ratings .= '<span class="jrRatingValue">' . $rating_value . $rating_count . '</span>';

            $ratings .= '</div>';
		}

		// user ratings
		if($page == 'content' && $user_reviews && $type != 'editor') {

			$user_rating = Sanitize::getVar($listing['Review'],'user_rating');

            $rating_stars = $this->drawStars($user_rating, $this->Config->rating_scale, 'user');

            $rating_value = $this->round($user_rating,$this->Config->rating_scale);

            $rating_count = Sanitize::getInt($listing['Review'],'user_rating_count');

            if ($rating_count > 0) { // add schema.org properties

                $ratings .= '<div class="jrOverallUser" title="' . __t("User rating", true) . '" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';

                $ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

                $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

                $ratings .= '<span class="jrRatingValue">';

                    $ratings .= '<span itemprop="ratingValue">' . $rating_value . '</span>';

                    $ratings .= '<span class="jrReviewCount"> (<span class="count" itemprop="reviewCount">' . $rating_count . '</span>)</span>';

                $ratings .= '</span>';

                $ratings .= '<meta itemprop="bestRating" content="' .  $this->Config->rating_scale . '">';

                $ratings .= '<meta itemprop="itemReviewed" content="'. $listing['Listing']['title'] . '">';

            } else {

                $ratings .= '<div class="jrOverallUser" title="' . __t("User rating", true) . '">';

                $ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

                $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

                $ratings .= '<span class="jrRatingValue">' . $rating_value . ' <span class="rating_count">(<span class="count">' . $rating_count . '</span>)</span></span>';

            }

            $ratings .= '</div>';
		}
		elseif ($show_user_rating && in_array($page,array('list','module')) && $user_reviews && $type != 'editor') {

			$user_rating = Sanitize::getVar($listing['Review'],'user_rating');

			$rating_stars = $this->drawStars($user_rating, $this->Config->rating_scale, 'user');

			$rating_value = $this->round($user_rating,$this->Config->rating_scale);

			$rating_count = Sanitize::getInt($listing['Review'],'user_rating_count');

			$ratings .= '<div class="jrOverallUser" title="' . __t("User rating", true) . '">';

			$ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

			$ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

			$ratings .= '<span class="jrRatingValue">' . $rating_value . ' <span class="rating_count">(<span class="count">' . $rating_count . '</span>)</span></span>';

			$ratings .= '</div>';
		}

		$ratings .= '</div>';

		return $ratings;
	}

	// Converts numeric ratings into graphical output
	function drawStars($rating, $scale, $type)
	{
		$ratingPercent = number_format(($rating/$scale)*100,0);

        $type = ucfirst($type);

		if ($rating > 0) {

			return "<div class=\"jrRatingStars$type\"><div style=\"width:{$ratingPercent}%;\">&nbsp;</div></div>";

		} elseif ($this->no_rating_text) {

			return $this->no_rating_text;
		} else {

			return "<div class=\"jrRatingStars$type\"><div style=\"width:0%;\">&nbsp;</div></div>";
		}
	}

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function detailedRatings($review, $type)
    {
        # Check if ratings enabled
        if($review['Criteria']['state'] != 1) return '';

        # Init vars
        $isReview = isset($review['Review']); // Is it a user/editor review or a listing?

        $showDetailedCriteriaRatings = !$isReview || (($type == 'user' && $this->Config->user_ratings) || ($type == 'editor' && $this->Config->author_ratings));

        $output = '';

        // Conditions below add support for calling this method using the $listing array
        if($type == 'editor' && $isReview && isset($review['Review']['editor_rating'])) {

            $review['Rating']['average_rating'] = $review['Review']['editor_rating'];

            $review['Rating']['ratings'] = explode(',',$review['Review']['editor_criteria_rating']);
        }
        elseif($type == 'user' && $isReview && isset($review['Review']['user_rating'])) {

            $review['Rating']['average_rating'] = $review['Review']['user_rating'];

            $review['Rating']['ratings'] = explode(',',$review['Review']['user_criteria_rating']);
        }

        if(!isset($review['Rating']['average_rating']) ||
            ($this->Config->rating_hide_na && $review['Rating']['average_rating'] == 'na')
            // ||
            // !isset($review['Review'])
            )  {

            return '';
        }

        # Remove all na rated criteria
        if($this->Config->rating_hide_na)
        {
            foreach($review['Criteria']['criteria'] AS $key=>$value)
            {
                if($review['Rating']['ratings'][$key] == 'na') { unset($review['Criteria']['criteria'][$key]); }
            }
        }

        $output .= '<table class="jrRatingTable" border="0" cellpadding="0" cellspacing="0">';

        # Only one criterion defined
        if(count($review['Criteria']['criteria']) == 1)
        {
            $output .= '<tr>';
			$output .= '<td class="jrRatingLabel">' . implode($review['Criteria']['criteria']) . '&nbsp;</td>';
            $output .= '<td>'. $this->drawStars($review['Rating']['average_rating'], $this->Config->rating_scale, $type) . '</td>';
            $output .=  '<td class="jrRatingValue">' . $this->round($review['Rating']['average_rating'],$this->Config->rating_scale);

            // rating count for criterion
            if (( $this->Config->show_criteria_rating_count == 2
                    || ( $this->Config->show_criteria_rating_count == 1
                        && in_array(0, $review['Criteria']['required']) ))
                && !empty($review['summary']))
            {
                $output .= '&nbsp;&nbsp;(' . (int) $review['Rating']['criteria_rating_count'][0] . ')';
            }

            $output .=  '</td>';
            $output .=  '</tr>';
        }
        # More than one criterion, display detailed ratings
        else
        {
            $output .= '<tr>';
            $output .= '<td class="jrRatingLabel">' . __t("Overall rating",true) . '&nbsp;</td>';
            $output .= '<td>' . $this->drawStars($review['Rating']['average_rating'], $this->Config->rating_scale, $type) . '</td>';
            $output .= '<td class="jrRatingValue">' . $this->round($review['Rating']['average_rating'],$this->Config->rating_scale) . '</td>';
            $output .=  '</tr>';

            if($showDetailedCriteriaRatings)
            {
                foreach($review['Criteria']['criteria'] AS $key=>$value)
                {
                    $output .= '<tr>';
                    $output .= '<td class="jrRatingLabel">' . $value . '&nbsp;</td>';
                    $output .= '<td>' . $this->drawStars(Sanitize::getString($review['Rating']['ratings'],$key), $this->Config->rating_scale, $type) . '</td>';
                    $output .= '<td class="jrRatingValue">' . $this->round(Sanitize::getString($review['Rating']['ratings'],$key),$this->Config->rating_scale);

                    // rating count for criterion
                    if (($this->Config->show_criteria_rating_count == 2
                            || ( $this->Config->show_criteria_rating_count == 1
                                && in_array(0, $review['Criteria']['required'])))
                            && !empty($review['summary']))
                    {
                        $output .= '&nbsp;&nbsp;(' . Sanitize::getInt($review['Rating']['criteria_rating_count'],$key) . ')';
                    }

                    $output .=  '</td>';
                    $output .=  '</tr>';
                }
            }
        }

        $output .= '</table>';
        $output .= '<div class="jrClear"></div>';

        return $output;
    }

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function compareRatings($listing, $type)
    {
		if($type == 'editor')
        {
            $average_rating = $listing['Review']['editor_rating'];

			$criteria_rating_count = explode(',',$listing['Review']['editor_criteria_rating_count']);
            $ratings = explode(',',$listing['Review']['editor_criteria_rating']);
        }
		else {

            $average_rating = $listing['Review']['user_rating'];

			$criteria_rating_count = explode(',',$listing['Review']['user_criteria_rating_count']);

			$ratings = explode(',',$listing['Review']['user_criteria_rating']);
		}

        # Remove all na rated criteria
        if($this->Config->rating_hide_na)
        {
            foreach($listing['Criteria']['criteria'] AS $key=>$value)
            {
                if($ratings[$key] == 'na') { unset($listing['Criteria']['criteria'][$key]); }
            }
        }

        // Only one criterion defined
        if(count($listing['Criteria']['criteria']) == 1)
        {
            return '<div class="itemUserRating jrCompareField">' . $this->drawStars($average_rating, $this->Config->rating_scale, $type) . '</div>';
        }

        // More than one criterion, display detailed ratings
        $output = '<div class="itemUserRating jrCompareField">' . $this->drawStars($average_rating, $this->Config->rating_scale, $type) . '</div>';

        $i = 0;

        foreach($listing['Criteria']['criteria'] AS $key=>$value)
        {
            $output .= '<div class="itemUserRating' . $i . ' jrCompareField' . (fmod($i, 2) ? '' : ' alt') . '">';
            $output .= $this->drawStars(Sanitize::getInt($ratings,$key), $this->Config->rating_scale, $type);
            $output .= '</div>';
            $i++;
        }
        return $output;
    }

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function compareRatingsHeader($listing, $type)
    {
        $isReview = isset($listing['Review']); // It's user or editor review
        $showDetailedCriteriaRatings = ($type == 'user' && $this->Config->user_ratings) || ($type == 'editor' && $this->Config->author_ratings);

        # Remove all na rated criteria
        if($this->Config->rating_hide_na)
        {
            foreach($listing['Criteria']['criteria'] AS $key=>$value)
            {
                if($listing['Rating']['ratings'][$key] == 'na') { unset($listing['Criteria']['criteria'][$key]); }
            }
        }

        // Only one criterion defined
        if(count($listing['Criteria']['criteria']) == 1)
        {
            return '<div class="itemUserRating jrCompareField">' . $listing['Criteria']['criteria'][0] . '</div>';
        }

        // More than one criterion, display detailed ratings
        if($showDetailedCriteriaRatings)
        {
            $output = '<div class="itemUserRating jrCompareField">' . __t("Overall rating",true) . '</div>';

            $i = 0;
            foreach($listing['Criteria']['criteria'] AS $key=>$value) {
                $output .= '<div class="itemUserRating' . $i . ' jrCompareField' . (fmod($i, 2) ? '' : ' alt') . '">';
                $output .= $value;
                $output .= '</div>';
                $i++;
            }
            return $output;
        }

        return '<div class="itemUserRating jrCompareField">' . __t("Overall rating",true) . '</div>';
    }

	function round($value, $scale)
	{
		if(is_numeric($value)) {
			$value = ceil($value * 100) / 100; // extra math forces ceil() to work with decimals
		        $round = $scale > 10 ? 0 : 1;
		        return number_format($value,$round);
		} else {
		 	return empty($value) ? '0.0' : '<span class="jr_noRating" title="'.__t('Not rated', true).'">'.__t('N/A', true).'</span>';
		}
	}

	function getRank($userid,$rank,$limit,$Itemid) {

		$pag_start = '';
		$start = floor($rank/$limit)*$limit;

		switch ($rank) {
			 case ($rank==1): $user_rank = _JR_RANK_TOP1; break;
			 case ($rank<=10 && $rank>0): $user_rank = _JR_RANK_TOP10; break;
			 case ($rank<=50 && $rank>10): $user_rank = _JR_RANK_TOP50; break;
			 case ($rank<=100 && $rank>50): $user_rank = _JR_RANK_TOP100; break;
			 case ($rank<=500 && $rank>100): $user_rank = _JR_RANK_TOP500; break;
			 case ($rank<=1000 && $rank>500): $user_rank = _JR_RANK_TOP1000; break;
			 default: $user_rank = '';
		}

		if ($start > 1) {
			$pag_start = "&amp;limit=$limit&amp;limitstart=$start";
		}


		if ($user_rank != '') {
			$url = $this->link($user_rank,'index.php?option='.S2Paths::get('jreviews','S2_CMSCOMP').'&amp;task=reviewrank&amp;user='.$userid.$pag_start.'#$userid');
			return $url;
		}
	}

}
