<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldModel extends MyModel  {

    var $name = 'Field';

    var $useTable = '#__jreviews_fields AS `Field`';

    var $primaryKey = 'Field.fieldid';

    var $realKey = 'fieldid';

    var $fieldOptions;

    var $fields = array(
        'Field.fieldid AS `Field.fieldid`',
        'Field.name AS `Field.name`',
        'Field.title AS `Field.title`',
        'Field.type AS `Field.type`',
        'Field.required AS `Field.required`',
        'Field.access AS `Field.access`',
        'Field.access_view AS `Field.access_view`',
        'Field.options AS `Field.options`',
        'Field.showtitle AS `Field.showtitle`',
        'Field.description AS `Field.description`',
        'Field.groupid AS `Field.groupid`',
        'Field.location AS `Field.location`',
        'Field.size AS `Field.size`',
        'Field.maxlength AS `Field.maxlength`',
        'Field.cols AS `Field.cols`',
        'Field.rows AS `Field.rows`',
        'Field.contentview AS `Field.contentview`',
        'Field.ordering AS `Field.ordering`',
        'Field.listview AS `Field.listview`',
        'Field.compareview AS `Field.compareview`',
        'Field.listsort AS `Field.listsort`',
        'Field.search AS `Field.search`',
        'Field.published AS `Field.published`',
        'Field.control_field AS `Field.control_field`',
        'Field.control_value AS `Field.control_value`'
    );

    function afterFind($results)
    {
        if(!is_array($results)) {
            return $results;
        }

        foreach($results AS $key=>$result)
        {
            # Convert field options into _params array
            if(!isset($result['Field']['options'])) continue;
            $results[$key]['Field']['_params']  = stringToArray($result['Field']['options']);

            # Poocess control field values
            $results[$key]['ControlValues'] = array();
            if(isset($result['Field']['control_value']) && $result['Field']['control_value'] != '')
            {
                $results[$key]['Field']['control_value'] = explode('*',rtrim(ltrim($result['Field']['control_value'],'*'),'*'));

                $query = "
                    SELECT
                        Field.fieldid,value,text
                    FROM
                        #__jreviews_fieldoptions AS FieldOption
                    LEFT JOIN
                        #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid
                    WHERE
                        Field.name = " . $this->Quote($result['Field']['control_field']) . "
                         AND FieldOption.value IN (". $this->Quote($results[$key]['Field']['control_value']) .")"
                ;

				$results[$key]['ControlValues'] = $this->query($query, 'loadAssocList');
            }
        }
        return $results;
    }

    /***********************************************************************
    * Process control data when creating/editing field via administration
    * @param mixed $data
    ***********************************************************************/
    function beforeSave(&$data)
    {
        // Convert Control Value array to string
        if(isset($data['Field']['control_value']))
        {
            $control_value = Sanitize::getVar($data['Field'],'control_value');
            $data['Field']['control_value'] = !empty($control_value) ? '*'.implode('*',$control_value).'*' : '';
        }
        else {
            $data['Field']['control_field'] = '';
        }
    }

    function getList($filters, $limitstart, $limit, &$total)
    {
        $type = Sanitize::getString($filters,'type');

        $group_id = Sanitize::getInt($filters,'group_id');

        $location = Sanitize::getString($filters,'location');

        $title = Sanitize::getString($filters,'title');

        // get the total number of records
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_fields AS Field
            INNER JOIN
                #__jreviews_groups AS `Group` ON `Group`.groupid = Field.groupid
            WHERE
                Field.location = " . $this->Quote($location)
            . ($group_id > 0 ? " AND Field.groupid = $group_id" : '')
            . ($type != '' ? " AND Field.type = " . $this->Quote($type) : '')
            . ($title != '' ? " AND Field.title LIKE " . $this->QuoteLike($title) : '')
        ;

        $total = $this->query($query, 'loadResult');

        $query = "
            SELECT
                Field.fieldid, Field.groupid, Field.name, Field.title, Field.showtitle, Field.required, Field.type,
                Field.ordering, Field.contentview, Field.listview, Field.listsort, Field.compareview, Field.search,
                Field.published, Field.metatitle, Field.metakey, Field.metadesc, `Group`.name AS `group`
            FROM
                #__jreviews_fields AS Field
            INNER
                JOIN #__jreviews_groups AS `Group` on `Group`.groupid = Field.groupid
            WHERE
                Field.location = " . $this->Quote($location)
                . ($group_id > 0 ? " AND Field.groupid = " . $group_id : '')
                . (!empty($type) ? " AND Field.type = " . $this->Quote($type) : '')
                . (!empty($title) != '' ? " AND Field.title LIKE " . $this->QuoteLike($title) : '') . "
            ORDER
                BY Field.groupid, Field.ordering
            LIMIT
                {$limitstart},{$limit}"
        ;

        return $this->query($query, 'loadObjectList');
    }

    /**
    * Returns an array of fields that can be used as control fields in forms
    */
    function getControlList($location, $fieldq = '', $fieldid = false)
    {
        if($fieldid > 0 && $fieldq != '') $fieldid = false;

        $query = "
            SELECT
                fieldid AS id, name AS value, CONCAT(name,' ( ',title,' )') AS label
            FROM
                #__jreviews_fields
            WHERE
                location = " . $this->Quote($location) . "
                " .
                ($fieldq != '' ?
                    " AND (name LIKE " . $this->QuoteLike($fieldq) . ' OR title LIKE ' . $this->QuoteLike($fieldq) . ')' : ''
                ) .
                ($fieldid && $fieldid != 'undefined' ?
                    " AND fieldid <> {$fieldid}" : ''
                ) . "
                AND type IN ('select','selectmultiple','checkboxes','radiobuttons')
            ORDER BY title
            LIMIT 15
        ";

        return $this->query($query, 'loadObjectList');
    }

    /**
     * Used for deleting of new fields in administration
     */
    function deleteTableColumn($fields, $location) {

        $output = array();

        if ($location == 'content') {
            foreach ($fields as $field)
            {
                if($field['type'] == 'banner') continue;

				$query = "ALTER TABLE #__jreviews_content DROP " . $field['name'];

                if (!$this->query($query))
				{

					$output[] = "It was not possible to delete the field " . $field['name'] . " from the #__jreviews_content table.";

				}
            }
        }
		elseif ($location == 'review' ) {

            foreach ($fields as $field)
            {
                if($field['type'] == 'banner') continue;


				$query = "ALTER TABLE #__jreviews_review_fields DROP ". $field['name'];

				if (!$this->query($query))
				{

					$output[] = "It was not possible to delete the field " . $field['name'] . " from the #__jreviews_review_fields table.";

				}
            }
        }

        if (count($output)>0) return implode("\n",$output); else return true;
    }

    /**
     * Used for creation of new fields in administration
     */
    function addTableColumn ($field, $location)
	{
        $output = $dbtype = '';

		$null = 'NOT NULL';

		$name = $field['name'];

		$type = $field['type'];

		$maxlength = Sanitize::getInt($field,'maxlength',255);

		$maxlength == 0 and $maxlength = 255;

        switch ($type) {
            case 'text':
                $dbtype = "VARCHAR($maxlength)";
                break;

			case 'website':
			case 'email':
                $dbtype = "VARCHAR(255)";
				break;

			case 'select':
			case 'radiobutton':
			case 'selectmultiple':
			case 'checkboxes':

                $db_version = explode('.',$this->_db->getVersion());

                if($db_version[0] >= 5 && $db_version[1] >= 0 && $db_version[2] >= 3) {
                    $dbtype = "VARCHAR($maxlength)";
                }
                else {
                    $dbtype = "TEXT";
                }

				break;

			case 'textarea':
			case 'code':
                $dbtype = "TEXT";
                break;

            case 'integer':
            case 'relatedlisting':
                $dbtype = "INT(13)";
                $null = '';
                break;

			case 'decimal':
                $dbtype = "DECIMAL(20,7)";
                $null = '';
                break;

			case 'date':
                $dbtype = "DATETIME";
                break;

			case 'banner':
                // This is not an input fields, just output
            break;

		default:
                $dbtype = "VARCHAR(255)";
            break;
        }

        if($dbtype == '') return '';

        if ($location == 'content')
		{
            $query = "ALTER TABLE #__jreviews_content ADD $name $dbtype " . $null;
        }
		elseif ($location == 'review') {

			$query = "ALTER TABLE #__jreviews_review_fields ADD $name $dbtype " . $null;

		}

        if (!$this->query($query))
		{

			$output = "It was not possible to add the new field to the #__jreviews_content table so we could not create the field.";

		}

        return $output;

    }

    function modifyTableColumn($field, $maxlength = 255)
    {
        $old_maxlength = $field['Field']['maxlength'];

        if($old_maxlength == $maxlength) return true;

        $table = $field['Field']['location'] == 'content' ? '#__jreviews_content' : '#__jreviews_review_fields';

        $fname = $field['Field']['name'];

        $query = "
            ALTER TABLE $table CHANGE COLUMN {$fname} {$fname} VARCHAR({$maxlength}) NOT NULL;
        ";

        if($this->query($query,'query')) {

            $field['Field']['maxlength'] = $maxlength;

            if($this->store($field)) {

                return true;
            }
        }

        return false;
    }

    function getMaxDataLength($fname, $location)
    {
        $table = $location == 'content' ? '#__jreviews_content' : '#__jreviews_review_fields';

        $query = "
            SELECT MAX(CHAR_LENGTH(" . $fname . ")) FROM {$table}
        ";

        $maxlength = $this->query($query,'loadResult');

        return $maxlength;
    }

    function tableIntegrityCheck($location) {

        $dbprefix = cmsFramework::getConfig('dbprefix');

        $table = $location == 'content' ? $dbprefix.'jreviews_content' : $dbprefix.'jreviews_review_fields';

        $query = "
            SELECT
                count(name)
              FROM
                #__jreviews_fields
              WHERE
                location = '{$location}' AND type <> 'banner' AND name NOT IN (
                    SELECT
                        column_name
                    FROM
                        information_schema.columns
                    WHERE
                        table_name = '{$table}'
                )
        ";

        $count = $this->query($query,'loadResult');

        return $count == 0 ? true : false;
    }

    ################## CHECK WHICH FUNCTIONS STAY AND WHICH GO

    function _category2Criteria($cat_ids) {

        $out = array();

        if (is_array($cat_ids))
        {
            $cat_ids = implode(',',$cat_ids);
        }

        $query = "SELECT criteriaid FROM #__jreviews_categories"
        . "\n WHERE `option` = 'com_content'"
        . ($cat_ids != '' ? "\n AND id IN ($cat_ids)" : '')
        ;

        $criteria_ids = $this->query($query, 'loadObjectList');

        if(is_array($criteria_ids))
        {
            foreach($criteria_ids AS $criteria) {
                $out[] = $criteria->criteriaid;
            }
        }

        return array_unique($out);
    }

    function _criteria2Groups($criteria_ids,$type)
    {

        if (is_array($criteria_ids) && isset($criteria_ids[0]) && $criteria_ids[0] != '')
        {
            $criteria_ids = array_unique($criteria_ids);

        } elseif (!is_array($criteria_ids) && $criteria_ids != '') {

            $criteria_ids = array($criteria_ids);

        } else {

            $this->group_ids = array();

            return array();

        }

        //build group_ids array to get the list of fields and field values for the page
        $Criteria = ClassRegistry::getClass('CriteriaModel');

        $group_ids = $Criteria->findAll(
            array(
                'fields'=>array('Criteria.groupid'),
                'conditions'=>array('Criteria.id IN ('.implode(',',$criteria_ids).')')
            )
        );

        if (!$group_ids || empty($group_ids)) {
            $this->group_ids = array();
            return array();
        }

        $group_group_ids = array();

        foreach ($group_ids AS $group_id) {
            if ($group_id['Criteria']['groupid'] != '')
                $group_group_ids[] = $group_id['Criteria']['groupid'];
        }

        if(!empty($group_group_ids))
        {
            if($type == 'listing') {
                $type = 'content';
            }

            //now leave only the group ids for the current type
            $query = "SELECT groupid FROM #__jreviews_groups"
            . "\n WHERE groupid IN (".implode(',',$group_group_ids).")"
            . "\n AND type = '$type'"
            ;

            $this->group_ids = $this->query($query, 'loadColumn');

        }
		else {

            $this->group_ids = array();

        }

        return $this->group_ids;

    }

    function _extractFieldIds($fields, $field_types = array()) {

        $field_ids = array();

        foreach ($fields AS $field)
        {
            $field = (array) $field;

            if(in_array($field['Field.type'],$field_types) || empty($field_types))
            {
                $field_ids[] = $field['Field.id'];
            }
        }

        return $field_ids;

    }

    function _getFieldOptions($optionValues = null)
    {
        if(!empty($this->field_ids))
        {

            # Get the field options for all multiple choice fields ordered by a-z ASC
            $query = "
                SELECT
                    FieldValue.optionid, FieldValue.fieldid, FieldValue.text, FieldValue.value, FieldValue.image, FieldValue.ordering
                FROM
                    #__jreviews_fieldoptions AS FieldValue
                WHERE
                    FieldValue.fieldid IN (".implode(',',$this->field_ids).")
                    " . ($optionValues ?
                        "AND FieldValue.value IN (" . $this->Quote($optionValues) . ")"
                        :
                        '')
                     . "
                ORDER
                    BY FieldValue.text ASC"
            ;

			$rows = $this->query($query, 'loadObjectList');

            $fieldValues = array();

			$fieldOrdering = array();

            foreach ($rows AS $row)
            {
                $this->field_options_alpha[$row->fieldid][$row->value] =
                    array(
                        'optionid'=>$row->optionid,
                        'value'=>$row->value,
                        'text'=>$row->text,
                        'image'=>$row->image,
                        'ordering'=>$row->ordering
                    );

                $this->field_optionsList_alpha[$row->fieldid][$row->value] = $row->text;
            }

            if(!empty($optionValues) && isset($this->field_options_alpha)) {
                $this->field_options_ordering = $this->field_options_alpha;
                $this->field_optionsList_ordering = $this->field_optionsList_alpha;
            }

            # Get the field options for all multiple choice fields ordered by ordering
            $query = "
                SELECT
                    FieldValue.optionid, FieldValue.fieldid, FieldValue.text, FieldValue.value, FieldValue.image, FieldValue.ordering
                FROM
                    #__jreviews_fieldoptions AS FieldValue
                WHERE
                    FieldValue.fieldid IN (".implode(',',$this->field_ids).")
                    " . ($optionValues ?
                        "AND FieldValue.value IN (" . $this->Quote($optionValues) . ")"
                        :
                        '')
                     . "
                ORDER
                    BY FieldValue.ordering ASC"
            ;

			$rows = $this->query($query, 'loadObjectList');

            $fieldValues = array();

			$fieldOrdering = array();

            foreach ($rows AS $row)
            {
                $this->field_options_ordering[$row->fieldid][$row->value] =
                    array(
                        'optionid'=>$row->optionid,
                        'value'=>$row->value,
                        'text'=>$row->text,
                        'image'=>$row->image,
                        'ordering'=>$row->ordering
                    );

                $this->field_optionsList_ordering[$row->fieldid][$row->value] = $row->text;
            }
        }
    }

    function addFields($entries, $type)
    {
        $this->getFieldsArray($entries, $type);

        switch($type) {
            case 'listing':
                    $field_key = 'listing_id';
                break;
            case 'review':
                    $field_key = 'review_id';
                break;
        }

        foreach($entries AS $key=>$value)
        {
            if(isset($this->custom_fields[$value[inflector::camelize($type)][$field_key]]))
            {
                $entries[$key]['Field']['groups'] = $this->custom_fields[$value[inflector::camelize($type)][$field_key]];

                $entries[$key]['Field']['pairs'] = $this->field_pairs[$value[inflector::camelize($type)][$field_key]];
            }
            else {

                $entries[$key]['Field']['groups'] = '';

                $entries[$key]['Field']['pairs'] = '';
            }

        }
        return $entries;
    }

    /**
     * Creates the custom field group array with group info and fields values and attributes
     *
     * @param array $entries Entry array must have keys for entry id and criteriaid
     */
    function getFieldsArray($elements, $type = 'listing')
    {
        $fields = array();
        $field_pairs = array();
        $element_ids = array();
        $fieldValues = array();
        $rows = array();
        $this->criteria_ids = array(); // Alejandro = for discussion functionality

        //build entry_ids and criteria_ids array
        switch($type) {

            case 'listing':
                foreach ($elements AS $key=>$element) {
                    if(isset($element['Criteria']))
                    {
                        $element_ids[] = $element[inflector::camelize($type)]['listing_id'];
                        if($element['Criteria']['criteria_id']!='')
                            $this->criteria_ids[] = $element['Criteria']['criteria_id'];
                    }
                }
                break;
            case 'review':
                   foreach ($elements AS $element) {
                    if(isset($element['Criteria']))
                    {
                        $element_ids[] = $element[inflector::camelize($type)]['review_id'];
                        if($element['Criteria']['criteria_id']!='')
                            $this->criteria_ids[] = $element['Criteria']['criteria_id'];
                    }
                }
                break;
        }

        $this->group_ids = $this->_criteria2Groups($this->criteria_ids, $type);

        $criteria_ids = implode(',',$this->criteria_ids);

        $element_ids = implode(',',array_unique($element_ids));

        if (empty($this->group_ids)){

            return;
        }

        $group_ids = implode(',',$this->group_ids);

        $field_type = $type == 'listing' ? 'content' : $type;

        // Get field attributes and field values
        $query = "SELECT Field.fieldid AS `Field.field_id`, Field.groupid AS `Field.group_id`, Field.name AS `Field.name`, Field.title AS `Field.title`,"
        . "\n Field.showtitle AS `Field.showTitle`, Field.description AS `Field.description`, Field.required AS `Field.required`,"
        . "\n Field.type AS `Field.type`, Field.location AS `Field.location`, Field.options AS `Field.params`,"
        . "\n Field.contentview AS `Field.contentView`, Field.listview AS `Field.listView`, Field.compareview AS `Field.compareView`, Field.listsort AS `Field.listSort`,"
        . "\n Field.search AS `Field.search`, Field.access AS `Field.access`, Field.access_view AS `Field.accessView`,"
        . "\n Field.published As `Field.published`,"
        . "\n `Group`.groupid AS `Group.group_id`, `Group`.title AS `Group.title`, `Group`.name AS `Group.name`, `Group`.showtitle AS `Group.showTitle`"
        . "\n FROM #__jreviews_fields AS Field"
        . "\n INNER JOIN #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND "
        . "\n `Group`.groupid IN ($group_ids) AND `Group`.type =  '$field_type' )"
        . "\n WHERE Field.location = '$field_type' AND Field.published = 1"
        . "\n ORDER BY Group.ordering, Field.ordering"
        ;


        $rows = $this->query($query, 'loadObjectList', 'Field.name');

        if (!$rows || empty($rows)) {
            return;
        }

        # Extract list of field names from array
        $fieldNames = $optionFieldNames = $nonInputFieldNames = $fieldNamesByType = $fieldRows = array();

		$optionFields = array('selectmultiple','checkboxes','select','radiobuttons');

		$nonInputFields = array('banner');

       foreach($rows AS $key=>$row)
        {
            // Exclude non-input fields, like banner, from forms
            if(!in_array($row->{'Field.type'},$nonInputFields)) {
                $fieldNames[] = $row->{'Field.name'};
            } else {
               $row->{'Field.search'} = 0;
               $nonInputFieldNames[] = $row->{'Field.name'};
            }

            $fieldIds[$row->{'Field.name'}] = $row->{'Field.field_id'};

            $fieldRows[$key] = (array) $row;

            if(in_array($row->{'Field.type'},$optionFields))
            {
                $optionFieldNames[$row->{'Field.name'}] = $row->{'Field.field_id'}; // Used to find the option text for each option value
            }
        }

        # Get field values from current element ids
        switch($type)
        {
            case 'listing':
                # PaidListings integration
                if(Configure::read('ListingEdit') && Configure::read('PaidListings.enabled') && is_int($element_ids))
                {   // Load the paid_listing_fields table instead of the jos_content table so users can see all their
                    // fields when editing a listing
                    Configure::write('ListingEdit',false);

					$fieldValues = PaidListingFieldModel::edit($element_ids);

					if($fieldValues) break;

				}

                if(!empty($fieldNames)) {

                    $query = "SELECT Listing.contentid AS element_id," . implode(',',$fieldNames)
                    . "\n FROM #__jreviews_content AS Listing"
                    . "\n WHERE Listing.contentid IN (" . $element_ids . ")"
                    ;

    				$fieldValues = $this->query($query, 'loadObjectList', 'element_id');
                }

			break;
            case 'review':

                if(!empty($fieldNames)) {

    				$query = "SELECT Review.reviewid AS element_id," . implode(',',$fieldNames)
                    . "\n FROM #__jreviews_review_fields AS Review"
                    . "\n WHERE Review.reviewid IN (" . $element_ids . ")"
                    ;

    				$fieldValues = $this->query($query, 'loadObjectList', 'element_id');
                }

			break;
        }

// prx($optionFieldNames);
// prx($fieldValues);

        # Now for each option field add array of selected value,text,images
        $elementFields = array();

        $relatedListingIds = array();

        if(!empty($fieldValues))
        {
            foreach($fieldValues AS $fieldValue)
            {
                $fieldValue = array_filter((array) $fieldValue);

                $fieldOptionValuesTemp = array_intersect_key($fieldValue,$optionFieldNames);

                foreach($fieldOptionValuesTemp AS $fname=>$optionval)
                {
                    $values = !is_array($optionval) ? explode('*',$optionval) : $optionval;

					foreach($values AS $optionval)
                    {
                        if($optionval!='') {
							$fieldOptionValuesToSearch[$optionval] = $optionval;
							$fieldOptionFieldIdsToSearch[$optionFieldNames[$fname]] = $optionFieldNames[$fname];
						}
                    }
                }
            }

			if(!empty($fieldOptionValuesToSearch))
            {
                $query = "
                    SELECT
                        *
                    FROM
                        #__jreviews_fieldoptions
                    WHERE
                        fieldid IN ( " . $this->Quote($fieldOptionFieldIdsToSearch) .")
                        AND
                        value IN ( " . $this->Quote($fieldOptionValuesToSearch) . ")
                    ORDER
                        BY ordering ASC ,optionid ASC
                ";

				$SelectedFieldOptionsArray = $this->query($query, 'loadObjectList', 'optionid');

                # Reformat array, group by field id
                $SelelectedFieldOptionsByValue = array();

                foreach($SelectedFieldOptionsArray AS $option) {

                    $SelelectedFieldOptionsByValue[$option->fieldid][$option->value] = (array) $option;
                }
            }


//prx($nonInputFieldNames);
			$fnameArray = array_keys($rows);

			foreach($fieldValues AS $fieldValue)
            {
                $fieldValue = (array) $fieldValue;

				$fieldvalue = $this->sortArrayByArray($fieldValue, $fnameArray);

                foreach($fnameArray AS $key)
                {
	      			$value = '';

					if(isset($fieldValue[$key])) {
						$value = $fieldValue[$key];
					}
					// Add non-input fields, banner, back to the array
					elseif(in_array($key,$nonInputFieldNames)) {
						$value = 'banner';
					}

                    if ($key != 'element_id' && $value != '' && isset($rows[$key]))
                    {
                        $properties = stringToArray($rows[$key]->{'Field.params'});

                        // Process related listing fields. Need to get listing info to build the url
                        if($rows[$key]->{'Field.type'} == 'relatedlisting') {
                            if($value == 0) continue;
                            $relatedListingIds[] = $value;
                        }

                        // Strip html from fields except those where it is allowed
                        if(!is_array($value)
                            && (
                                !in_array($rows[$key]->{'Field.type'},array('text','textarea','code'))
                                ||
                                (in_array($rows[$key]->{'Field.type'},array('text','textarea')) && Sanitize::getBool($properties,'allow_html') == false)
                                )
                            ) {
                            $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');
                        }

                        if($rows[$key]->{'Field.type'} != 'date' || ($rows[$key]->{'Field.type'} == 'date' && $value != NULL_DATE))
                        {
                            $elementFields[$fieldValue['element_id']]['field_id'] = $fieldRows[$key]['Field.field_id'];

                            if(!in_array($rows[$key]->{'Field.type'},$optionFields) )
                            {
                                $elementFields[$fieldValue['element_id']][$key]['Field.text'][] = $value;
                                $elementFields[$fieldValue['element_id']][$key]['Field.value'][] = $value;
                                $elementFields[$fieldValue['element_id']][$key]['Field.image'][] = '';

                            } elseif(in_array($rows[$key]->{'Field.type'},$optionFields) ) {

                                $fieldOptions = Sanitize::getVar($SelelectedFieldOptionsByValue,$rows[$key]->{'Field.field_id'});

                                $selOptions = !is_array($value) ? explode('*',$value) : $value;

                                foreach($selOptions AS $selOption)
                                {
                                    if($selOption != '' && isset($fieldOptions[$selOption])) {
                                        $elementFields[$fieldValue['element_id']][$key]['Field.value'][] = $fieldOptions[$selOption]['value'];
                                        $elementFields[$fieldValue['element_id']][$key]['Field.text'][] = $fieldOptions[$selOption]['text'];
                                        $elementFields[$fieldValue['element_id']][$key]['Field.image'][] = $fieldOptions[$selOption]['image'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Only banner fields are present so we need to populate the $elementFields array just for them

        if(!empty($fieldRows) && !empty($nonInputFieldNames) && empty($fieldValues) && empty($elementFields)) {

            $ids = explode(',',$element_ids);

            foreach($ids AS $element_id) {

                foreach($fieldRows AS $field_name=>$field_row) {

                    if(!in_array($field_row['Field.type'],$nonInputFields)) continue;

                    $group_ids = $this->_criteria2Groups($elements[$element_id]['Criteria']['criteria_id'], $type);

                    if(!in_array($fieldRows[$field_name]['Field.group_id'], $group_ids)) continue;

                    $elementFields[$element_id]['field_id'] = $field_row['Field.field_id'];

                    $elementFields[$element_id][$field_name]['Field.id'] = $field_row['Field.field_id'];

                    $elementFields[$element_id][$field_name]['Field.text'][] = $field_row['Field.description'];

                    $elementFields[$element_id][$field_name]['Field.value'][] = $field_row['Field.description'];

                    $elementFields[$element_id][$field_name]['Field.image'][] = $field_row['Field.description'];
                }
            }
        }

        $relatedListings = $this->getRelatedListings($relatedListingIds);

        // Reformat array so array keys match element ids
        foreach ($elementFields AS $key=>$elementField)
        {
            $element_id = $key;
            $field_id = $elementField['field_id'];

            unset($elementField['field_id']);

            $field_name = key($elementField);

            $group_ids = $this->_criteria2Groups($elements[$element_id]['Criteria']['criteria_id'], $type);

            foreach($elementField AS $field_name=>$field_options)
            {
                if(!in_array($fieldRows[$field_name]['Field.group_id'], $group_ids)) continue;

                //FieldGroups array
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['group_id'] = $fieldRows[$field_name]['Field.group_id'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['title'] = $fieldRows[$field_name]['Group.title'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['name'] = $fieldRows[$field_name]['Group.name'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['show_title'] = $fieldRows[$field_name]['Group.showTitle'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['id'] = $fieldRows[$field_name]['Field.field_id'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['group_id'] = $fieldRows[$field_name]['Field.group_id'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['name'] = $fieldRows[$field_name]['Field.name'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['type'] = $fieldRows[$field_name]['Field.type'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['title'] = $fieldRows[$field_name]['Field.title'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['description'] = $fieldRows[$field_name]['Field.description'];

                // Field values
//                prx($field_options);exit;
                if($fieldRows[$field_name]['Field.type'] == 'relatedlisting') {
                    $value = isset($relatedListings[$field_options['Field.value'][0]]) ? array($relatedListings[$field_options['Field.value'][0]]['value']) : '';
                    $real_value = $field_options['Field.value'];
                    $text = isset($relatedListings[$field_options['Field.text'][0]]) ? array($relatedListings[$field_options['Field.value'][0]]['text']) : '';
                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['value'] = $value;
                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['text'] = $text;
                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['real_value'] = $field_options['Field.text'];
                }
                else {
                    $value = $field_options['Field.value'];
                    $text = $field_options['Field.text'];
                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['value'] = $value;
                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['text'] = $text;
                }

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['image'] = $field_options['Field.image'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['show_title'] = $fieldRows[$field_name]['Field.showTitle'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['location'] = $fieldRows[$field_name]['Field.location'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['contentview'] = $fieldRows[$field_name]['Field.contentView'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['listview'] = $fieldRows[$field_name]['Field.listView'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['compareview'] = $fieldRows[$field_name]['Field.compareView'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['listsort'] = $fieldRows[$field_name]['Field.listSort'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['search'] = $fieldRows[$field_name]['Field.search'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['access'] = $fieldRows[$field_name]['Field.access'];
                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];

                //FieldPairs associative array with field name as key and field value as value
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['field_id'] = $element_id;
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_id'] = $fieldRows[$field_name]['Field.group_id'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_show_title'] = $fieldRows[$field_name]['Group.showTitle'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_title'] = $fieldRows[$field_name]['Group.title'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_name'] = $fieldRows[$field_name]['Group.name'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['name'] = $fieldRows[$field_name]['Field.name'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['title'] = $fieldRows[$field_name]['Field.title'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['value'] = $value;
                isset($real_value) and $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['real_value'] = $real_value;
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['text'] = $text;
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['image'] = $field_options['Field.image'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['type'] = $fieldRows[$field_name]['Field.type'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['description'] = $fieldRows[$field_name]['Field.description'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['show_title'] = $fieldRows[$field_name]['Field.showTitle'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['location'] = $fieldRows[$field_name]['Field.location'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['contentview'] = $fieldRows[$field_name]['Field.contentView'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['listview'] = $fieldRows[$field_name]['Field.listView'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['compareview'] = $fieldRows[$field_name]['Field.compareView'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['listsort'] = $fieldRows[$field_name]['Field.listSort'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['search'] = $fieldRows[$field_name]['Field.search'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access'] = $fieldRows[$field_name]['Field.access'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];

                $properties = stringToArray($fieldRows[$field_name]['Field.params']);

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties'] = array_merge($fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties'],$properties);
                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties'] = array_merge($field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties'],$properties);

                //$params = explode("\n",$fieldRows[$field_name]['Field.params']);
            }
        }

        $this->custom_fields = $fields;
        $this->field_pairs = $field_pairs;
    }

    /**
     * Used in forms
     * Creates the custom field group array with group info and fields attributes
     * The selected key is filled if an $entry array with field values is passed
     */
    function getFieldsArrayNew($criteria_ids, $location = 'listing', $entry = null, $search = null)
    {
        $rows = false;

        # Check for cached version
        $cache_prefix = 'field_model_new';
        $cache_key = func_get_args();
        $cache_key['locale'] = cmsFramework::locale();

        if(isset($cache_key[2])) unset($cache_key[2]); // $entry not required to cache the results
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            $rows = $cache;
        }

        if(false==$rows || $rows == '') {

            $fields = array();

            $location = ($location == 'listing' ? 'content' : $location);

            $group_ids = $this->_criteria2Groups($criteria_ids,$location);

            if (empty($group_ids))
            {
                return;
            }

            //get field attributes only, no values
            $query = "
                SELECT
                    Field.fieldid AS `Field.id`, Field.groupid AS `Field.groupid`, Field.name AS `Field.name`, Field.title AS `Field.title`,
                    Field.showtitle AS `Field.showTitle`, Field.description AS `Field.description`, Field.required AS `Field.required`,
                    Field.type AS `Field.type`, Field.location AS `Field.location`, Field.options AS `Field.params`,
                    Field.search AS `Field.search`, Field.access AS `Field.access`, Field.access_view AS `Field.accessView`,
                    Field.published As `Field.published`,Field.compareview AS `Field.compareView`,
                    `Group`.groupid AS `Group.group_id`, `Group`.title AS `Group.title`, `Group`.name AS `Group.name`, `Group`.showtitle AS `Group.showTitle`
                FROM
                    #__jreviews_fields AS Field
                INNER JOIN
                    #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND
                    `Group`.groupid IN (".implode(',',$group_ids).") AND `Group`.type =  " . $this->Quote($location) . " )
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    " . ($search ? " AND search = 1" : '') . "
                GROUP BY
                    Field.fieldid
                ORDER
                    BY `Group`.ordering, Field.ordering
            ";

            $rows = $this->query($query, 'loadObjectList');

            # Send to cache
            S2cacheWrite($cache_prefix,$cache_key,$rows);
        }

        if (!$rows || empty($rows))
        {
            return;
        }

        //extract field ids from array
        /**
        * Select and multiple fields excluded because their option values are loaded via ajax via the control fields feature
        **/
        $this->field_ids = $multi_field_ids = $this->_extractFieldIds($rows,
            array(/*'select','selectmultiple',*/'radiobuttons','checkboxes')
        );

        //get the field options for multiple choice fields
        $this->_getFieldOptions();

        // Reformat array and add field options to each multiple choice field
        foreach ($rows AS $row)
        {
            $row = (array) $row;

            $fields[$row['Group.title']]['group_name'] = $row['Group.name'];

            //FieldGroups array
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['field_id'] = $row['Field.id'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['group_id'] = $row['Field.groupid'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['group_title'] = $row['Group.title'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['group_show_title'] = $row['Group.showTitle'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['name'] = $row['Field.name'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['type'] = $row['Field.type'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['title'] = $row['Field.title'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['description'] = $row['Field.description'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['required'] = $row['Field.required'];

            # $entry is passed when editing listing or review and includes the existing field values
            if(is_array($entry) && !empty($entry['Field']['pairs']) && isset($entry['Field']['pairs'][$row['Field.name']]['value']))
            {
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['selected'] = $entry['Field']['pairs'][$row['Field.name']]['value'];
            }

            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['show_title'] = $row['Field.showTitle'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['location'] = $row['Field.location'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['search'] = $row['Field.search'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['access'] = $row['Field.access'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['access_view'] = $row['Field.accessView'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['compareview'] = $row['Field.compareView'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['published'] = $row['Field.published'];

            $params = explode("\n",$row['Field.params']);

            foreach ($params AS $param) {
                $key_array = explode("=",$param);
                $key = current($key_array);
                $value_array = explode("=",$param);
                $value = end($value_array);
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties'][$key] = $value;
            }

            if(isset($this->field_options_alpha[$row['Field.id']])) {

                $ordering = isset($fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['option_ordering']) && $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['option_ordering'] ? 'alpha' : 'ordering';

                $method = 'field_options_'.$ordering;
                $methodList = 'field_optionsList_'.$ordering;
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['options'] = $this->{$method}[$row['Field.id']];
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['optionList'] = $this->{$methodList}[$row['Field.id']];
            }
        }

        return $fields;
    }

    /**
     * Used in dynamic forms
     * Creates an array of fields with attributes and options using an array of field names as input
     */
    function getFieldsArrayFromNames($names, $location = 'listing',  $entry = null)
    {
        if(empty($names) || (count($names)==1 & $names[0] == 'category')) {
            return array();
        }

        $rows = false;

        # Check for cached version
        $cache_prefix = 'field_model_names';
        $cache_key = func_get_args();
        $cache_key['locale'] = cmsFramework::locale();

        if(isset($cache_key[2])) unset($cache_key[2]); // $entry not required to cache the results
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            $rows = $cache;
        }

        if(false==$rows || $rows == '') {

            $location = ($location == 'listing' ? 'content' : $location);

            foreach($names AS $name) {
                $quoted_names[] = "'".$name."'";
            }

            $quoted_names = implode(',',$quoted_names);

            //get field attributes only, no values
            $query = "SELECT Field.fieldid AS `Field.id`, Field.name AS `Field.name`, Field.title AS `Field.title`,"
            . "\n Field.type AS `Field.type`, Field.options AS `Field.params`,  Field.required AS `Field.required`,"
            . "\n `Group`.groupid AS `Group.group_id`, `Group`.title AS `Group.title`"
            . "\n FROM #__jreviews_fields AS Field"
            . "\n INNER JOIN #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND "
            . "\n `Group`.groupid AND `Group`.type =  '$location' )"
            . "\n WHERE Field.name IN ($quoted_names) AND Field.location = '$location'"
            . " ORDER BY Group.ordering, Field.ordering"
            ;

            $rows = $this->query($query, 'loadObjectList');

            # Send to cache
            S2cacheWrite($cache_prefix,$cache_key,$rows);
        }

        if (!$rows || empty($rows))
        {
            return;
        }

        //extract field ids from array
        $this->field_ids = $multi_field_ids = $this->_extractFieldIds($rows,
            array(/*'select','selectmultiple',*/'radiobuttons','checkboxes')
        );

        //get the field options for multiple choice fields
        $this->_getFieldOptions();

        // Reformat array and add field options to each multiple choice field
        foreach ($rows AS $row)
        {
            $row = (array) $row;

            //FieldGroups array
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['field_id'] = $row['Field.id'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['name'] = $row['Field.name'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['type'] = $row['Field.type'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['title'] = $row['Field.title'];
            $fields[$row['Group.title']]['Fields'][$row['Field.name']]['required'] = $row['Field.required'];

            # $entry is passed when editing listing or review and includes the existing field values
            if(is_array($entry) && isset($entry['Field']['pairs'][$row['Field.name']]['value']))
            {
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['selected'] = $entry['Field']['pairs'][$row['Field.name']]['value'];
            }


            $params = explode("\n",$row['Field.params']);

            foreach ($params AS $param) {
                $key_array = explode("=",$param);
                $key = current($key_array);
                $value_array = explode("=",$param);
                $value = end($value_array);
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties'][$key] = $value;
            }

            if(isset($this->field_options_alpha[$row['Field.id']])) {

                $ordering = isset($fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['option_ordering']) && $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['option_ordering'] ? 'alpha' : 'ordering';

                $method = 'field_options_'.$ordering;
                $methodList = 'field_optionsList_'.$ordering;
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['options'] = $this->{$method}[$row['Field.id']];
                $fields[$row['Group.title']]['Fields'][$row['Field.name']]['optionList'] = $this->{$methodList}[$row['Field.id']];
            }

        }

        return $fields;

    }

    /**
     * Auxiliary function - used for custom coding
     * Creates an array of fields with field names as array keys and attributes and options using an array of field names as input
     * Used in in geomaps module
     */
    function getFields($names, $location = 'listing', $optionValues = null)
    {
        if(empty($names) || (count($names)==1 & $names[0] == 'category')) {
            return array();
        }

        $rows = false;

        # Check for cached version
        $cache_prefix = 'field_model_names';
        $cache_key = func_get_args();
        $cache_key['locale'] = cmsFramework::locale();

        if(isset($cache_key[2])) unset($cache_key[2]); // $entry not required to cache the results
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            $rows = $cache;
        }

        if(false==$rows || $rows == '') {

            $location = ($location == 'listing' ? 'content' : $location);

            //get field attributes only, no values
            $query = "
                SELECT
                    Field.fieldid AS `Field.id`, Field.name AS `Field.name`, Field.title AS `Field.title`,
                    Field.location AS `Field.location`,
                    Field.type AS `Field.type`, Field.options AS `Field.params`,  Field.required AS `Field.required`,
                    Field.access_view AS `Field.access_view`, `Group`.groupid AS `Group.group_id`, `Group`.title AS `Group.title`
                FROM
                    #__jreviews_fields AS Field
                INNER JOIN
                    #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND `Group`.groupid AND `Group`.type =  '$location' )
                WHERE
                    Field.name IN (".$this->Quote($names).") AND Field.location = " . $this->Quote($location)
            ;

            $rows = $this->query($query, 'loadObjectList');

            # Send to cache
            S2cacheWrite($cache_prefix,$cache_key,$rows);
        }

        if (!$rows || empty($rows))
        {
            return;
        }

        //extract field ids from array
        $this->field_ids = $multi_field_ids = $this->_extractFieldIds($rows,
            array('select','selectmultiple','radiobuttons','checkboxes')
        );

        //get the field options for multiple choice fields
        $this->_getFieldOptions($optionValues);

        // Reformat array and add field options to each multiple choice field
        foreach ($rows AS $row)
        {
            $row = (array) $row;

            //FieldGroups array
            $fields[$row['Field.name']]['field_id'] = $row['Field.id'];
            $fields[$row['Field.name']]['name'] = $row['Field.name'];
            $fields[$row['Field.name']]['type'] = $row['Field.type'];
            $fields[$row['Field.name']]['title'] = $row['Field.title'];
            $fields[$row['Field.name']]['required'] = $row['Field.required'];
            $fields[$row['Field.name']]['properties']['location'] = $row['Field.location'];
            $fields[$row['Field.name']]['properties']['access_view'] = $row['Field.access_view'];
            $params = explode("\n",$row['Field.params']);

            foreach ($params AS $param) {
                $parts = explode("=",$param); // Need to use this approach because click2search field has equal signs in the value of the property
                $key = array_shift($parts);
                $value = implode('=',$parts);
                $fields[$row['Field.name']]['properties'][$key] = $value;
            }

            if(isset($this->field_options_alpha[$row['Field.id']]))
            {
                $ordering = isset($fields[$row['Field.name']]['Fields'][$row['Field.name']]['properties']['option_ordering']) && $fields[$row['Group.title']]['Fields'][$row['Field.name']]['properties']['option_ordering'] ? 'alpha' : 'ordering';
                $method = 'field_options_'.$ordering;
                $methodList = 'field_optionsList_'.$ordering;
                $fields[$row['Field.name']]['options'] = $this->{$method}[$row['Field.id']];
                $fields[$row['Field.name']]['optionList'] = $this->{$methodList}[$row['Field.id']];
            }
        }

        return $fields;
    }

	/**
	 * Used in simplesearch to use only the text-based custom fields for searches
	 * @return array
	 */
	function getTextBasedFieldNames()
	{
        # Check for cached version
        $cache_prefix = 'text_fieldnames';
        $cache_key = func_get_args();

        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            $fields = $cache;
        }

		// $database = cmsFramework::getConfig('db');

		// $dbprefix = cmsFramework::getConfig('dbprefix');

		// $query = "
		// 	SELECT
		// 		column_name
		// 	FROM
		// 		information_schema.columns
		// 	WHERE
		// 		table_name='".$dbprefix."jreviews_content'
		// 		AND table_schema = " . $this->Quote($database) . "
		// 		AND data_type IN ('text','mediumtext','varchar','char')
		// 		AND column_name NOT IN ('email','listing_note','ipaddress')
		// ";

        $query = "
            SELECT
                name
            FROM
                #__jreviews_fields
            WHERE
                location = 'content'
                AND
                published = 1
                AND
                type IN ('select','selectmultiple','text','textarea','radiobuttons','checkboxes');";

		$fields = $this->query($query,'loadColumn');

		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$fields);

		return $fields;
	}

    /**
     * This is the list of values used for the order select list
     */
    function getOrderList($cat_ids, $location, $task, $field_list_tasks)
    {
        $list = array();
        $field_order_array = array();

        if($location == 'listing') {
            $location = 'content';
        }

        // Check the number of criteria defined and if more than one check if all have a common group
        // A common set of groups means the criteria is different because of the ratings, not the fields
        // This is to show the custom fields in the dropdown select list for search results if the fields are common to all entries
        $query = "SELECT count(DISTINCT groupid)"
        . "\n FROM #__jreviews_criteria"
        . "\n WHERE groupid != ''"
        ;

        $criteria_groups = $this->query($query, 'loadResult');

		$criteria_ids = $this->_category2Criteria($cat_ids);

        if(($criteria_groups == 1 || count($criteria_ids) ==1) && in_array($task, $field_list_tasks))
        {
            $group_ids = $this->_criteria2Groups($criteria_ids, $location);

            if(!empty($group_ids))
            {
                // Need to include fieldid for multilingual support Joomfish
                $query = "SELECT Field.fieldid, Field.title AS text, Field.name AS value, Field.access_view AS access"
                . "\n FROM #__jreviews_fields AS Field"
                . "\n WHERE Field.location = '".$location."' "
                . "\n AND Field.listsort = 1"
                . "\n AND Field.groupid IN (" . implode(',',$group_ids) . ")"
                ;

                if($list = $this->query($query, 'loadObjectList')) {
                    foreach ($list AS $key=>$row) {
                        $field_order_array[$row->value] = (array) $row;
                    }
                }
            }
        }

        return $field_order_array;
    }

    function processNewFieldOptions($options, $field)
    {
        $processedOptions = array();
        // Process new field options and modify the $data array
        $FieldOption = ClassRegistry::getClass('FieldOptionModel');
        $click2add = Sanitize::getBool($field['_params'],'click2add');
        !is_array($options) and $options = array($options);
        foreach($options AS $option)
        {
            if($click2add && strstr($option,'|click2add'))
            {
                $data = array();
                $option_parts = explode('|',$option);

                // Build array to pass to the FieldOptions model
                $data['FieldOption']['fieldid'] = Sanitize::getInt($field,'fieldid');
                $data['FieldOption']['value'] = Sanitize::stripAll($option_parts,0);
                $data['FieldOption']['text'] = trim(Sanitize::getString($option_parts,0));
                // If it's a dependent field add the relevant control field data
                if(count($option_parts) == 4) {
                    $option_parts[3] == 'null' and $option_parts[3] = '';
                    $controlledBy = $data['FieldOption']['controlledBy'] = array($option_parts[2]=>$option_parts[3]);
                    $control_field = key($controlledBy);
                    $control_value = is_array(current($controlledBy)) ?  array_values(current($controlledBy)) : array(current($controlledBy));
                    if($control_field != '' && $control_value != '**')
                    {
                        $data['FieldOption']['control_field'] = $control_field;
                        $data['FieldOption']['control_value'] = $control_value;
                    }
                }
                if(in_array($FieldOption->save($data),array('success','duplicate'))) {
                    $processedOptions[] = $data['FieldOption']['value'];
                }
            }
            else {
                $processedOptions[] = $option;
            }
        }
        return $processedOptions;
    }

    function save(&$data, $location = 'listing', $isNew, &$validFields = array())
    {
        $msg = '';
        $fieldLocation = inflector::camelize($location);

        // Check if there are custom fields to save or exit
        if (isset($data['Field'])
            &&
            (!is_array($data['Field'][$fieldLocation]) || count($data['Field'][$fieldLocation]) == 1
            )
        ) {
            return $msg;
        }

       if(!empty($validFields))
       {
            S2App::import('Model','field_option','jreviews');

			$validFieldNames = array_keys($validFields);

			foreach ($validFieldNames AS $fieldName)
            {
                $inputValue = '';
				$validField = isset($validFields[$fieldName]) ? $validFields[$fieldName] : false;

                if ( ($validField && $validField['valid'])
					&&
					(
						$validField['type'] != 'code'
                    	|| ($validField['type'] == 'code' && Sanitize::getVar($data['__raw']['Field'][$fieldLocation],$fieldName,'') != ''))
 					)
                {
                    switch($validField['type'])
                    {
                        case 'selectmultiple': case 'checkboxes':
                        case 'select': case 'radiobuttons':
                            //Checks for types with options
                            $options = $this->processNewFieldOptions(Sanitize::getVar($data['Field'][$fieldLocation],$fieldName),$validField);
                            $inputValue = '*'.implode('*',$options).'*';
  							break;
                        case 'code':
                            // Affiliate code left unfiltered
                            $inputValue = Sanitize::getString($data['__raw']['Field'][$fieldLocation],$fieldName,'');
                        break;
                        case 'decimal':
                        	$inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName);
							$inputValue = $inputValue == '' ? null : $inputValue;
                        break;
                        case 'integer':
                        case 'relatedlisting':
                            $inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName);
							$inputValue = $inputValue == '' ? null : (int) $inputValue;
                        break;
                        case 'date':
                            if(Sanitize::getString($data['Field'][$fieldLocation],$fieldName) != '' && Sanitize::getString($data['Field'][$fieldLocation],$fieldName) != null)
                            {
                                $inputValue = strftime( _CURRENT_SERVER_TIME_FORMAT, strtotime(Sanitize::getString($data['Field'][$fieldLocation],$fieldName)));
                            }
                            else
                            {
                                $inputValue = '';
                            }
                        break;
                        case 'textarea': case 'text':
                            if (isset($validField['_params']['allow_html']) && $validField['_params']['allow_html'] == 1 )
                            {
                                $inputValue = Sanitize::stripScripts(Sanitize::getString($data['__raw']['Field'][$fieldLocation],$fieldName,''));
                                $inputValue = stripslashes($inputValue);
                            }
                            else
                            {
                                $inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName,'');
                            }
                        break;
                        case 'website': case 'email':
                            $inputValue = Sanitize::stripScripts(Sanitize::getVar($data['Field'][$fieldLocation],$fieldName));
                        break;
                        default:
                            $inputValue = Sanitize::getVar($data['Field'][$fieldLocation],$fieldName);
                        break;
                    }

                    # Modify form post arrays to current values
                    if($inputValue === '' || $inputValue === '**') {
                        $inputValue = '';
                    }

                    $data['Field'][$fieldLocation][$fieldName] = $inputValue;

                }
				elseif($validField) {
	                switch($validField['type'])
                    {
                        case 'decimal':
                        case 'integer':
						case 'relatedlisting':
                            $data['Field'][$fieldLocation][$fieldName] = null;
                        break;
                        default:
                            $data['Field'][$fieldLocation][$fieldName] = '';
                        break;
                    }
                }

                // Debug custom fields array
                $msg .=  "{$validField['name']}=>{$inputValue}"."<br />";
            }
        }

        # Need to check if jreviews_content or jreviews_reviews record exists to decide whether to insert or update the table
        if($location == 'review')
        {
            S2App::import('Model','jreviews_review_field','jreviews');
            $JreviewsReviewFieldModel = new JreviewsReviewFieldModel();
            $recordExists = $JreviewsReviewFieldModel->findCount(array(
				'conditions'=>array('JreviewsReviewField.reviewid= ' . $data['Field']['Review']['reviewid']),
				'session_cache'=>false
			));
        }
        else
        {
            S2App::import('Model','jreviews_content','jreviews');
            $JreviewsContentModel = new JreviewsContentModel();
            $recordExists = $JreviewsContentModel->findCount(array(
				'conditions'=>array('JreviewsContent.contentid = ' . $data['Listing']['id']),
				'session_cache'=>false
			));
        }

        $dbAction = $recordExists ? 'update' : 'insert';

        if($location == 'review')
        {
            $result = $this->$dbAction('#__jreviews_review_fields',$fieldLocation,$data['Field'],'reviewid');
        }
        else
        {
            if(Configure::read('PaidListings.enabled') && Sanitize::getInt($data,'paid_category'))
            {
                # PaidListings integration - saves all fields to jreviews_paid_listing_fields table and removes unpaid fields from jreviews_content table
                $PaidListingField = ClassRegistry::getClass('PaidListingFieldModel');
                $PaidListingField->save($data);
            }

            $result = $this->$dbAction('#__jreviews_content',$fieldLocation,$data['Field'],'contentid');
         }

         return $result;
    }

    function validate(&$data, $fieldLocation, $Access)
    {
        $valid_fields = array();

        $valid_fields_str = Sanitize::getString($data,'valid_fields');

        if(!isset($data['Field']) && !$valid_fields_str) {
            return;
        }

		$location = $fieldLocation == 'listing' ? 'content' : 'review';

        $query = "
            SELECT
                groupid
            FROM
                #__jreviews_criteria
            WHERE
                id = " . (int) $data['Criteria']['id'];

        $groupids = $this->query($query, 'loadResult');

        if ($groupids)
        {
            appLogMessage("*********Validate fields",'database');

            # PaidListings integration to remove hidden fields from validation
            $plan_fields = isset($data['Paid']) ? explode(",",Sanitize::getString($data['Paid'],'fields')) : '';

            !empty($plan_fields) and $plan_fields =  "'" . implode("','", $plan_fields) . "'";

            $queryData = array(
                    'conditions'=>array(
                        'Field.groupid IN (' . $groupids . ')',
                        'Field.published = 1',
                        "Field.location = '$location'",
						"Field.type <> 'banner'"
                    )
                );

            if($location == 'content') {

                $plan_fields != '' and $queryData['conditions'][] = "Field.name IN (" . $plan_fields . ")";
            }

            $fields = $this->findAll($queryData);

            if (!$fields) {
                return;
            }

            $fields_for_validation = array_unique(explode(',',$valid_fields_str));

            $fieldLocation = inflector::camelize($fieldLocation);

			// For automatic geocoding of coordinates with GeoMaps
			$Config = Configure::read('JreviewsSystem.Config');

			$latField = Sanitize::getString($Config,'geomaps.latitude');

			$lonField = Sanitize::getString($Config,'geomaps.longitude');

			$checkCoordinates = $latField != '' && $lonField != '';

            foreach ($fields as $field)
            {
                // Check validation only for displayed fields *access rights*
                if(
					in_array($field['Field']['name'],$fields_for_validation)
                    &&
					($Access->in_groups($field['Field']['access'])
						||
						($checkCoordinates && in_array($field['Field']['name'],array($latField,$lonField)))
					))
                {

                    $value = isset($data['Field'][$fieldLocation]) ? Sanitize::getVar($data['Field'][$fieldLocation],$field['Field']['name'],'') : '';
                    /*
                    Was previously the line below. Changes made so that required checkbox fields without a checked value are properly validated
                     */
                    // $value = Sanitize::getVar($data['Field'][$fieldLocation],$field['Field']['name'],'');

                    $label = sprintf(__t("Please enter a valid value for %s.",true),$field['Field']['title']);

                    $name = $field['Field']['name'];

                    $type = $field['Field']['type'];

                    $required = $field['Field']['required'];

                    $valid_fields[$name] = $field['Field'];

                    $valid_fields[$name]['valid'] = true;

                    $regex = '';

                    if(!isset($field['Field']['_params']['valid_regex'])) {

                        switch($field['Field']['type']) {
                            case 'integer':
                            case 'relatedlisting':
                                $regex = "^[0-9]+$";
                                break;
                            case 'decimal':
                                $regex = "^(\.[0-9]+|[0-9]+(\.[0-9]*)?)$";
                                break;
                            case 'website':
                                $regex = "^((ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]|[a-z0-9_-]+\.+[a-z0-9_-])";
                                break;
                            case 'email':
                                $regex = ".+@.*";
                                break;
                            default:
                                $regex = '';
                                break;
                        }

                    } elseif ($type != 'date') {

                        $regex = $field['Field']['_params']['valid_regex'];
                    }

                    if (!is_array($value)) {
                        $value = array($value);

                    } elseif($type == 'selectmultiple' && is_array($value[0])) {
                        $data['Field'][$fieldLocation][$field['Field']['name']] = $data['Field'][$fieldLocation][$field['Field']['name']][0];
                        $value = $value[0];
                    }

                    $value = trim(implode(',',$value));

                    $this->validateInput($value, $name, $type, $label, $required, $regex);
                }
				elseif($Access->in_groups($field['Field']['access'])) {

					$valid_fields[$field['Field']['name']] = $field['Field'];

                    $valid_fields[$field['Field']['name']]['valid'] = false;
				}

            }

            return $valid_fields;
        }
    }

    function getRelatedListings($ids)
    {
        $results = array();

        $ids = array_filter($ids); // Remove zeroes

        if(empty($ids)) return array();

        $Listing = ClassRegistry::getClass('EverywhereComContentModel');

        $listings = $Listing->getListingById($ids);

        foreach($listings AS $key=>$listing) {
            $results[$key] = array('value'=>$listing['Listing']['url'],'text'=>$listing['Listing']['title']);
        }

        return $results;
    }

	function sortArrayByArray($array,$orderArray) {
		$ordered = array();
		foreach($orderArray as $key) {
			if(array_key_exists($key,$array)) {
				$ordered[$key] = $array[$key];
			}
			unset($array[$key]);
		}
		return $ordered + $array;
	}
}
