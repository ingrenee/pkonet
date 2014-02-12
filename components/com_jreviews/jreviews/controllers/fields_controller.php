<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldsController extends MyController {

    var $uses = array('menu','field','field_option');

    var $helpers = array();

    var $components = array('access','config');

    var $autoRender = false; //Output is returned

    var $autoLayout = false;

    function beforeFilter() {

        $this->Access->init($this->Config);

        parent::beforeFilter();
    }

    /**
    * Used for related listings field
    *
    */
    function relatedListings()
    {
        $id = Sanitize::getInt($this->params,'id');

        $listing_type = cleanIntegerCommaList(Sanitize::getString($this->params,'listingtype'));

        $valueq = Sanitize::getString($this->params,'value');

        $fname = Sanitize::getString($this->params,'fname');

        $conditions = $joins = array();

        if($valueq != '' || $id > 0)
        {
            $field = $this->Field->findRow(array('conditions'=>array("Field.name = " . $this->Quote($fname))));
            $owner_filter = Sanitize::getBool($field['Field']['_params'],'listing_type_owner',false);

            # Check owner filter and apply only if user is member and not in editor group or above
            if(!$this->Access->isEditor() && $owner_filter && $this->_user->id > 0) {
                $conditions[] = "Listing.created_by = " . $this->_user->id;
            }
			elseif($owner_filter && $this->_user->id == 0) {
				return cmsFramework::jsonResponse(array());
			}

            $valueq != '' and $conditions[] = "Listing.title LIKE " . $this->QuoteLike($valueq);
            $id > 0 and $conditions[] = "Listing.id  = " . $id;

            if($listing_type != '') {
                $conditions[] = "JreviewsCategory.criteriaid IN (". $listing_type . ")";
                $joins[] = "LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id";
            }

            $query = "
                SELECT
                    Listing.id as value, Listing.title AS label
                FROM
                    #__content AS Listing "
                . (!empty($joins) ? implode(" ", $joins) : '') . "
                WHERE
                    " . implode(' AND ', $conditions) . "
                ORDER BY Listing.title
                LIMIT 15
            ";

            $this->_db->setQuery($query);

            $rows = $this->_db->loadObjectList();

            return cmsFramework::jsonResponse($rows);
        }
    }

    function _loadValues()
    {
        $field_id = Sanitize::getString($this->params,'field_id');

        $valueq = Sanitize::getString($this->params,'value');

        if($field_id != '') {

            $field_options = $this->FieldOption->getControlList($field_id, $valueq);

            return cmsFramework::jsonResponse($field_options);
        }
    }

    /**
    * Returns a json object of field options used to dynamicaly show and populate dependent fields
    *
    */
    function _loadFieldData($json = true, $_data = array())
    {
        !empty($_data) and $this->data = $_data;

        $fields = $field_options = $selected_values = $group_ids = array();

        $selected_values_autocomplete = array();

        $dependent_fields = $dependent_groups = $control_fields = $fields = $responses = array();

        $location = strtolower(Sanitize::getString($this->data,'fieldLocation','content'));

        $location == 'listing' and $location = 'content';

        $recursive = Sanitize::getBool($this->data,'recursive');

        $field_names = Sanitize::getVar($this->data,'fields');

        $control_field = $field_names = is_array($field_names) ? array_filter($field_names) : array($field_names);

        $page_setup = Sanitize::getInt($this->data,'page_setup',false);

        $control_value = Sanitize::getVar($this->data,'value');

        $entry_id = Sanitize::getInt($this->data,'entry_id');

        $referrer = Sanitize::getString($this->data,'referrer');

        $autocomplete = Sanitize::getBool($this->data,'autocomplete');

        $edit = (bool) $entry_id || is_array($control_value); // In adv. search module we make it work like edit for previously searched values which are passed as an array in $control_value

        // Cached response for adv. search module requests
        if($json == true && $page_setup && $referrer == 'adv_search_module'){

            // Add the user access groups to the filename algorithm because not all fields are visible to all groups
            $this->data['aid'] = $this->Access->getAccessId();

            $cache_file = s2CacheKey('field_data',$this->data);

            if($cache = S2Cache::read($cache_file,'default')) {

                return cmsFramework::jsonResponse($cache);
            }
        }

        # Access check
        # Need to pass token to validate the listing id and check user access.

        # Filter passed field names to fix those with double underscores which are checkboxes and radiobuttons
        foreach($field_names AS $key=>$name)
        {
            if(substr_count($name, '_')>1)
            {
                $tmp = explode('_',$name); array_pop($tmp);
                $field_names[$key] = implode('_',$tmp);
            }
        }

        $field_names = array_unique($field_names);

        /**
        * We are in edit mode. Find selected values
        */
        if($page_setup && $entry_id > 0)
        {
            $PaidPlanCategoryModel = ClassRegistry::getClass('PaidPlanCategoryModel');

            # PaidListings integration
            if($location == 'content' && Configure::read('PaidListings.enabled') && $PaidPlanCategoryModel->isInPaidCategoryByListingId($entry_id))
            {
            // Load the paid_listing_fields table instead of the jos_content table so users can see all their
                // fields when editing a listing
                Configure::write('ListingEdit',false);

                $PaidListingFieldModel = ClassRegistry::getClass('PaidListingFieldModel');

                $curr_field_values = $PaidListingFieldModel->edit($entry_id);

                if($curr_field_values && !empty($curr_field_values)) {

                    $curr_field_values = (array) array_shift($curr_field_values);

                    $curr_field_values['contentid'] = $curr_field_values['element_id'];

                    unset($curr_field_values['element_id'], $curr_field_values['email']);
                }
            }

			if(empty($curr_field_values)) {

				$query = $location == 'content' ?
                    "SELECT * FROM #__jreviews_content WHERE contentid = {$entry_id}"
                    :
                    "SELECT * FROM #__jreviews_review_fields WHERE reviewid = {$entry_id}"
                ;

                $field_values = $this->Field->query($query,'loadAssocList');

                $curr_field_values = array_shift($field_values);
            }

            if(!empty($curr_field_values))
            {
                foreach($curr_field_values AS $key=>$val)
                {
                    if(substr($key,0,3) == 'jr_' && !strstr($val,' ') /*ignore text and textarea fields - better implementation would be to pass the input types in the ajax request*/)
                    {
                        $selected_values[$key] = $val != '' ? ( is_array($val) ? $val : explode('*',ltrim(rtrim($val,'*'),'*')) ) : array();
                    }
                }
            }

        } elseif (is_array($control_value)) {

            $selected_values = $control_value;

            $control_value = '';
        }

       /****************************************************************************************
       *  Control field option selected, so we find all dependent fields and groups
       *  Need to look in FieldOptions, Fields and FieldGroups
       ****************************************************************************************/
        if(!$page_setup)
        {
            # Find dependent FieldOptions
            $query = "
                SELECT
                    DISTINCT Field.name
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid AND (
                        Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    )
                LEFT JOIN
                    #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering, Field.ordering
            ";

            $field_names = $this->Field->query($query,'loadColumn');

            # Find dependent Fields
            $query = "
                SELECT
                    DISTINCT Field.name
                FROM
                    #__jreviews_fields AS Field
                LEFT JOIN
                    #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND Field.control_field = " . $this->Quote($control_field) . " AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering, Field.ordering
            ";

            $dep_field_names = $this->Field->query($query,'loadColumn');

            $field_names = is_array($field_names)
                            ?
                            array_merge($field_names,$dep_field_names)
                            :
                            $dep_field_names;

           # Find depedent Field Groups
           $query = "
                SELECT DISTINCT
                   FieldGroup.groupid
                FROM
                    #__jreviews_groups AS FieldGroup
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND FieldGroup.type = " . $this->Quote($location) . "
                    AND FieldGroup.control_field = ". $this->Quote($control_field) . "
                    AND FieldGroup.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering
           ";

           $group_ids = $this->Field->query($query,'loadColumn');

           !empty($field_names) and $field_names = array_unique($field_names);

           if(empty($field_names) && empty($group_ids)) return cmsFramework::jsonResponse(compact('control_field','dependent_fields','dependent_groups','data'));
        }

        # Get info for all fields
        $query = "
            SELECT
                Field.fieldid, Field.groupid, Field.title, Field.name, Field.type, Field.options, Field.control_field, Field.control_value, FieldGroup.name AS group_name
            FROM
                #__jreviews_fields AS Field
            LEFT JOIN
                #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND (
                    " . (!empty($field_names) ? "Field.name IN (" . $this->Quote($field_names) . ")" : '') . "
                    " . (!empty($field_names) && !empty($group_ids) ? " OR " : '') . "
                    " . (!empty($group_ids) ? "Field.groupid IN (" . $this->Quote($group_ids). ")" : '') . "
                )
            ORDER BY
                FieldGroup.ordering, Field.ordering
        ";

        $this->_db->setQuery($query);

        $curr_form_fields = $this->_db->loadAssocList('name');

        if(empty($curr_form_fields)) return cmsFramework::jsonResponse(compact('control_field','dependent_fields','dependent_groups','data'));

        foreach($curr_form_fields AS $key=>$curr_form_field) {

            $curr_form_fields[$key]['options'] = stringToArray($curr_form_field['options']);
        }


       /****************************************************************************************
       *  Check if fields have any dependents to avoid unnecessary ajax requests
       *  Three tables need to be checked: fieldoptions, fields, and fieldgroups
       ****************************************************************************************/
       # FieldOptions
       $query = "
            SELECT DISTINCT
                Field.name AS dependent_field, FieldOption.control_field
            FROM
                #__jreviews_fieldoptions AS FieldOption
            LEFT JOIN
                #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND FieldOption.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY Field.ordering
       ";

       $controlling_and_dependent_fields = $this->Field->query($query,'loadAssocList');

       # Fields
       $query = "
            SELECT DISTINCT
                Field.name AS dependent_field, Field.control_field
            FROM
                #__jreviews_fields AS Field
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND Field.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY Field.ordering
       ";

       $controlling_and_dependent_fields_Fields = $this->Field->query($query,'loadAssocList');

       $controlling_and_dependent_fields = is_array($controlling_and_dependent_fields)
                                            ?
                                            array_merge($controlling_and_dependent_fields,$controlling_and_dependent_fields_Fields)
                                            :
                                            $controlling_and_dependent_fields_Fields;

       # Groups
       $query = "
            SELECT DISTINCT
               FieldGroup.name AS dependent_group, FieldGroup.control_field
            FROM
                #__jreviews_groups AS FieldGroup
            LEFT JOIN
                #__jreviews_fields AS Field ON Field.groupid = FieldGroup.groupid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND FieldGroup.type = " . $this->Quote($location) . "
                AND FieldGroup.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND FieldGroup.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY
                FieldGroup.ordering
       ";

       $controlling_and_dependent_fields_Groups = $this->Field->query($query,'loadAssocList');

       $controlling_and_dependent_fields = is_array($controlling_and_dependent_fields)
                                            ?
                                            array_merge($controlling_and_dependent_fields,$controlling_and_dependent_fields_Groups)
                                            :
                                            $controlling_and_dependent_fields_Groups;

        #Extract controlling and dependent fields
        foreach($controlling_and_dependent_fields AS $row)
        {
            isset($row['dependent_field']) and $dependent_fields[$row['dependent_field']] = $row['dependent_field'];

            if(isset($row['dependent_group'])) {

                $group_name = str_replace(' ','',$row['dependent_group']);

                $dependent_groups[$group_name] = $group_name;
            }

            $control_fields[$row['control_field']] = $row['control_field'];
        }

        $ids_to_names = $ids_to_names_autocomplete = $ids_to_names_noautocomplete = array();

        $control_fields_array = array();

        foreach($curr_form_fields AS $curr_form_field)
        {

            if(in_array($referrer,array('adv_search'/*,'adv_search_module'*/)) &&
                $this->Config->search_field_conversion &&
                Sanitize::getInt($curr_form_field['options'],'autocomplete.search') == 0
                // && isset($value['optionList']) && !empty($value['optionList'])
                )
            {
                switch($curr_form_field['type']) {

                    case 'radiobuttons':
                        $curr_form_field['type'] = 'checkboxes';
                    break;

                    case 'select':
                        $curr_form_field['type'] = 'selectmultiple';
                    break;
                }
            }

            $ordering = Sanitize::getVar($curr_form_field['options'],'option_ordering',null);

            $fields[$curr_form_field['name']]['name'] = $curr_form_field['name'];

            $fields[$curr_form_field['name']]['type'] = $curr_form_field['type'];

            $fields[$curr_form_field['name']]['group'] = $curr_form_field['group_name'];

            if($autocomplete) {

                $fields[$curr_form_field['name']]['autocomplete'] = Sanitize::getVar($curr_form_field['options'],(in_array($referrer,array('adv_search','adv_search_module')) ? 'autocomplete.search' : 'autocomplete'), 0);

                $fields[$curr_form_field['name']]['autocompletetype'] = Sanitize::getVar($curr_form_field['options'],'autocomplete.option_type','link');

                $fields[$curr_form_field['name']]['autocompletepos'] = Sanitize::getVar($curr_form_field['options'],'autocomplete.option_pos','after');
            }
            else {

                $fields[$curr_form_field['name']]['autocomplete'] = 0;
            }

            $fields[$curr_form_field['name']]['title'] = $curr_form_field['title'];

            $entry_id and $fields[$curr_form_field['name']]['selected'] = array();

            !is_null($ordering) and $fields[$curr_form_field['name']]['order_by'] = !$ordering ? 'ordering' : 'text';

            // Add selected value for text fields
            if(isset($selected_values[$curr_form_field['name']])) {

                switch($fields[$curr_form_field['name']]['type'])
                {
                    case 'decimal':

                        if(Sanitize::getInt($curr_form_field['options'],'curr_format') && !empty($selected_values[$curr_form_field['name']])) {

                            $decimals = Sanitize::getInt($curr_form_field['options'],'decimals',2);

                            $fields[$curr_form_field['name']]['selected'][0] = round($selected_values[$curr_form_field['name']][0],$decimals);
                        }

                    break;
                    case 'date':
                        if(isset($selected_values[$curr_form_field['name']][0]))
                        {
                            if($selected_values[$curr_form_field['name']][0] == NULL_DATE) {
                                $fields[$curr_form_field['name']]['selected'] = array();
                            }
                            else {
                                $fields[$curr_form_field['name']]['selected'] = array(str_replace(" 00:00:00","",$selected_values[$curr_form_field['name']][0]));
                            }
                        }
                    break;
                    case 'relatedlisting':
                        if(isset($selected_values[$curr_form_field['name']][0]) && $selected_values[$curr_form_field['name']][0] > 0) {
                            $fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];
                        }
                    break;
					case 'radiobuttons':
					case 'select':
					case 'checkboxes':
					case 'selectmultiple':

						if(!empty($selected_values[$curr_form_field['name']])) {

							$selected_values[$curr_form_field['name']] = $selected_values[$curr_form_field['name']];

							$fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];
						}

						break;
                    default:

                        $fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];

                    break;
                }
            }

            // Add control related vars
            // If field is text type, then it has no control and we check the controlBy values
            if($fields[$curr_form_field['name']]['type'] == 'text') {
                $fields[$curr_form_field['name']]['control'] = false;
                $fields[$curr_form_field['name']]['controlled'] = $curr_form_field['control_field'] != '' && $curr_form_field['control_value'];
            }
            else {
                $fields[$curr_form_field['name']]['control'] = $recursive ? true : in_array($curr_form_field['name'],$control_fields);
                $fields[$curr_form_field['name']]['controlled'] = in_array($curr_form_field['name'],$dependent_fields);
            }

            if(in_array($curr_form_field['groupid'],$group_ids)) {
                $fields[$curr_form_field['name']]['controlgroup'] = true;
            }

            // Create an array of field ids to field names used below to save on additional queries.
            // The initial field option values are loaded for the fields in this array
            if(!$page_setup
                || !$fields[$curr_form_field['name']]['autocomplete']
//                || !in_array($referrer,array('listing','review')) // Pre-load list options for control fields in search forms
                || !empty($fields[$curr_form_field['name']]['selected']) // Pre-load list options when editing if the field has any selected options.
                ) {

                if(in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))) {
                    $ids_to_names[$curr_form_field['fieldid']] = $curr_form_field['name'];
                }

                if(!empty($fields[$curr_form_field['name']]['selected'])
                    && $fields[$curr_form_field['name']]['autocomplete']
                    && in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))
                    ) {
                        $ids_to_names_autocomplete[$curr_form_field['fieldid']] = $curr_form_field['name'];
                        $selected_values_autocomplete = array_merge($selected_values_autocomplete,$selected_values[$curr_form_field['name']]);
                }
                elseif(!$fields[$curr_form_field['name']]['autocomplete'] && in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))) {
                        $ids_to_names_noautocomplete[$curr_form_field['fieldid']] = $curr_form_field['name'];
                }
            }
            $control_fields_array[] = $curr_form_field['name'];
        }

//prx($ids_to_names);
//prx($ids_to_names_autocomplete);
//prx($ids_to_names_noautocomplete);

//prx('------------------BEGIN-------------------');
//prx($recursive);
//prx($curr_form_fields);
//prx($fields);
//prx($control_fields);
//prx('------------------END-------------------');

        /****************************************************************************************
       * Build the fields array for control and controlled fields
       ****************************************************************************************/
       # For FieldOption-FieldOption relationships get field options ordered by a-z ASC to start building the fields array.
        if(!empty($ids_to_names))
        {
            if($edit)
            {
                if(!empty($ids_to_names_autocomplete))
                {
                     $query = "
                        SELECT
                            Field.name, Field.fieldid, FieldOption.optionid, FieldOption.text, FieldOption.value, FieldOption.image, FieldOption.ordering
                        FROM
                            #__jreviews_fieldoptions AS FieldOption
                        LEFT JOIN
                            #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
                        WHERE
                            Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                            AND ". ($page_setup ?
                                    " FieldOption.fieldid IN (". $this->Quote(array_keys($ids_to_names_autocomplete)) .") "
                                    :
                                    '1 = 1'
                            ). "
                            ". ($page_setup ?
                                    " AND FieldOption.control_field = ''" // Load only control options = not dependent on other fields
                                    :
                                    " AND FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                            ). "
                            " . (!empty($selected_values_autocomplete) ?
                                    "AND FieldOption.value IN ( " . $this->Quote($selected_values_autocomplete). ")"
                                    :
                                    ''
                            ). "
                        ORDER BY
                            FieldOption.fieldid, FieldOption.text
                    ";

                    $field_options_ac = $this->Field->query($query,'loadAssocList');

                }

                if(!empty($ids_to_names_noautocomplete))
                {
                     $query = "
                        SELECT
                            Field.name, Field.fieldid, FieldOption.optionid, FieldOption.text, FieldOption.value, FieldOption.image, FieldOption.ordering
                        FROM
                            #__jreviews_fieldoptions AS FieldOption
                        LEFT JOIN
                            #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
                        WHERE
                            Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                            AND ". ($page_setup ?
                                    " FieldOption.fieldid IN (". $this->Quote(array_keys($ids_to_names_noautocomplete)) .") "
                                    :
                                    '1 = 1'
                            ). "
                            ". ($page_setup ?
                                    " AND FieldOption.control_field = ''" // Load only control options = not dependent on other fields
                                    :
                                    " AND FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                            ). "
                        ORDER BY
                            FieldOption.fieldid, FieldOption.text
                    ";

                    $field_options_noac = $this->Field->query($query,'loadAssocList');

                }

                empty($field_options_ac) and $field_options_ac = array();

                empty($field_options_noac) and $field_options_noac = array();

                $field_options = array_merge($field_options_ac,$field_options_noac);
            }
            else {

                 $query = "
                    SELECT
                        Field.name, Field.fieldid, FieldOption.optionid, FieldOption.text, FieldOption.value, FieldOption.image, FieldOption.ordering
                    FROM
                        #__jreviews_fieldoptions AS FieldOption
                    LEFT JOIN
                        #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
                    WHERE
                        Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                        AND ". ($page_setup ?
                                " FieldOption.fieldid IN (". $this->Quote(array_keys($ids_to_names)) .") "
                                :
                                '1 = 1'
                        ). "
                        ". ($page_setup ?
                                " AND FieldOption.control_field = ''" // Load only control options = not dependent on other fields
                                :
                                " AND FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                        ). "
                    ORDER BY
                        FieldOption.fieldid, FieldOption.text
                ";

                $field_options = $this->Field->query($query,'loadAssocList');

            }

        }

       # For FieldOption-Field relationships get field options ordered by a-z ASC to start building the fields array.
        if(!$page_setup /*&& empty($field_options) */&& !empty($ids_to_names)) {

            $query = "
                SELECT
                    Field.name, Field.fieldid, FieldOption.optionid, FieldOption.text, FieldOption.value, FieldOption.image, FieldOption.ordering
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND ". ($page_setup ?
                            " FieldOption.fieldid IN (". $this->Quote(array_keys($ids_to_names)) .") "
                            :
                            '1 = 1'
                    ). "
                    ". ($page_setup ?
                            " AND Field.control_field = ''" // Load only control options = not dependent on other fields
                            :
                            " AND Field.control_field = " . $this->Quote($control_field) ." AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                    ). "
                ORDER BY
                    FieldOption.fieldid, FieldOption.text
            ";

            $field_options_OptionToField = $this->Field->query($query,'loadAssocList');

            $field_options = array_merge($field_options,$field_options_OptionToField);
        }

        foreach ($field_options AS $field_option)
        {
            $field_id = $field_option['fieldid'];
            $field_name = $field_option['name'];
            unset($field_option['fieldid'],$field_option['name']);
            if(isset($ids_to_names[$field_id]))
            {
                $fields[$ids_to_names[$field_id]]['options'][] = $field_option;
                isset($selected_values[$field_name]) and $fields[$ids_to_names[$field_id]]['selected'] = $selected_values[$field_name];
            }
        }

        if($page_setup)
        {
            $control_field = array_values($control_fields_array);
            $dependent_fields = array();
        }
        else
        {
            $control_field = $control_field;
            $dependent_fields = array_values($dependent_fields);
        }

        # Edit mode: for each control field that has a selected value find dependent field options
        foreach($selected_values AS $key=>$val)
        {
            if(!empty($val) && $val != '' && in_array($key,$field_names))
            {
                foreach($val AS $selected)
                {
                    $res = $this->_loadFieldData(false,array('recursive'=>true,'fields'=>$key,'value'=>array_shift($val),'fieldLocation'=>$location));
                    if(is_array($res))
                    {
                        $responses[$res['control_field'][0]][$res['control_value']] = $res;
                        foreach($res['fields'] AS $res_fields)
                        {
                            if(isset($selected_values[$res_fields['name']]) && !empty($res_fields['options']) && empty($fields[$res_fields['name']]['options']))
                            {
                                $fields[$res_fields['name']] = $res_fields;
                                $fields[$res_fields['name']]['selected'] = $selected_values[$res_fields['name']];
                            }
                        }
                    }
                    elseif($fields[$key]['type'] != 'text') {
                        $responses[$key][$selected] = array(
                            'location'=>$location,
                            'control_field'=>array($key),
                            'control_value'=>$selected,
                            'dependent_groups'=>array(),
                            'dependent_fields'=>array(),
                            'fields'=>array()
                        );
                    }
                }
            }
        }

/** DEBUG **/
//if($json) {prx(compact('page_setup','control_field','control_value','dependent_fields','dependent_groups','fields','responses'));}
//if($json && !$page_setup) {prx(compact('page_setup','control_field','control_value','dependent_fields','dependent_groups','fields','responses'));}

        $dependent_groups = array_values($dependent_groups);

        $location = $location == 'content' ? 'Listing' : 'Review';

        if($json) {

            $response = compact('page_setup','edit','location','control_field','control_value','dependent_groups','dependent_fields','fields','responses');
        }

        if($json == true && $page_setup && $referrer == 'adv_search_module'){

            S2Cache::write($cache_file, $response, 'default');
        }

        return $json
            ?
            cmsFramework::jsonResponse($response)
            :
            compact('location','control_field','control_value','dependent_groups','dependent_fields','fields');
    }

 }
