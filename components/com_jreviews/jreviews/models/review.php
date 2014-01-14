<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReviewModel extends MyModel {

    var $name = 'Review';

    var $useTable = '#__jreviews_comments AS Review';

    var $primaryKey = 'Review.review_id';

    var $realKey = 'id';

    var $fields = array(
        'Review.id AS `Review.review_id`',
        'Review.pid AS `Review.listing_id`',
        'Review.mode AS `Review.extension`',
        'Review.created AS `Review.created`',
        'Review.modified AS `Review.modified`',
        'Review.userid AS `Review.user_id`',
        'Review.userid AS `User.user_id`',
        'CASE WHEN CHAR_LENGTH(User.name) THEN User.name ELSE Review.name END AS `User.name`',
        'CASE WHEN CHAR_LENGTH(User.username) THEN User.username ELSE Review.username END AS `User.username`',
        'Review.email AS `User.email`',
        'Review.ipaddress AS `User.ipaddress`',
        'Rank.rank AS `User.review_rank`',
        'Rank.reviews AS `User.review_count`',
        'Review.title AS `Review.title`',
        'Review.comments AS `Review.comments`',
        'Review.posts AS `Review.posts`',
        'Review.author AS `Review.editor`',
        'Review.published AS `Review.published`',
        'Rating.ratings AS `Rating.ratings`',
        '(Rating.ratings_sum/Rating.ratings_qty) AS `Rating.average_rating`',
        'Review.vote_helpful AS `Vote.yes`',
        '(Review.vote_total - Review.vote_helpful) AS `Vote.no`',
        '(Review.vote_helpful/Review.vote_total)*100 AS `Vote.helpful`',
        'Review.owner_reply_text AS `Review.owner_reply_text`',
        'Review.owner_reply_approved AS `Review.owner_reply_approved`',
        'Review.owner_reply_created AS `Review.owner_reply_created`',
		'Review.media_count AS `Review.media_count`',
		'Review.video_count AS `Review.video_count`',
		'Review.photo_count AS `Review.photo_count`',
		'Review.audio_count AS `Review.audio_count`',
		'Review.attachment_count AS `Review.attachment_count`'
//        'Criteria.id AS `Criteria.criteria_id`',
//        'Criteria.criteria AS `Criteria.criteria`',
//        'Criteria.tooltips AS `Criteria.tooltips`',
//        'Criteria.weights AS `Criteria.weights`'
    );

    var $joins = array(
        'ratings'=>'LEFT JOIN #__jreviews_ratings AS Rating ON Review.id = Rating.reviewid',
//        'listings'=>'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id', // Overriden in controller for jReviewsEverywhere
//        'jreviews_categories'=>'LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id', // AND JreviewsCategory.`option`=\'com_content\'
//        'criteria'=>'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        'user'=>'LEFT JOIN #__users AS User ON Review.userid = User.id',
        'ranks'=>'LEFT JOIN #__jreviews_reviewer_ranks AS Rank ON Review.userid = Rank.user_id'
    );

    var $conditions = array();

//    var $group = array('Review.id');

    var $runProcessRatings = true;

    var $valid_fields = array(); // Review fields


    function addReviewInfo($results, $modelName, $reviewKey)
    {
        // First get the review ids
        foreach($results AS $key=>$row)
            {
                if(isset($row[$modelName][$reviewKey])){
                    $review_ids[$row[$modelName][$reviewKey]] = $row[$modelName][$reviewKey];
                }
            }

        if(!empty($review_ids))
            {
                $fields = $this->fields;

                $this->fields = array(
                    'Review.id AS `Review.review_id`',
                    'Review.title AS `Review.title`',
                    'Review.`mode` AS `Listing.extension`',
                    'Review.pid AS `Listing.listing_id`',
                );

                $reviews = $this->findAll(array('conditions'=>array('Review.id IN ('.implode(',',$review_ids).')')),array());
                $reviews = $this->changeKeys($reviews,'Review','review_id');

                foreach($results AS $key=>$row)
                {
                    if(isset($reviews[$row[$modelName][$reviewKey]]))
                        {
                        $results[$key] = array_merge($results[$key],$reviews[$row[$modelName][$reviewKey]]);
                        }
                }
            }

        return $results;
    }

    /*
    * Centralized review delete function
    * @param array $review_ids
    */
    function del($ids)
	{
        if(!is_array($ids)) {
            $ids = array($ids);
        }

        if (!empty($ids))
        {
			foreach($ids AS $id)
			{
                $success = false;

                $this->data['Review']['id'] = $id;

				$this->plgBeforeDelete('Review.id',$id); // Only works for single review deletion

				# delete associated media
				$Media = ClassRegistry::getClass('MediaModel');

				$Media->deleteByReviewId($id);
			}

			// Get listings info before review id is lost. Used to update the listing totals after deletion.
			$query = "
				SELECT
					DISTINCT Review.pid AS listing_id, Review.mode AS extension
				FROM
					#__jreviews_comments AS Review
				WHERE
					Review.id IN (" . cleanIntegerCommaList($ids) . ")"
				;

			$listings = $this->query($query, 'loadObjectList');

			$query = "
				DELETE
					Review,
					FieldReview,
					Rating,
					Report,
					Vote,
					Discussion
				FROM
					#__jreviews_comments AS Review
				LEFT JOIN
					#__jreviews_review_fields AS FieldReview ON FieldReview.reviewid = Review.id
				LEFT JOIN
					#__jreviews_ratings AS Rating ON Rating.reviewid = Review.id
				LEFT JOIN
					#__jreviews_reports AS Report ON Report.review_id = Review.id
				LEFT JOIN
					#__jreviews_votes AS Vote ON Vote.review_id = Review.id
				LEFT JOIN
					#__jreviews_discussions AS Discussion ON Discussion.review_id = Review.id
				WHERE
					Review.id IN (".cleanIntegerCommaList($ids).")
			";

			if ($this->query($query)) {

				$success = true;

                // Clear cache
                cmsFramework::clearSessionVar('Review', 'findCount');

                cmsFramework::clearSessionVar('Discussion', 'findCount');

                cmsFramework::clearSessionVar('Media', 'findCount');

                clearCache('', 'views');

                clearCache('', '__data');

			}

			// Update listing totals
			$err = array();

			foreach ( $listings as $listing )
			{
				if ( !$this->saveListingTotals($listing->listing_id, $listing->extension) )
				{
					$err[] = $listing->listing_id;
				}
			}
        }

        return $success;
    }



    function getReviewExtension($review_id) {

        $query = "
			SELECT
				Review.`mode`
			FROM
				#__jreviews_comments AS Review
			WHERE
				Review.id = " . (int) $review_id
		;

		return $this->query($query, 'loadResult');
    }

    function getReviewerTotal() {

        return $this->query("SELECT COUNT(*) FROM #__jreviews_reviewer_ranks", 'loadResult');
    }

    function getRankPage($page,$limit)
    {
        # Check for cached version
        $cache_prefix = 'review_model_rankpage';
        $cache_key = func_get_args();
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            return $cache;
        }

        $offset = (int)($page-1)*$limit;


        $query = "
            SELECT
                User.id AS `User.user_id`,
                User.name AS `User.name`,
                User.username AS `User.username`,
                Rank.reviews AS `Review.count`,
                Rank.votes_percent_helpful AS `Vote.helpful`,
                Rank.votes_total AS `Vote.count`
            FROM
                #__users AS User
            INNER JOIN
                #__jreviews_reviewer_ranks AS Rank ON Rank.user_id = User.id
            ORDER BY
               Rank.rank
            LIMIT
                $offset, $limit
        ";

        $results = $this->__reformatArray($this->query($query, 'loadObjectList'));

        # Add Community info to results array
        if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
            $Community = ClassRegistry::getClass('CommunityModel');
            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

        # Send to cache
        S2cacheWrite($cache_prefix,$cache_key,$results);

        return $results;
    }

    /**
    * Shortcust to saveListingTotals if only the review id is available
    *
    * @param mixed $review_id
    * @return boolean
    */
    function saveListingTotalsByReviewId($review_id)
    {
        $review = $this->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

        return $this->saveListingTotals($review['Review']['listing_id'],$review['Review']['extension']);
    }

    /**
     * Saves totals for the listing after any kind of reviews update (save, publish, delete, change weights etc.)
     * @return    boolean
     */
    function saveListingTotals($listing_id, $extension, $weights = array())
    {
        if (empty($weights))
		{
            // Load listings' Everywhere model
            $file_name = 'everywhere' . '_' . $extension;

			$class_name = inflector::camelize($file_name).'Model';

			S2App::import('Model',$file_name,'jreviews');

			$ListingModel = new $class_name();

            $weights = $ListingModel->findRow(array(
                'fields' => 'Criteria.weights AS `Criteria.weights`',
                'conditions' => "Listing.{$ListingModel->realKey} = {$listing_id}"
                ),
                array() // No callback functions
            );

            unset($ListingModel);

            $weights = explode("\n", trim($weights['Criteria']['weights']));
        }

        $reviewTypes['user'] = 0; # user reviews

        # editor reviews only in com_content
        if ( $extension == 'com_content' )
        {
            $reviewTypes['editor'] = 1;
        }

        # initiate the results array now moved before the foreach
        $data['Totals'] = array(
            'listing_id' => $listing_id
            ,'extension' => $extension
        );

        # encompassing all calculations with foreach (procedures like changing the review type can affect both averages)
        foreach ( $reviewTypes as $reviewType => $reviewTypeValue )
        {
            # count comments
            $query = "
                SELECT COUNT(*)
                FROM #__jreviews_comments
                WHERE
                    pid = $listing_id
                    AND mode = '$extension'
                    AND published = 1
                    AND author = $reviewTypeValue
            ";

            $data['Totals'][$reviewType.'_comment_count'] = $this->query($query, 'loadResult');

            if ( empty($data['Totals'][$reviewType.'_comment_count']) )
            {
                # listing deletion moved after the foreach. instead populate the relevant array elements with empty values and move on
                $data['Totals'] += array_fill_keys(array($reviewType.'_rating',$reviewType.'_rating_count',$reviewType.'_criteria_rating',$reviewType.'_criteria_rating_count'), '');

                continue;
            }

            $reviewsExist = 1; # to be used after the foreach

            // Now, do ratings exist?
            $query = "
				SELECT
					Rating.ratings
                FROM
					#__jreviews_comments AS Review
                INNER JOIN
					#__jreviews_ratings AS Rating ON Review.id = Rating.reviewid
                WHERE
					Review.pid = '$listing_id' AND Review.published = 1
					AND Review.author = $reviewTypeValue
					AND Review.mode = " . $this->Quote($extension) . "
			";

        //      GROUP BY reviews.id

			$rows = $this->query($query, 'loadAssocList');

            if (!empty($rows))
			{
                // Ratings exist, begin calculations

                $sum_weights = array_sum($weights);

                $weighted = (is_array($weights) && round($sum_weights,0) == 100 ? 1 : 0);

                $reviewCount = 0;

                $sumRatings = array(); # must init so values from previous foreach iteration won't be used

                // This is used like reviewCount, but for each criterion separately.
                // Preparing the inital array here, see later on for its use
                $reviewCountForCriterion = array_fill(0, count(explode(',', $rows[0]['ratings'])), 0);

                foreach ($rows as $rating) {

                    $ratings_array = explode(',',$rating['ratings']);

                    // if all is N/A, do not count this review towards the average
                    if ( array_sum($ratings_array) != 0 ) # recall 'na' == 0 equals true
                    {
                        $reviewCount++;
                    }

                    // Calculates the totals for each criteria
                    for ($j = 0;$j<count($ratings_array);$j++)
                    {
                        if (isset($sumRatings[$j]))
                        {
                            $sumRatings[$j] += $ratings_array[$j];
                        } else
                        {
                            $sumRatings[$j] = $ratings_array[$j];
                        }

                        /// If value is N/A, do not count this review towards the criterion average.
                        if (isset($reviewCountForCriterion[$j]) && $ratings_array[$j] != 0 ) # recall 'na' == 0 equals true
                        {
                            $reviewCountForCriterion[$j]++;
                        }

                    }

                }

                # creates criteria averages.
                $ratings =
                    array_map(
                        create_function(
                            '$el, $revCount',
                            'return empty($revCount) ? "na" : number_format($el / $revCount, 4);'
                        ),
                        $sumRatings,
                        $reviewCountForCriterion
                    )
                ;

                $userRating = 'na';

                if ( $reviewCount > 0 ) # if there's at least one not-n/a rating somewhere!
                {
                    if ( $weighted )
                    {
                        # calculate sum of valid weights (=whose ratings aren't n/a)
                        $sumWeights =
                            array_sum(
                                array_intersect_key(
                                    $weights,
                                    array_filter(
                                        $sumRatings,
                                        create_function(
                                            '$el', 'return !empty($el) && $el != "na";' # both conditions must be checked so to include a case of only one review! (never mind, just don't change it..)
                                        )
                                    )
                                )
                            )
                        ;

                        if ( $sumWeights > 0 )
                        {
                            foreach ( $ratings as $k => $v )
                            {
                                $userRating += $v * $weights[$k] / $sumWeights;
                            }
                        }

                    } # if ( $weighted )

                    else
                    {
                        # calculate the average, count criteria averages without the n/a ones
                        $userRating =
                            array_sum($ratings)
                            /
                            count(
                                array_filter(
                                    $ratings,
                                    create_function(
                                        '$el', 'return !empty($el) && $el != "na";' # both conditions must be checked so to include a case of only one review! (never mind, just don't change it..)
                                    )
                                )
                            )
                        ;
                    }

                } # if ( $reviewCount > 0 )

                // populate saving array for jreviews_listing_totals table
                $data['Totals'] += array(
                    $reviewType.'_rating' => is_numeric($userRating) ? number_format($userRating, 4) : $userRating
                    ,$reviewType.'_rating_count' => $reviewCount
                    ,$reviewType.'_criteria_rating' => implode(',', $ratings)
                    ,$reviewType.'_criteria_rating_count' => implode(',', $reviewCountForCriterion)
                );

            } # if ($rows)

        } # foreach ( $reviewTypes as $reviewType )

        // ready to update database!

        if(!$this->replace('#__jreviews_listing_totals', 'Totals', $data, 'listing_id') ) {

            return false;
        }

        return true;
    }

    function processSorting($selected = null)
    {
        $order = '';

        switch ( $selected ) {
              case 'rdate':
                  $order = '`Review.created` DESC';
                  break;
              case 'date':
                  $order = '`Review.created` ASC';
                  break;
              case 'rating':
                  $order = '`Rating.average_rating` DESC, `Review.created` DESC';
                  break;
              case 'rrating':
                  $order = '`Rating.average_rating` ASC, `Review.created` DESC';
                  break;
              case 'helpful':
                  $order = 'Review.vote_helpful DESC, `Rating.average_rating` DESC';
                  break;
              case 'rhelpful':
                  $order = 'Review.vote_helpful ASC, `Rating.average_rating` DESC';
                  break;
                case 'discussed':
                    $order = 'Review.posts DESC, `Rating.average_rating` DESC';
                break;
                case 'updated':
                    $order = 'Review.modified DESC, Review.created DESC';
                break;
            default:
                $order = '`Review.created` DESC';
                 break;
        }

        return $order;
    }

    function publish($review_id, $include_reject_state = false)
    {
        $result = array('success'=>false,'state'=>null,'access'=>true);

        $review_id = (int) $review_id;

        if(!$review_id) return $result;

        # Load current listing publish state and author id
        $this->runProcessRatings = false;

        $review = $this->findRow(array('conditions'=>array('Review.id = ' . $review_id)));

        if($review)
        {
            # Check access
            $Access = Configure::read('JreviewsSystem.Access');

            if(!$Access->isManager())
            {
                $result['access'] = false;
                return $result;
            }

            $data['Review']['id'] = $review['Review']['review_id'];

            $data['Review']['mode'] = $review['Review']['extension'];

            $data['Review']['pid'] = $review['Review']['listing_id'];

            // Define toggle states
            if($include_reject_state) {

                if($review['Review']['published'] == 1) {

                    $data['Review']['published'] = $result['state'] = 0;
                }
                elseif($review['Review']['published'] == 0) {

                    $data['Review']['published'] = $result['state'] = -2;
                }
                elseif($review['Review']['published'] == -2) {

                    $data['Review']['published'] = $result['state'] = 1;
                }
            }
            else {

                $data['Review']['published'] = $result['state'] = (int)!$review['Review']['published'];
            }

            # Update state
            if($this->store($data))
            {
                // clear cache
                clearCache('', 'views');
                clearCache('', '__data');

                $result['success'] = true;
            }
        }

        return $result;
    }

    function updatePostCount($review_id,$value)
    {
        if($value != 0)
        {
            $query = "
                UPDATE
                    #__jreviews_comments AS Review
                SET
                    Review.posts = Review.posts " . ($value == 1 ? '+1' : '-1') . "
                WHERE
                    Review.id = ". (int) $review_id
            ;

			return $this->query($query);
        }
    }


    /**
     * Updates votes count in the relevant review and calls the user rank update method
     * Called by afterSave method of the votes model
     *
     * @param int $reviewId cleaned in the votes controller
     * @param int $voteYes cleaned in the votes controller
     * @return bool Notice that there is no error handling for this yet in s2
     */
    function updateVoteHelpfulCount($reviewId, $voteYes)
    {
        $query = "
            UPDATE
                #__jreviews_comments
            SET
                vote_helpful = vote_helpful + $voteYes,
                vote_total = vote_total + 1
            WHERE
                id = $reviewId
        ";

        if( !$this->query($query) ) return false;

        # get user id of the review
        $query = "SELECT userid FROM #__jreviews_comments WHERE id = $reviewId";

		$userId = $this->query($query, 'loadResult');

        # if not written by guest, trigger user rank update
        return $userId ? $this->updateUserRank($userId) : true;
    }

    /**
     * Update reviewer rank per user
     * Called by updateVoteHelpfulCount method and by admin maintenance functions
     *
     * @param array $userId the list of user id's to update
     * @return boolean
     */
    function updateUserRank($userIds)
    {
        $Config = Configure::read('JreviewsSystem.Config');

        is_array($userIds) or $userIds = (array) $userIds;

        foreach ( $userIds as $userId )
        {
            if ( ! $userId = (int) $userId )    continue;

            # get user ranks info

            $query = "
                SELECT
                     COUNT(*) AS reviews
                    ,SUM(vote_helpful) votes_helpful
                    ,SUM(vote_total) AS votes_total
                FROM
                    #__jreviews_comments
                WHERE
                        userid = $userId
                    AND published = 1
                    ".( $Config->editor_rank_exclude ? "AND author = 0" : "" )
            ; # Notice that COUNT(*) considered faster than COUNT(column)

            $user = $this->query($query, 'loadObject');

			empty($user->votes_total) and $user->votes_total = 0;

			$user->votes_percent_helpful = $user->votes_total ? $user->votes_helpful / $user->votes_total : 0;

            # insert the info into users table, without rank calculation
            $query = "
                REPLACE
                    #__jreviews_reviewer_ranks
                SET
                     user_id = $userId
                    ,reviews = $user->reviews
                    ,votes_percent_helpful = $user->votes_percent_helpful
                    ,votes_total = $user->votes_total
            ";

            if(!$this->query($query) ) {

                return false;
            }

            # demote everyone that is below this user's ranking

            $query = "
                UPDATE
                   #__jreviews_reviewer_ranks
                SET
                    rank = rank + 1
                WHERE
                    reviews < $user->reviews
                    OR
                    reviews = $user->reviews AND votes_percent_helpful < $user->votes_percent_helpful
                    OR
                    reviews = $user->reviews AND votes_percent_helpful = $user->votes_percent_helpful AND votes_total < $user->votes_total
            ";


            if(!$this->query($query)) {

                return false;
            }

            # update the user's current rank

            $query = "
                SELECT
                    COUNT(*)
                FROM
                   #__jreviews_reviewer_ranks
                WHERE
                    user_id != $userId
                    AND
                    (
                        reviews > $user->reviews
                        OR
                        reviews = $user->reviews AND votes_percent_helpful > $user->votes_percent_helpful
                        OR
                        reviews = $user->reviews AND votes_percent_helpful = $user->votes_percent_helpful AND votes_total >= $user->votes_total
                    )
            ";

            $rank = $this->query($query, 'loadResult') + 1;

            $query = "UPDATE #__jreviews_reviewer_ranks SET rank = $rank WHERE user_id = $userId";

            if(!$this->query($query)) {

                return false;
            }
        }

        return true;
    }

    /**
     * Rebuilds the user ranks table
     * Done by admin request or periodically
     *
     */
    function rebuildRanksTable()
    {
        if( ! isset($this->Config) )
        {
            S2App::import('Component','config','jreviews');
            $this->Config = ClassRegistry::getClass('ConfigComponent');
        }

        # clear the ranks table
        $query = "TRUNCATE TABLE #__jreviews_reviewer_ranks";

        if (!$this->query($query)) {

            return false;
        }

        # load all users that wrote reviews (and that admin didn't remove from the site)

        $query = "
            SELECT DISTINCT
                userid
            FROM
                #__jreviews_comments AS Review
            INNER JOIN
                #__users AS User ON User.id = Review.userid
        ";


		# if no users then nothing to do
        if ( ! $userIds = $this->query($query, 'loadColumn') )
        {
            return true;
        }

        # update all user ranks
        if ( ! $this->updateUserRank($userIds) )
        {
            return false;
        }

        # all is well
        appLogMessage('*******Reviewer ranks table rebuilt successfully at '.strftime(_CURRENT_SERVER_TIME_FORMAT, time()).' (unix time '.time().')', 'database');

        return true;
    }

    function save(&$data,$Access,$validFields = array())
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $User = cmsFramework::getUser();

        $userid = $User->id;

        $this->valid_fields = $validFields;

        $referrer = Sanitize::getString($data,'referrer'); // Comes from admin editing

        # Check if this is a new review or an updated review
        $review_id = isset($data['Review']) ? Sanitize::getInt($data['Review'],'id') : 0;

        $isNew = $review_id ? false : true;

        $output = array("success" =>false, "reviewid" => '', "author" => 0 );

        # If new then assign the logged in user info. Zero if it's a guest
        if ($isNew) {

            # Validation passed, so proceed with saving review to DB

            $data['Review']['ipaddress'] = s2GetIpAddress();

            $data['Review']['userid'] = $userid;

            $data['Review']['created'] = gmdate('Y-m-d H:i:s');
        }

        # Edited review
        if (!$isNew)
        {
            appLogMessage('*********Load current info because we are editing the review','database');

            // Load the review info
            $row = $this->findRow(
                array(
                    'fields'=>array('Rating.rating_id AS `Rating.rating_id`'),
                    'conditions'=>array('Review.id = ' . $review_id)),
                array() /* stop callbacks*/ );

            $data['ratings_col_empty'] = !Sanitize::getInt($row['Rating'],'rating_id') && Sanitize::getString($row['Rating'],'ratings','') == ''; // Used in afterFind

            // Capture ip address of reviewer
            if ( $userid == $row['User']['user_id']) {

                $data['Review']['ipaddress'] = s2GetIpAddress();
            }

            $referrer != 'moderation' && $data['Review']['modified'] = gmdate('Y-m-d H:i:s'); // Capture last modified date

            $data['Review']['author'] = $row['Review']['editor'];
        }

        # Complete user info for new reviews
        if ($isNew && $userid > 0)
        {
            $data['Review']['name'] = $User->name;

            $data['Review']['username'] = $User->username;

            $data['Review']['email'] = $User->email;
        }
        elseif(!$isNew && !$Access->isManager()) {

            unset($data['Review']['name']);

            unset($data['Review']['username']);

            unset($data['Review']['email']);
        }

        if(!defined('MVC_FRAMEWORK_ADMIN'))
        {
            $data['Review']['published'] = (int) ! (
                    ( $Access->moderateReview() && $isNew && !$data['Review']['author'] )
                ||    ( $Config->moderation_editor_reviews && $isNew && $data['Review']['author'] )
                ||    ( $Access->moderateReview() && $Config->moderation_review_edit && !$isNew && !$data['Review']['author'] )
                ||    ( $Access->moderateReview() && $Config->moderation_editor_review_edit && !$isNew && $data['Review']['author'] )
            );
        }

        # Get criteria info    to process ratings
        appLogMessage('*******Get criteria info to process ratings','database');

        $CriteriaModel = ClassRegistry::getClass('CriteriaModel');

        $criteria = $CriteriaModel->findRow(
            array(
                'conditions'=>array('Criteria.id = '. $data['Criteria']['id'])
            )
        );

        // Complete review info with $criteria info
        $data = array_insert($data,$criteria);

        $data['new'] = $isNew ? 1 : 0;

        # Save standard review fields
        appLogMessage('*******Save standard review fields','database');

        $save = $this->store($data);

        if(!$save) {

            appLogMessage('*******There was a problem saving the review fields','database');

        }
        else {

            $output['success'] = true;
        }

        return $output;
    }

    /**
    * Saves review ratings, fields and recalculates listing totals
    *
    * @param mixed $status
    */
    function afterSave($status)
    {
        $isNew = Sanitize::getBool($this->data,'new');

        $ratings_col_empty = Sanitize::getBool($this->data,'ratings_col_empty');

        $weights = '';

        clearCache('','__data');

        clearCache('','views');

        if(isset($this->data['Criteria']) && Sanitize::getInt($this->data['Criteria'],'state') == 1)
        {
            // Process rating data
            // to account for "n/a" values in the ratings and weights, changing the source arrays rather than the whole computation procedure.

            // init variables
            $applicableRatings = array_filter($this->data['Rating']['ratings'], create_function('$el', 'return is_numeric($el);'));
            $ratings_qty = count($applicableRatings);
            $this->data['average_rating'] = $ratings_sum = 'na';

            if ( $ratings_qty > 0 )
            {
                if (trim($this->data['Criteria']['weights'])!='')
                {
                    $weights = explode ("\n", $this->data['Criteria']['weights']);

                    // we have to remove the irrelevant weights so to produce clean weights_sum to be used later for proportion calculations
                    $sumWeights = array_sum(array_intersect_key($weights, $applicableRatings));

                    if ( $sumWeights > 0 )
                    {
                        foreach ($applicableRatings  as $key=>$rating)
                            {
                                $ratings_sum += $rating * $weights[$key] / $sumWeights;
                            }

                        $ratings_sum = $ratings_sum*$ratings_qty; // This is not the real sum, but it is divided again in the queries.
                    }

                } else {
                    $ratings_sum = array_sum($applicableRatings);
                }

                // Makes average rating easily available in Everywhere model afterSave method
                $this->data['average_rating'] = $ratings_sum / $ratings_qty;
                $this->data['Rating']['ratings_sum'] = $ratings_sum;
                $this->data['Rating']['ratings_qty'] = $ratings_qty;

            } # if ( $ratings_qty > 0  )i


            $this->data['Rating']['reviewid'] = $this->data['Review']['id'];
            $this->data['Rating']['ratings'] = implode(',',$this->data['Rating']['ratings']);

            # Save rating fields
            appLogMessage('*******Save standard rating fields','database');

            if($isNew || (!$isNew && $ratings_col_empty)) {

                $save = $this->insert( '#__jreviews_ratings', 'Rating', $this->data, 'reviewid');
            }
            else {

                $save = $this->update( '#__jreviews_ratings', 'Rating', $this->data, 'reviewid');
            }

            if(!$save) {

                return false;
            }

        } # if ( $criteria['Criteria']['state'] == 1 )

        // save listing totals
        if ( !$this->saveListingTotals($this->data['Review']['pid'], $this->data['Review']['mode'], $weights) )
        {
            return false;
        }

        # Save custom fields
        appLogMessage('*******Save review custom fields','database');

        $this->data['Field']['Review']['reviewid'] = $this->data['Review']['id'];

        S2App::import('Model','field','jreviews');

        $FieldModel = ClassRegistry::getClass('FieldModel');

        if(count($this->data['Field']['Review'])> 1 && !$FieldModel->save($this->data, 'review', $isNew, $this->valid_fields))
        {
            return false;
        }
    }

    function afterFind($results)
    {
        if (empty($results)) {
            return $results;
        }

        $sumRatings = array();

        # Add Community Builder info to results array
        if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
            $Community = ClassRegistry::getClass('CommunityModel');
            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

		# Add media info
		if($this->runAfterFindModel('Media') && class_exists('MediaModel'))
		{
            $Config = ClassRegistry::getClass('ConfigComponent');

			$Media = ClassRegistry::getClass('MediaModel');

			$results = $Media->addMedia(
				$results,
				'Review',
				'review_id',
				array(
					'sort'=>Sanitize::getString($Config,'media_general_default_order_listing'),
					'controller'=>Sanitize::getString($this,'controller_name'),
					'action'=>Sanitize::getString($this,'controller_action'),
					'photo_limit'=>Sanitize::getInt($Config,'media_review_photo_limit'),
					'video_limit'=>Sanitize::getInt($Config,'media_review_video_limit'),
					'attachment_limit'=>Sanitize::getInt($Config,'media_review_attachment_limit'),
					'audio_limit'=>Sanitize::getInt($Config,'media_review_audio_limit'),
				)
			);
		}

		if($this->runAfterFindModel('Field'))
		{
			# Add custom field info to results array
			S2App::import('Model','field','jreviews');
			$CustomFields = new FieldModel();
			$results = $CustomFields->addFields($results,'review');
		}

        # Preprocess criteria and rating information
        if($this->runProcessRatings) {
            $results = $this->processRatings($results);
        }

		$this->clearAllAfterFindModel();

        return $results;
    }

    /**
     * Pre-process criteria and rating information
     */
    function processRatings($results) {

        $single_row = false;

        foreach($results AS $key=>$result)
        {
            if(isset($results[$key]['Rating']) && is_string($results[$key]['Rating']['ratings'])) {

                // check if all is n/a. if this is not checked the average of a totally n/a review will be zero and not n/a. easier to do here than to create a complex query. cannot do == 0 check since 0 ratings may be implemented in the future.
                if ( strlen(trim($results[$key]['Rating']['ratings'], 'na,')) == 0 )
                {
                    $results[$key]['Rating']['average_rating'] = 'na';
                }

                $results[$key]['Rating']['ratings'] = explode(',',$results[$key]['Rating']['ratings']);
            }

            if(isset($result['Criteria']['criteria']) && $result['Criteria']['criteria'] != '' && is_string($result['Criteria']['criteria'])) {
                $results[$key]['Criteria']['criteria'] = explode("\n",$results[$key]['Criteria']['criteria']);
            }

            if(isset($result['Criteria']['tooltips']) && $result['Criteria']['tooltips'] != '' && is_string($result['Criteria']['tooltips'])) {
                $results[$key]['Criteria']['tooltips'] = explode("\n",$results[$key]['Criteria']['tooltips']);
            }

            # Calculate weighted average rating for each review
            if(
                isset($result['Criteria']['weights'])
                && $result['Criteria']['weights'] != ''
                && is_string($result['Criteria']['weights'])
                && !empty($results[$key]['Rating']['ratings']) // since could be comments without ratings
                && $results[$key]['Rating']['average_rating'] != 'na'
            ) {

                $results[$key]['Criteria']['weights'] = explode("\n",$results[$key]['Criteria']['weights']);

                $weighted_average = 0;

                $weights_sum = array_sum($results[$key]['Criteria']['weights']);

                if (round($weights_sum,0) == 100) {
                    // see function save() for explanations. basically this extracts the relevant weights (without N/A rates) and sums them.
                    $sumWeights =
                        array_sum(
                            array_intersect_key(
                                $results[$key]['Criteria']['weights'],
                                array_filter(
                                    $results[$key]['Rating']['ratings'],
                                    create_function(
                                        '$el', 'return is_numeric($el);'
                                    )
                                )
                            )
                        )
                    ;
                    if ( $sumWeights > 0 )
                    {
                        $i = 0;
                        while(isset($results[$key]['Rating']['ratings'][$i]))
                        {

                            $weighted_average += $results[$key]['Rating']['ratings'][$i] * $results[$key]['Criteria']['weights'][$i] / $sumWeights;

                            $i++;
                        }
                    }

                    $results[$key]['Rating']['average_rating'] = $weighted_average;
                }
            }
        }

        return $results;
    }

}
