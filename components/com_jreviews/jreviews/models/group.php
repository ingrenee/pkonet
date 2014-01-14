<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class GroupModel extends MyModel  {

	var $name = 'Group';

	var $useTable = '#__jreviews_groups AS `Group`';

	var $primaryKey = 'Group.group_id';

	var $realKey = 'groupid';

	var $fields = array(
		'Group.groupid AS `Group.group_id`',
		'Group.name AS `Group.name`',
		'Group.title AS `Group.title`',
		'Group.type AS `Group.type`',
		'Group.ordering AS `Group.ordering`',
		'Group.showtitle AS `Group.showtitle`',
        'Group.control_field AS `Group.control_field`',
        'Group.control_value AS `Group.control_value`'
	);

    function afterFind($results)
    {
        foreach($results AS $key=>$result)
        {
            if(!is_array($results)) {
                return $results;
            }

            // Process Control Field values
            foreach($results AS $key=>$result)
            {
                $results[$key]['ControlValues'] = array();
                if(isset($result['Group']['control_value']) && $result['Group']['control_value'] != '')
                {
                    $results[$key]['Group']['control_value'] = explode('*',rtrim(ltrim($result['Group']['control_value'],'*'),'*'));
                    $query = "
                        SELECT
                            Field.fieldid,value,text
                        FROM
                            #__jreviews_fieldoptions AS FieldOption
                        LEFT JOIN
                            #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid
                        WHERE
                            Field.name = " . $this->Quote($result['Group']['control_field']) . "
                             AND FieldOption.value IN (". $this->Quote($results[$key]['Group']['control_value']) .")"
                    ;
                    $this->_db->setQuery($query);
                    $results[$key]['ControlValues'] = $this->_db->loadAssocList();
                }
            }
        }
        return $results;
    }

    /***********************************************************************
    * Process control data when creating/editing group via administration
    * @param mixed $data
    ***********************************************************************/
    function beforeSave(&$data)
    {
        // Convert Control Value array to string
        if(isset($data['Group']['control_value']))
        {
            $control_value = Sanitize::getVar($data['Group'],'control_value');
            $data['Group']['control_value'] = !empty($control_value) ? '*'.implode('*',$control_value).'*' : '';
        }
        else {
            $data['Group']['control_field'] = '';
        }
    }

	function getList($type, $limitstart, $limit, &$total) {

		// get the total number of records
		$query = "SELECT COUNT(*) FROM `#__jreviews_groups` WHERE type='$type'";
		$this->_db->setQuery($query);
		$total = $this->_db->loadResult();

		$query = "
            SELECT `Group`.*, count(Field.fieldid) AS field_count"
		    . "\n FROM #__jreviews_groups AS `Group`"
		    . "\n LEFT JOIN #__jreviews_fields AS Field ON `Group`.groupid = Field.groupid"
		    . "\n WHERE Group.type='$type'"
		    . "\n GROUP BY `Group`.groupid"
		    . "\n ORDER BY ordering LIMIT $limitstart, $limit"
		;

		$rows = $this->query($query,'loadObjectList');

		if(!$rows) {

			$rows = array();
		}

		return $rows;
	}

	function getSelectList($type)
    {
        $query = "
            SELECT
                groupid AS value, CONCAT(title,' (',name,')') AS text
            FROM
                #__jreviews_groups
            WHERE
                type= ". $this->Quote($type) ."
            ORDER BY
                title
        ";

		$this->_db->setQuery($query);

        if (!$results = $this->_db->loadObjectList()) {
            $results = array();
        }

		return $results;
	}

}
