<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
S2App::import('Helper','routes','jreviews');

class EverywhereComContentModel extends MyModel  {

	var $UI_name = 'Content';

	var $name = 'Listing';

	var $useTable = '#__content AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_content';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
        'Listing.alias AS `Listing.slug`',
        'Category.alias AS `Category.slug`',
		'Listing.title AS `Listing.title`',
		'Listing.introtext AS `Listing.summary`',
		'Listing.fulltext AS `Listing.description`',
		'Listing.images AS `Listing.images`',
		'Listing.hits AS `Listing.hits`',
		'Listing.catid AS `Listing.cat_id`',
		'Listing.created_by AS `Listing.user_id`',
		'Listing.created_by_alias AS `Listing.author_alias`',
		'Listing.created AS `Listing.created`',
        'Listing.modified AS `Listing.modified`',
		'Listing.access AS `Listing.access`',
		'Listing.state AS `Listing.state`',
		'Listing.publish_up AS `Listing.publish_up`',
		'Listing.publish_down AS `Listing.publish_down`',
		'Listing.metakey AS `Listing.metakey`',
		'Listing.metadesc AS `Listing.metadesc`',
		'\'com_content\' AS `Listing.extension`',
		'Category.id AS `Category.cat_id`',
		'Category.title AS `Category.title`',
        'cat_params'=>'Category.params AS `Category.params`', /* J16 */
		'Directory.id AS `Directory.dir_id`',
		'Directory.desc AS `Directory.title`',
		'Directory.title AS `Directory.slug`',
		'criteria'=>'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.title AS `Criteria.title`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',
		'Criteria.state AS `Criteria.state`',
        'Criteria.config AS `ListingType.config`',
		'`Field`.featured AS `Listing.featured`',
		'User.id AS `User.user_id`',
		'User.name AS `User.name`',
		'User.username AS `User.username`',
		'email'=>'User.email AS `User.email`',
        'Claim.approved AS `Claim.approved`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`',
		// Editor reviews
        'editor_rating'=>'Totals.editor_rating AS `Review.editor_rating`',
        'Totals.editor_rating_count AS `Review.editor_rating_count`',
        'Totals.editor_criteria_rating AS `Review.editor_criteria_rating`',
        'Totals.editor_criteria_rating_count AS `Review.editor_criteria_rating_count`',
        'Totals.editor_comment_count AS `Review.editor_review_count`',
		'Totals.media_count AS `Listing.media_count`',
		'Totals.video_count AS `Listing.video_count`',
		'Totals.photo_count AS `Listing.photo_count`',
		'Totals.audio_count AS `Listing.audio_count`',
        'Totals.attachment_count AS `Listing.attachment_count`',
        '(Totals.media_count - Totals.media_count_user) AS `Listing.media_count_owner`',
        '(Totals.video_count - Totals.video_count_user) AS `Listing.video_count_owner`',
        '(Totals.photo_count - Totals.photo_count_user) AS `Listing.photo_count_owner`',
        '(Totals.audio_count - Totals.audio_count_user) AS `Listing.audio_count_owner`',
        '(Totals.attachment_count - Totals.attachment_count_user) AS `Listing.attachment_count_owner`',
        'Totals.media_count_user AS `Listing.media_count_user`',
        'Totals.video_count_user AS `Listing.video_count_user`',
        'Totals.photo_count_user AS `Listing.photo_count_user`',
        'Totals.audio_count_user AS `Listing.audio_count_user`',
        'Totals.attachment_count_user AS `Listing.attachment_count_user`',
	);

	var $joins = array(
        'JreviewsCategory'=>"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
        'Category'=>        "LEFT JOIN #__categories AS Category ON JreviewsCategory.id = Category.id",
        'ParentCategory'=>  "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt",
		'Total'=>           "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_content'",
        'Field'=>           "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
		                    "LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id",
		                    "LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		'User'=>            "LEFT JOIN #__users AS User ON User.id = Listing.created_by",
        'Claim'=>           "LEFT JOIN #__jreviews_claims AS Claim ON Claim.listing_id = Listing.id AND Claim.user_id = Listing.created_by AND Claim.approved = 1"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'Listing' => 'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
		"LEFT JOIN #__categories AS Category ON Category.id = JreviewsCategory.id",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

	var $conditions = array();

	var $limit;

	var $offset;

	var $order = array();

    public static $joinListingState = array(
        'INNER JOIN #__content AS Listing ON Listing.id = %s AND Listing.state = 1'
        );

	function __construct()
    {
        parent::__construct();

		$this->tag = __t("Listing",true);  // Used in MyReviews page to differentiate from other component reviews

		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

        $this->Routes =  ClassRegistry::getClass('RoutesHelper');
	}

	public static function exists() {

        return (bool) file_exists(PATH_ROOT . 'components' . _DS . 'com_content' . _DS . 'content.php');
	}

	function listingUrl($listing)
    {
		return $this->Routes->content('',$listing,array('return_url'=>true,'sef'=>false));
	}

    // Used to check whether reviews can be posted by listing owners
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.created_by AS user_id, User.name, User.email
            FROM
                #__content AS Listing
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id
            WHERE
                Listing.id = " . (int) ($result_id);

		return current($this->query($query,'loadAssocList'));
    }


    function afterFind($results)
    {
        if (empty($results)){
            return $results;
        }

        // Read in media model to ignore the Listing Type overrides and also below
        // to ignore the listing title override
        $disable_overrides = Sanitize::getBool($this,'disable_overrides',false);

        S2App::import('Model',array('favorite','field','criteria'),'jreviews');

        # Add Menu ID info for each row (Itemid)

        $Menu = ClassRegistry::getClass('MenuModel');

		$results = $Menu->addMenuListing($results);

        # Add custom field info to results array

        if($this->runAfterFindModel('Field'))
        {
            $CustomFields = ClassRegistry::getClass('FieldModel');

            $results = $CustomFields->addFields($results,'listing');
        }

        # Reformat image and criteria info

        foreach ($results AS $key=>$listing)
        {
            // Check for guest user submissions
            if(isset($listing['User'])
                && ($listing['User']['user_id'] == 0
                || ($listing['User']['user_id'] == 62 && $listing['Listing']['author_alias']!='')))
            {
                $results[$key]['User']['name'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['username'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['user_id'] = 0;
            }

            // Remove plugin tags
            if(isset($results[$key]['Listing']['summary']) && Sanitize::getString($this,'controller')=='categories') { // Not in edit mode
                $regex = "#{[a-z0-9]*(.*?)}(.*?){/[a-z0-9]*}#s";
                $results[$key]['Listing']['summary'] = preg_replace( $regex, '', $results[$key]['Listing']['summary'] );
            }

             // Escape quotes in meta tags
            isset($listing['Listing']['metakey']) and $listing['Listing']['metakey'] = htmlspecialchars($listing['Listing']['metakey'],ENT_QUOTES,'UTF-8');
            isset($listing['Listing']['metadesc']) and $listing['Listing']['metadesc'] = htmlspecialchars($listing['Listing']['metadesc'],ENT_QUOTES,'UTF-8');

            # Config overrides
            if(isset($listing['ListingType'])) {
                $results[$key]['ListingType']['config'] = json_decode($listing['ListingType']['config'],true);
                if(isset($results[$key]['ListingType']['config']['relatedlistings'])) {
                    foreach($results[$key]['ListingType']['config']['relatedlistings'] AS $rel_key=>$rel_row) {
                        isset($rel_row['criteria']) and $results[$key]['ListingType']['config']['relatedlistings'][$rel_key]['criteria'] = implode(',',$rel_row['criteria']);
                    }
                }
            }

            $results[$key][$this->name]['url'] = $this->listingUrl($listing);

            if(isset($listing['Criteria']['criteria']) && $listing['Criteria']['criteria'] != '')
            {
                $results[$key]['Criteria']['criteria'] = explode("\n",$listing['Criteria']['criteria']);

                $results[$key]['Criteria']['required'] = explode("\n",$listing['Criteria']['required']);
                // every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
                if ( count($results[$key]['Criteria']['required']) != count($results[$key]['Criteria']['criteria']) )
                {
                    $results[$key]['Criteria']['required'] = array_fill(0, count($results[$key]['Criteria']['criteria']), 1);
                }
            }

            if(isset($listing['Criteria']['tooltips']) && $listing['Criteria']['tooltips'] != '') {
                $results[$key]['Criteria']['tooltips'] = explode("\n",$listing['Criteria']['tooltips']);
            }

            if(isset($listing['Criteria']['weights']) && $listing['Criteria']['weights'] != '') {
                $results[$key]['Criteria']['weights'] = explode("\n",$listing['Criteria']['weights']);
            }

            // Add detailed rating info
            // If $listing['Rating'] is already set we don't want to overwrite it because it's for individual reviews

            if(isset($listing['Review'])
                && !isset($listing['Rating'])
                && ($listing['Review']['user_rating_count'] > 0 || $listing['Review']['editor_rating_count'] >0)
                )
            {
                $results[$key]['Rating'] = array(
                        'average_rating' => $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_rating'] : $listing['Review']['editor_rating'],
                        'ratings' => explode(',', $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_criteria_rating'] : $listing['Review']['editor_criteria_rating']),
                        'criteria_rating_count' => explode(',', $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_criteria_rating_count'] : $listing['Review']['editor_criteria_rating_count'])
                    );
            }

            if(isset($listing['Review'])) {

                $results[$key]['Review']['review_count'] = Sanitize::getInt($listing['Review'],'review_count'); // Make sure it's zero if empty
            }

            // Override listing title

            if(isset($listing['ListingType']) && !$disable_overrides) {

                $listing = $results[$key];

                $page_title = Sanitize::getString($listing['ListingType']['config'],'type_metatitle');

                $override_listing_title = Sanitize::getInt($listing['ListingType']['config'],'override_listing_title',0);

                if(in_array(Sanitize::getString($this,'controller_name'),array('categories','com_content')) && $override_listing_title && $page_title != '') {

                    // Get and process all tags
                    $tags = self::extractTags($page_title);

                    $tags_array = array();

                    foreach($tags AS $tag)
                    {
                        switch($tag)
                        {
                            case 'title':
                                $tags_array['{title}'] = Sanitize::stripAll($listing['Listing'],'title');
                            break;
                            case 'directory':
                                $tags_array['{directory}'] = Sanitize::stripAll($listing['Directory'],'title');
                            break;
                            case 'category':
                                $tags_array['{category}'] = Sanitize::stripAll($listing['Category'],'title');
                            break;
                            default:
                                if(substr($tag,0,3) == 'jr_' && isset($listing['Field']))
                                {
                                    $fields = $listing['Field']['pairs'];

                                    $tags_array['{'.$tag.'}'] = isset($listing['Field']['pairs'][$tag]) && isset($fields[$tag]['text']) ? html_entity_decode(implode(", ", $fields[$tag]['text']),ENT_QUOTES,'utf-8') : '';
                                }
                            break;
                        }
                    }

                    $results[$key]['Listing']['title'] = trim(str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$page_title)));
                }
            }
        }

        if($this->runAfterFindModel('Favorite') && (!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0)) {

            # Add Favorite info to results array
            $Favorite = ClassRegistry::getClass('FavoriteModel');
            $Favorite->Config = &$this->Config;
            $results = $Favorite->addFavorite($results);
        }

        # Add Community info to results array
        if(isset($listing['User']) && !defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
            $Community = ClassRegistry::getClass('CommunityModel');
            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

		# Add media info
		if($this->runAfterFindModel('Media') && class_exists('MediaModel'))
		{
			$Media = ClassRegistry::getClass('MediaModel');

			if(!isset($this->Config)) {

                $Config = Configure::read('JreviewsSystem.Config');
			}
			else {

				$Config = & $this->Config;
			}

			$results = $Media->addMedia(
				$results,
				'Listing',
				'listing_id',
				array(
					'sort'=>Sanitize::getString($Config,'media_general_default_order_listing'),
					'extension'=>'com_content',
					'controller'=>Sanitize::getString($this,'controller_name'),
					'action'=>Sanitize::getString($this,'controller_action'),
					'photo_limit'=>Sanitize::getInt($Config,'media_detail_photo_limit'),
					'video_limit'=>Sanitize::getInt($Config,'media_detail_video_limit'),
					'attachment_limit'=>Sanitize::getInt($Config,'media_detail_attachment_limit'),
					'audio_limit'=>Sanitize::getInt($Config,'media_detail_audio_limit'),
                    'photo_layout'=>Sanitize::getString($Config,'media_detail_photo_layout'),
                    'video_layout'=>Sanitize::getString($Config,'media_detail_video_layout'),
                    'disable_overrides'=>Sanitize::getInt($this,'disable_overrides')
				)
			);

		}

		$this->clearAllAfterFindModel();

		return $results;
    }

    static function extractTags($text)
    {
        $pattern = '/{([a-z0-9_|]*)}/i';

        $matches = array();

        $result = preg_match_all( $pattern, $text, $matches );

        if( $result == false ) {
            return array();
        }

        return array_unique(array_values($matches[1]));
    }

	/**
	 * This can be used to add post save actions, like synching with another table
	 *
	 * @param array $model
	 */
    function afterSave($status)
    {
        clearCache('','__data');

        clearCache('','views');

        if(isset($this->name))
        {
            switch($this->name)
            {
                case 'Review':break;
                case 'Listing':break;
            }
        }
    }

    function processSorting($controller_action, $order)
    {
        $addCondition = false;

        # Order by custom field
        if (false !== (strpos($order,'jr_')))
        {
            $this->__orderByField($order);
        }
        else {
            # If special task, then set the correct ordering processed in urlToSqlOrderBy
            switch($controller_action)
            {
                case 'mylistings':
                case 'favorites':
                case 'category':
                case 'custom':
                    if ($order == '') {
                        $order = $this->Config->list_order_default;
                    }
                    break;
                case 'toprated':
                    $order = 'rating';
                    break;
                case 'topratededitor':
                    $order = 'editor_rating';
                    break;
                case 'mostreviews':
                    $order = 'reviews';
                    break;
                case 'latest':
                    $order = 'rdate';
                    break;
                case 'popular':
                    $order = 'rhits';
                    break;
                case 'featured':
                    $order = 'featured';
                    break;
                case 'updated':
                    $order = 'updated';
                    break;
                case 'search':
                case 'alphaindex':
                    // Nothing
                    break;
                case 'random':
                case 'featuredrandom':
                    $order = 'random';
                    break;
                case 'module':
                    $addCondition = true;
                break;
                default:
                    $order = $controller_action;
                break;
            }

            $this->order[] = $this->__urlToSqlOrderBy($order,$addCondition);

        }
	}

    function __orderByField($field)
    {
        $direction = 'ASC';

        if (false !== (strpos($field,'rjr_'))) {
            $field = substr($field,1);
            $direction = 'DESC';
        }

        $CustomFields = ClassRegistry::getClass('FieldModel');

        $queryData = array(
            'fields'=>array('Field.fieldid AS `Field.field_id`'),
            'conditions'=>array(
                'Field.name = "'.$field.'"',
//                    'Field.listsort = 1'
                )
        );

        $field_id = $CustomFields->findOne($queryData);

        if ($field_id)
        {
            $this->fields[] = 'Field.' . $field . ' AS `Field.' . $field . '`';
//            $this->fields[] = 'IF (Field.' .$field . ' IS NULL, IF(Field.' .$field . ' = "",1,0), 1) AS `Field.notnull`';
//            $this->order[] = '`Field.notnull` DESC';
//            $this->conditions[] = 'Field.' . $field . ' IS NOT NULL';
//            $this->conditions[] = 'Field.' . $field . '<> ""';
            $this->order[] = 'Field.' . $field . ' ' .$direction;
            $this->order[] = 'Listing.created DESC';
        }
    }

    function __urlToSqlOrderBy($sort, $addCondition = false)
    {
        $order = '';
        switch ( $sort )
        {
            case 'featured':
                $order = '`Listing.featured` DESC, Listing.created DESC';
            break;
            case 'editor_rating':
            case 'author_rating':
                $order = 'Totals.editor_rating DESC, Totals.editor_rating_count DESC';
                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';
            break;
            case 'reditor_rating':
                $order = 'Totals.editor_rating ASC, Totals.editor_rating_count DESC';
//                $this->useKey = array('Totals'=>'editor_rating,editor_rating_count'); // KEY HINT por improved performance
                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';
            break;
            case 'rating':
                $order = 'Totals.user_rating DESC, Totals.user_rating_count DESC';
                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';
            break;
            case 'rrating':
                $order = 'Totals.user_rating ASC, Totals.user_rating_count DESC';
//                $this->useKey = array('Total'=>'user_rating,user_rating_count'); // KEY HINT por improved performance
                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';
            break;
            case 'reviews':
                $order = 'Totals.user_comment_count DESC';
                $addCondition and $this->conditions[] = 'Totals.user_comment_count > 0';
            break;
            case 'date':
                $order = 'Listing.created';
                $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance
            break;
            case 'rdate':
                $order = 'Listing.created DESC';
                $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance
            break;
//			case 'alias':
//				$order = 'Listing.alias DESC';
//				break;
            case 'alpha':
                $order = 'Listing.title';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
            break;
            case 'ralpha':
                $order = 'Listing.title DESC';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
            break;
            case 'hits':
                $order = 'Listing.hits ASC';
                $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance
            break;
            case 'rhits':
                $order = 'Listing.hits DESC';
                $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance
            break;
            case 'order':
                $order = 'Listing.ordering';
                $this->useKey = array('Listing'=>'jr_ordering'); // KEY HINT por improved performance
            break;
            case 'author':
                if ($this->Config->name_choice == 'realname') {
                    $order = 'User.name, Listing.created';
                } else {
                    $order = 'User.username, Listing.created';
                }
            break;
            case 'rauthor':
                if ($this->Config->name_choice == 'realname') {
                $order = 'User.name DESC, Listing.created';
                } else {
                $order = 'User.username DESC, Listing.created';
                }
            break;
            case 'random':
                $order = 'RAND()';
            break;
            case 'updated':
                $order = 'Listing.modified DESC, Listing.created DESC';
                $this->useKey = array('Listing'=>'jr_modified,jr_created'); // KEY HINT por improved performance
            break;
            default:
                $order = 'Listing.title';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
                break;
        }
        return $order;
    }

    function del($ids = array())
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

		foreach($ids AS $id)
		{
            $success = false;

            $this->data['Listing']['id'] = $id;

			$this->plgBeforeDelete('Listing.id',$id); // Only works for single listing deletion

			# Delete associated media
			$Media = ClassRegistry::getClass('MediaModel');

			$Media->deleteByListingId($id, 'com_content');
		}

		$query = "
			DELETE
				Listing,
				Frontpage,
				Field,
				ListingTotal,
				Claim,
				ReportListing,
				Review,
				FieldReview,
				Rating,
				ReportReview,
				Vote,
				Discussion
			FROM
				#__content AS Listing
			LEFT JOIN
				#__content_frontpage AS Frontpage ON Frontpage.content_id = Listing.id
			LEFT JOIN
				#__jreviews_content AS Field ON Field.contentid = Listing.id
			LEFT JOIN
				#__jreviews_listing_totals AS ListingTotal ON ListingTotal.listing_id = Listing.id
			LEFT JOIN
				#__jreviews_claims AS Claim ON Claim.listing_id = Listing.id
			LEFT JOIN
				#__jreviews_reports AS ReportListing ON ReportListing.listing_id = Listing.id AND ReportListing.extension = 'com_content'
			LEFT JOIN
				#__jreviews_comments AS Review On Review.pid = Listing.id AND Review.mode = 'com_content'
			LEFT JOIN
				#__jreviews_review_fields AS FieldReview ON FieldReview.reviewid = Review.id
			LEFT JOIN
				#__jreviews_ratings AS Rating On Rating.reviewid = Review.id
			LEFT JOIN
				#__jreviews_reports AS ReportReview ON ReportReview.review_id = Review.id
			LEFT JOIN
				#__jreviews_votes AS Vote ON Vote.review_id = Review.id
			LEFT JOIN
				#__jreviews_discussions AS Discussion ON Discussion.review_id = Review.id
			WHERE
				Listing.id IN (".cleanIntegerCommaList($ids).")
		";

		if($this->query($query))
		{
            foreach($ids AS $id) {

                // Trigger plugin callback
                $this->data = $data = array('Listing'=>array('id'=>$id));

                $this->plgAfterDelete($data);

            }

			$success = true;

			// Clear cache
			cmsFramework::clearSessionVar('Listing', 'findCount');

			cmsFramework::clearSessionVar('Review', 'findCount');

			cmsFramework::clearSessionVar('Discussion', 'findCount');

			cmsFramework::clearSessionVar('Media', 'findCount');

			clearCache('', 'views');

			clearCache('', '__data');
		}

		return $success;
    }

    function feature($listing_id)
    {
        $listing_id = (int) $listing_id;

        $result = array('success'=>false,'state'=>null,'access'=>true);

        if(!$listing_id) return $result;

        # Check access
        $Access = Configure::read('JreviewsSystem.Access');

        if(!$Access->isManager())
        {
            $result['access'] = false;
            return $result;
        }

        # Load current listing featured state
        $query = "
            SELECT
                Listing.id, Field.featured AS state
            FROM
                #__content AS Listing
            LEFT JOIN
                #__jreviews_content AS Field ON Field.contentid = Listing.id
            WHERE
                Listing.id = " . $listing_id
        ;

        $listing = $this->query($query, 'loadAssocList');

        if($row = end($listing))
        {
            $new_state = $result['state'] = (int) !$row['state'];

            $query = "
                INSERT INTO
                    #__jreviews_content (contentid,featured)
                VALUES
                    ($listing_id,$new_state)
                ON DUPLICATE KEY UPDATE
                    featured = $new_state;
            ";

            if($this->query($query))
            {
                // Clear cache
                clearCache('', 'views');
                clearCache('', '__data');
                $result['success'] = true;
            }
        }

        return $result;
    }

    function publish($listing_id, $include_reject_state = false)
    {
        $result = array('success'=>false,'state'=>null,'access'=>true);

        $listing_id = (int) $listing_id;

        if(!$listing_id) return $result;

        # Load current listing publish state and author id

        $listing = $this->getListingById($listing_id);

        if($listing)
        {
            $user_id = $listing['Listing']['user_id'];

            $state = $listing['Listing']['state'];

            $overrides = $listing['ListingType']['config'];

            # Check access
            $Access = Configure::read('JreviewsSystem.Access');

            if(!$Access->canPublishListing($user_id, $overrides))
            {
                $result['access'] = false;

                return $result;
            }

            $data['Listing']['id'] = $listing_id;

            // Define toggle states
            if($include_reject_state) {

                if($state == 1) {

                    $data['Listing']['state'] = $result['state'] = 0;
                }
                elseif($state == 0) {

                    $data['Listing']['state'] = $result['state'] = -2;
                }
                elseif($state == -2) {

                    $data['Listing']['state'] = $result['state'] = 1;
                }
            }
            else {

                $data['Listing']['state'] = $result['state'] = (int)!$state;
            }

            # Update listing state
            if($this->store($data,false,array()))
            {
                // clear cache
                clearCache('', 'views');
                clearCache('', '__data');

                $result['success'] = true;
            }
        }

        return $result;
    }

    /**
    * Gets the most basic listing info to construct the urls for them
    *
    * @param mixed $id
    */
    function getListingById($id)
    {
        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');

        $fields = array(
            'Listing.id AS `Listing.listing_id`',
            'Listing.alias AS `Listing.slug`',
            'Listing.title AS `Listing.title`',
            'Listing.state AS `Listing.state`',
            'Listing.created_by AS `Listing.user_id`',
            'Listing.publish_down AS `Listing.publish_down`',
            'Listing.publish_up AS `Listing.publish_up`',
            '\'com_content\' AS `Listing.extension`',
            'Listing.catid AS `Listing.cat_id`',
            'Category.alias AS `Category.slug`',
            'Category.id AS `Category.cat_id`',
            'Category.title AS `Category.title`',
            'ListingType.config AS `ListingType.config`'
        );

        $query = "
            SELECT
                " . implode (",", $fields) . "
            FROM
                #__content AS Listing
            LEFT JOIN
                #__categories AS Category ON Category.id = Listing.catid
            LEFT JOIN
                #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id
            WHERE
                Listing.id IN (" . $this->Quote($id) . ")
        ";

        $listings = $this->__reformatArray($this->query($query, 'loadObjectList'));

        $listings = $this->changeKeys($listings,'Listing','listing_id');

        $listings = $Menu->addMenuListing($listings);

        foreach($listings AS $key=>$listing) {

            $listings[$key]['Listing']['url'] = $this->listingUrl($listing);

            $listings[$key]['ListingType']['config'] = json_decode($listings[$key]['ListingType']['config'],true);
        }

        return is_array($id) ? $listings : array_shift($listings);
    }

    /***********************************************************
     *                      ADMIN                              *
     ***********************************************************/

    function adminBrowseFilters($filters) {

        $filter_catid       = Sanitize::getInt($filters,'catid');

        $filter_authorid    = Sanitize::getInt($filters,'authorid');

        $filter_state        = Sanitize::getString($filters,'state');

        $title              = Sanitize::getString($filters,'title');

        $conditions        = array();

        $this->order = array('Listing.id DESC');

        // used by filter

        if($filter_catid > 0)
        {
            $conditions[] = "ParentCategory.id = " . $filter_catid;
        }
        else {

            unset($this->joins['ParentCategory']);
        }

        $filter_authorid > 0 and $conditions[] = "Listing.created_by = $filter_authorid";

        $title != '' and $conditions[] = "LOWER( Listing.title ) LIKE " . $this->QuoteLike($title);

        switch($filter_state)
        {
            case 'unpublished':
                $conditions[] = "Listing.state = 0";
            break;
            case 'featured':
                $conditions[] = "Field.featured = 1";
            break;
            case 'media_count':
                $conditions[] = "Totals.media_count > 0";
                break;
            case 'rejected':
                $conditions[] = "Listing.state = -2";
                break;
            default:
                // $conditions[] = "Listing.state >= 0";
            break;
        }

        return $conditions;
    }
}
