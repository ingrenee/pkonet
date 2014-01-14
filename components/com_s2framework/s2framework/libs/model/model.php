<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Model extends S2Object{

    var $useTable;
    var $primaryKey;
    var $className;
    var $_db;
    var $_user;

    var $fields = array();
    var $conditions = array();
    var $joins = array();
    var $group = array();
    var $order = array();
    var $limit;
    var $offset;
    var $having = array();

    var $runAfterFind = true;

    /**
    * Array of callbacks that should be run even if cache is enebaled before they peform other actions unrelated to the query results
    * plgAfterFind','afterFind','plgAfterAfterFind
    * @var mixed
    */
    var $cacheCallbacks = array();

    var $validateErrors = array();

    function __construct() {
        # Adds CMS DB and Mainframe methods
        $this->_db = cmsFramework::getDB();
        parent::__construct();
    }

    function emptyModel()
    {
        $model = array();

        foreach($this->fields AS $field) {

            $alias_defined = strpos($field,' AS ');

            $field = $alias_defined ? explode('.',substr($field,$alias_defined)) : explode('.',$field);

            $key = str_replace('`','',end($field));

            $model[$this->name][$key] = null;
        }

        return $model;
    }

    function findAll($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        return $this->find('all',$queryData, $callbacks);
    }

    function findAllCache($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        return $this->find('all',$queryData, $callbacks, true);
    }

    function findOne($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        return $this->find('one',$queryData, $callbacks);
    }

    function findOneCache($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        return $this->find('one',$queryData, $callbacks, true);
    }

    function findRow($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind'))
    {
        $cache = false;
        if(isset($queryData['cache']) && $queryData['cache'] == true) {
            $cache = true;
            unset($queryData['cache']);
        }

        $rows = $this->find('all',$queryData, $callbacks, $cache);

        if(!$rows || empty($rows) || !is_array($rows)) {

            return false;
        }
        return array_shift($rows);

    }

    function findRowCache($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        $rows = $this->find('all',$queryData, $callbacks, true);

        if(!$rows || empty($rows)) {

            return false;
        }

        return current($rows);

    }

    function findCount($queryData = array(), $countField = '*', $cache = false)
    {
        $queries = array();

        $conditionsArray = array();

        $union = Sanitize::getBool($queryData,'union');

        unset($queryData['union']);

        if(!$union) {

            $queryData = array($queryData);
        }

        foreach($queryData AS $key=>$query) {

            $queryData[$key] = $this->__mergeArrays($query, $union);

            // Check if session cache has been disabled for this particular query
            $session_cache = Sanitize::getBool($query,'session_cache',true);

            unset($queryData[$key]['session_cache']);

            $conditionsArray = array_merge($conditionsArray,$queryData[$key]['conditions']);
        }

        // Session cache takes precedence
        if($session_cache && Configure::read('Cache.session')) {

            $count = $this->cacheSessionGetCount($conditionsArray);

            if(is_numeric($count)) return $count;
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {

            $cache_key = s2CacheKey('query_'.$countField, $queryData);

            $count = S2Cache::read($cache_key,'default');

            if(false !== $count) {
                return $count;
            }
        }

        if($union && $countField == '*') $countField = $this->primaryKey;

        foreach($queryData AS $key=>$query) {

            if(!Sanitize::getBool($queryData,'useGroup')) {

                unset($queryData['group']);
            }

            if($union && count($queryData) > 1) {

                $queries[] =
                    'SELECT ' . $countField
                    . "\n FROM " . $this->useTable
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
            //        . ( !empty($query['groupCount']) ? "\n GROUP BY ". implode(',', $query['groupCount']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                ;

            }
            else {

                $queries[] =
                    'SELECT COUNT(' . $countField . ')'
                    . "\n FROM " . $this->useTable
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
            //        . ( !empty($query['groupCount']) ? "\n GROUP BY ". implode(',', $query['groupCount']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                ;

            }

        }

        if(count($queries) > 1 && $union) {

            $sql = 'SELECT COUNT(*) FROM ((' . implode(" )\nUNION\n (", $queries) . ')) AS t';

        }
        else {

            $sql = array_shift($queries);
        }

        $this->_db->setQuery($sql);

        $count = $this->_db->loadResult();

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | findCount | Count: '. $count;

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        if(Configure::read('Cache.session')) {

            $this->cacheSessionSetCount($count,$conditionsArray);

            return $count;
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {

            S2Cache::write($cache_key,$count,'default');
        }

        return $count;
    }

    function findCountCache($queryData = array(), $countField = '*') {
        return $this->findCount($queryData, $countField, true);
    }

    function find($type, $queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind'), $cache = false)
    {
       $queries = array();

       $useTable = $this->useTable;

       $order = $limit = '';

       $union = Sanitize::getBool($queryData,'union');

       unset($queryData['union']);

        if(!$union) {

            $queryData = array($queryData);

        }

        foreach($queryData AS $key=>$query) {

            $queryData[$key] = $this->__mergeArrays($query, $union);
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true)
        {
            $cache_key = s2CacheKey('query_'.$type, serialize($queryData).serialize($callbacks));

            $rows = S2Cache::read($cache_key,'default');

/*            if($type != 'one' && in_array('plgAfterFind',$this->cacheCallbacks) && method_exists($this,'plgAfterFind')) {
                $rows = $this->plgAfterFind($rows);
            }
            if($type != 'one' && in_array('afterFind',$this->cacheCallbacks) && method_exists($this,'afterFind')) {
                $rows = $this->afterFind($rows);
            }
*/
            if($type != 'one' && in_array('plgAfterAfterFind',$this->cacheCallbacks) && method_exists($this,'plgAfterAfterFind')) {

                $rows = $this->plgAfterAfterFind($rows);
            }

            if(false !== $rows) {
                return $rows;
            }
        }

        foreach($queryData AS $key=>$query) {

            // Add query KEY HINTS
            if(isset($query['useKey'])) {

                $table_alias = key($query['useKey']);

                $key_hint = $query['useKey'][$table_alias];

                if($table_alias == $this->name) {

                    $useTable .= ' USE KEY ('.$key_hint.')';
                }
                elseif (isset($query['joins'][$table_alias])) {

                    $split_ON = explode('ON',$query['joins'][$table_alias]);

                    $split_ON[0] .= ' USE KEY ('.$key_hint.') ';

                    $query['joins'][$table_alias] = implode('ON',$split_ON);
                }
            }

            $query = S2Model::array_remove_empty($query);

            $order = !empty($query['order']) ? "\n ORDER BY ". implode(',', $query['order']) : '';

            if(isset($query['limit']) && $query['limit'] === 0) {

                $limit = "\n LIMIT 0";
            }
            else {

                $limit = !empty($query['limit']) ? "\n LIMIT ". (Sanitize::getInt($query,'offset',null) ? $query['offset'] . ", " : ''). $query['limit'] : '';
            }

            $queries[] =
                "SELECT " .
                    implode (",\n",$query['fields'])
                    . "\n FROM " . $useTable
            //        . ( !empty($query['useKey']) ? " USE KEY (".$query['useKey'].")" : '')
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                    . $order
                    . ($union ? '' : $limit)
                ;

        }

        if(count($queries) > 1 && $union) {

            $sql = '(' . implode(" )\nUNION\n (", $queries) . ')' . $order . $limit;

        }
        else {

            $sql = array_shift($queries);

            $union and $sql = $sql . $limit;
        }

        $this->_db->setQuery($sql);

        switch($type) {

            case 'all':

                $rows = $this->_db->loadObjectList();

                $rows = $this->__reformatArray($rows);

                break;

            case 'one':

                $rows = $this->_db->loadResult();

                break;
        }

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Find';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        if($type != 'one' && in_array('plgAfterFind',$callbacks) && method_exists($this,'plgAfterFind')) {
            $rows = $this->plgAfterFind($rows);
        }

        if($type != 'one' && in_array('afterFind',$callbacks) && method_exists($this,'afterFind')) {
            $rows = $this->afterFind($rows);
        }


        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {
            S2Cache::write($cache_key,$rows,'default');
        }

        if($type != 'one' && in_array('plgAfterAfterFind',$callbacks) && method_exists($this,'plgAfterAfterFind')) {
            $rows = $this->plgAfterAfterFind($rows);
        }

        return $rows;
    }

    static function array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = S2Model::array_remove_empty($haystack[$key]);
            }

            if ($haystack[$key] !== 0 && empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * Removes a field or fields if an array is passed from the query fields
     */
    function modelUnbind($fields)
    {
       $fields = is_array($fields) ? $fields : array($fields);
       $this->fields = array_diff($this->fields,$fields);
    }

    function views($id,$views_col = 'views')
    {
        // Uncomment line below to test views increment on page reload
        // cmsFramework::clearSessionNamespace('jreviews');

        $session_var = cmsFramework::getSessionVar($this->name.'View'.$id,'jreviews');

        // Session check to prevent views increment when the same user is reloading the page

        if(!$session_var)
        {
            cmsFramework::setSessionVar($this->name.'View'.$id,true,'jreviews');

            $query = "
                UPDATE
                    {$this->useTable}
                SET
                    {$views_col} = {$views_col} + 1
                WHERE
                    {$this->realKey} = " . (int) $id . "
            ";

            $this->query($query);

        }
    }

    function init() {

        $model = array();

        foreach($this->fields AS $field)
        {
            $clean_name = str_replace('`','',$field);
            $field = explode(' AS ',$clean_name);
            $keys = explode('.', end($field));
            $model[$keys[0]][$keys[1]] = null;
        }

        return $model;

    }


    function store(&$data, $updateNulls = false, $callbacks=array('beforeSave','afterSave','plgBeforeSave','plgAfterSave'))
    {
        $forceInsert = Sanitize::getBool($data,'insert',false);

        if(method_exists($this,'beforeSave') && in_array('beforeSave',$callbacks)) {
            $this->beforeSave($data);
        }
        if(method_exists($this,'plgBeforeSave') && in_array('plgBeforeSave',$callbacks)) {
            $data = $this->plgBeforeSave($data);
        }

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

        $primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;

        $clean_primary_key = str_replace('`','',$primaryKeyString);

        $key_parts = explode('.',$clean_primary_key);

        $keyName = end($key_parts);

        if( isset($data[$this->name][$keyName]) &&  $data[$this->name][$keyName] != '' && $data[$this->name][$keyName] != 0 && !$forceInsert) {

            $ret = $this->update( $table, $this->name, $data, $keyName, $updateNulls );

        } else {

            $ret = $this->insert( $table, $this->name, $data, $keyName );
        }

        if( !$ret ) {
            $this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->_db->getErrorMsg();
        }

        $this->data = &$data;

        if(method_exists($this,'afterSave') && in_array('afterSave',$callbacks)) {
            $this->afterSave($ret);
        }

        if(method_exists($this,'plgAfterSave') && in_array('plgAfterSave',$callbacks)) {
            $this->plgAfterSave($ret);
        }

        // clearCache('', 'views');
        // clearCache('', '__data');

        return $ret;

    }

    function delete($keyName, $values, $condition = '', $callbacks=array('beforeDelete','plgBeforeDelete','afterDelete','plgAfterDelete'))
    {
        if(in_array('beforeDelete',$callbacks))
            {
            $this->beforeDelete($keyName, $values, $condition);
            }

        if(in_array('plgBeforeDelete',$callbacks) && method_exists($this,'plgBeforeDelete'))
            {
            $this->plgBeforeDelete($keyName, $values, $condition);
            }

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

        $fmtsql = "DELETE FROM $table WHERE %s IN ( %s ) %s";

        $condition = $condition != '' ? "AND $condition" : '';

        $this->_db->setQuery( sprintf( $fmtsql, $keyName, is_array($values) ? implode( ",", $values ) : $values, $condition) );

        if($delete = $this->_db->query())
        {
            if(in_array('afterDelete',$callbacks))
                {
                    $this->afterDelete($keyName, $values, $condition);
                }

            if(in_array('plgAfterDelete',$callbacks) && method_exists($this,'plgAfterDelete'))
                {
                    $this->plgAfterDelete($keyName, $values, $condition);
                }
        }

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Delete';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        // clearCache('', 'views');
        // clearCache('', '__data');
        // Clear session cache
        if(Configure::read('Cache.session')) {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }


        return $delete;

    }

    function insert( $table, $alias, &$data, $keyName = null)
    {
        $fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ";

        $alias = inflector::camelize($alias);

        $fields = array();

        foreach ($data[$alias] as $k => $v) {
            if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;
            $fields[] = "`$k`";
            $values[] = $this->Quote($v);
        }

        if (!isset($fields)) die ('class database method insertObject - no fields');

        $this->_db->setQuery( sprintf( $fmtsql, implode( ",", $fields ), implode( ",", $values ) ) );

        $insert = $this->_db->query();

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Insert';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        if (!$insert)
        {
            return false;
        }

        $id = $this->_db->insertid();

        if ($keyName && $id) {
            $data[$alias][$keyName] = $id;
        }

        $data['insertid'] = $id;

        // Clear session cache
        if(Configure::read('Cache.session')) {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }

        return true;
    }

    function replace( $table, $alias, &$data, $keyName = null)
    {
        // Changed from REPLACE to INSERT with ON DUPLICATE UPDATE
        $fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ON DUPLICATE KEY UPDATE %s";

        $alias = inflector::camelize($alias);

        $fields = $duplicates = array();

        foreach ($data[$alias] as $k => $v)
        {
            if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;

            $fields[] = "`$k`";

            $values[] = $this->Quote($v);

            $duplicates[] = $k ."  = " . $this->Quote($v);
        }

        if (!isset($fields)) die ('class database method insertObject - no fields');

        $this->_db->setQuery( sprintf( $fmtsql, implode( ", ", $fields ), implode( ", ", $values ), implode( ", ", $duplicates ) ) );

        $replace = $this->_db->query();

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Replace';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        if (!$replace)
        {
            return false;
        }

        $id = $this->_db->insertid();

        if ($keyName && $id) $data[$alias][$keyName] = $id;

        return true;
    }

    function update( $table, $alias, &$data, $keyName, $updateNulls=true )
    {
        $fmtsql = "UPDATE $table SET %s WHERE %s";

        $tmp = array();

        foreach ($data[$alias] as $k => $v)
        {
            if (is_array($v) OR is_object($v) OR $k[0] == '_' OR ($v === null AND !$updateNulls)) continue;

             // use primary key to locate update record
             if( $k == $keyName )
             {
                $where = "$keyName= " . $this->Quote( $v );
                continue;
            }

            if ($v === null)
            {
                if ($updateNulls) {
                    $val = 'NULL';
                } else {
                    continue;
                }
            } else {
                $val = $this->Quote($v);
            }

            $tmp[] = "`$k`= $val";

        }

        if (!isset($tmp)) return true;

        if (!isset($where)) die ('Model class update method - no key value');

        $this->_db->setQuery( sprintf( $fmtsql, implode( ",", $tmp ) , $where ) );

        $update = $this->_db->query();

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Update';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        if (!$update)
        {
            return false;
        }

        return true;
    }

    function move( $direction, $where='' ) {

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

        $compops = array (-1 => '<', 0 => '=', 1 => '>');
        $relation = $compops[($direction>0)-($direction<0)];
        $ordering = ($relation == '<' ? 'DESC' : 'ASC');
        $k = $this->realKey;
        $o1 = $this->Result[$this->name]['ordering'];
        $k1 = $this->Result[$this->name][$k];

        $sql = "SELECT $k, ordering FROM $table WHERE ordering $relation $o1";

        $sql .= ($where ? "\n AND $where" : '').' ORDER BY ordering '.$ordering.' LIMIT 1';

        $this->_db->setQuery( $sql );

        if ($row = $this->_db->loadObjectList()) {
            $row = current($row);
            $o2 = $row->ordering;
            $k2 = $row->$k;
            $sql = "UPDATE $table SET ordering = (ordering=$o1)*$o2 + (ordering=$o2)*$o1 WHERE $k = $k1 OR $k = $k2";
            $this->_db->setQuery($sql);
            $this->_db->query();
        }

        clearCache('', 'views');
        clearCache('', '__data');
    }


    function reorder($order_data, $order_col = 'ordering') {

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

        $primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;

        $keyParts = explode('.',str_replace('`','',$primaryKeyString));

        $key = end($keyParts);

        $query = "
            UPDATE
                 {$table}
            SET
                {$order_col} = CASE {$key}
        ";

        foreach ($order_data AS $row) {
            $ids[] = (int) $row['id'];
            $query .= sprintf("WHEN %d THEN %d ", (int) $row['id'], $row['order']);
        }

        $query .= "END WHERE {$key} IN (".cleanIntegerCommaList($ids).")";

        clearCache('', 'views');

        clearCache('', '__data');

        return $this->query($query);
    }

    function validateInput($value, $name, $type, $label, $required, $regex = '')
    {
        $regex = (string)trim($regex);

        if($regex=='')
        {
            switch($type)
            {
                case 'integer':
                    $regex = '^[0-9]+$';
                    break;
                case 'decimal':
                    $regex = '^(\.[0-9]+|[0-9]+(\.[0-9]*)?)$';
                    break;
                case 'website':
                    $regex = '^(ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]';
                    break;
                case 'email':
                    $regex = '.+@.*';
                    break;
                default:
                    $regex = '';
                    break;
            }
        }

        if ( $required && trim(strip_tags($value)) == "" )
        {
            $this->validateSetError($name, $label);

        } elseif (trim(strip_tags($value)) && $regex != "")
        {
            if(!preg_match('/'.$regex.'/i',$value))
            {
                $this->validateSetError($name, $label);
            }

        }
    }

    function validateSetError($name, $label)
    {
        $this->validateErrors[] = array("input"=>$name, "message"=>$label);
    }

    /* original function */
    function validateGetError() {

        $errors = $this->validateErrors;
        $msg = '';

        foreach($errors as $error) {
            $msg .= $error['message'] != 'ok' ? "<span class=\"error\">".addslashes($error['message'])."</span><br />" : '';
        }

        return $msg;
    }

    function validateGetErrorArray()
    {
        $errors = $this->validateErrors;
        $msg = array();
        foreach($errors as $error)
        {
            if($error['message'] != 'ok'){
                $msg[] =  $error['message'];
            }
        }
        return $msg;
    }

    function validateGetErrorAlert() {

        $errors = $this->validateErrors;
        $msg = array();

        foreach ($errors as $error) {
            $msg[] = $error['message'];
        }

        return count($msg) > 0 ? implode("\r\n",$msg) : '';

    }

    function makeSafe($text)
    {
        $dbResource = cmsFramework::getConnection($this->_db);

        if(is_object($dbResource) && get_class($dbResource) == 'mysqli')
        {
            $quoted = mysqli_real_escape_string( $dbResource, $text );
        } else {
            $quoted = mysql_real_escape_string( $text, $dbResource );
        }
        return $quoted;
    }

    function Quote( $values )
    {
        !is_array($values) and $values = array($values);

        $dbResource = cmsFramework::getConnection();

        $mysqli = is_object($dbResource) && get_class($dbResource) == 'mysqli';

        foreach($values AS $key=>$text)
        {
            if(is_string($text)) {
                $values[$key] = '\'' . ($mysqli ? mysqli_real_escape_string( $dbResource, $text ) : mysql_real_escape_string( $text, $dbResource ) ) . '\'';
            }
            elseif(is_bool($text)) {
                $values[$key] = (int) $text;
            }
            else {
                $values[$key] = $text;
            }
        }

        return implode(',',$values);
    }

    function QuoteLike( $text )
    {
        $dbResource = cmsFramework::getConnection();

        if(is_object($dbResource) && get_class($dbResource) == 'mysqli')
        {
            $quoted = mysqli_real_escape_string( $dbResource, $text );
        } else {
            $quoted = mysql_real_escape_string( $text, $dbResource );
        }
        return '\'%' . $quoted . '%\'';
    }

    function __mergeArrays($queryData, $union = false)
    {
        $newQueryData = $queryData;

        $valid_keys = array('useTable','useKey','fields','field_count','conditions','joins','order','group','having','limit','offset');

        // elements that need to be sent as arrays
        $array_elements = array('fields','joins','conditions','group','having','order');

        foreach($valid_keys AS $key)
        {
            if(isset($queryData[$key]) && is_array($queryData[$key])) {

                if($union && $key == 'joins') {

                    $newQueryData[$key] = array_merge($newQueryData[$key],$this->$key);
                }
                else {

                    $newQueryData[$key] = array_merge($this->$key,$newQueryData[$key]);
                }

                $newQueryData[$key] = array_unique($newQueryData[$key]);

            } elseif (isset($queryData[$key]) && !is_array($queryData[$key]) && in_array($key,$array_elements)) {

                $newQueryData[$key] = array($newQueryData[$key]);

            } elseif (isset($this->$key)) {

                $newQueryData[$key] = $this->$key;
            }
        }

        return $newQueryData;
    }

    function __reformatArray($rows) {

        $results = array();

        if($rows && !empty($rows))
        {
            if($this->primaryKey)
            {
                foreach($rows AS $key=>$row) {

                    if(isset($row->{$this->primaryKey})) {
                        $primaryKey = $row->{$this->primaryKey};
                    } else {
                        $primaryKey = $key;
                    }

                    foreach((array) $row AS $key2=>$row2)
                    {
                        $col_var = explode('.',$key2);

                        if(count($col_var) == 2) {
                            $modelName = $col_var[0];
                            $modelKey =$col_var[1];
                        } else {
                            $modelName = $this->name;
                            $modelKey =$col_var[0];
                        }
                        $results[$primaryKey][$modelName][$modelKey] = $row2;
                    }
                }
            } else {
                foreach($rows AS $key=>$row) {
                    $results[$key] = (array) $row;
                }
            }
        }

        return $results;

    }

    function getErrorMsg()
    {
        return $this->_db->getErrorMsg();
    }

    function getQuery()
    {
        return $this->_db->getQuery();
    }

    function query($query, $type = 'query', $param = '')
    {
        $message = array();

        $query and $this->_db->setQuery($query);

        if($param != '') {

            $result = $this->_db->{$type}($param);
        }
        else {

            $result = $this->_db->{$type}();
        }

        // Debug
        if(S2_DEBUG == 1) {

            $debug_source = '*********' . get_class($this) . ' | Non-Model Query:';

            $debug_query = $debug_source . '<br />' . $this->_db->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->_db->getErrorMsg();

            $debug_query_error and s2Error::add($debug_source . '<br />' . $debug_query_error,'query_error');
        }

        return $result;
    }

    function changeKeys($rows, $modelName, $modelKey) {

        $results = array();

        foreach ($rows AS $row) {

            $results[$row[$modelName][$modelKey]] = $row;

        }

        return $results;

    }

    function cacheSessionSetCount($count,$conditionsArray) {

        !isset($this->conditions) and $this->conditions = array();

        $conditions = array_filter(array_merge($this->conditions,$conditionsArray));

        $findCount = cmsFramework::getSessionVar($this->name,'findCount');

        $findCount[md5($this->name.implode('',$conditions))] = $count;

        cmsFramework::setSessionVar($this->name,$findCount,'findCount');
    }

    function cacheSessionGetCount($conditionsArray) {

        !isset($this->conditions) and $this->conditions = array();

        $conditions = array_filter(array_merge($this->conditions,$conditionsArray));

        $findCount = cmsFramework::getSessionVar($this->name,'findCount');

        if(isset($findCount[md5($this->name.implode('',$conditions))])) {

            return $findCount[md5($this->name.implode('',$conditions))];
        }

        return false;

    }

    /**
     * Model callbacks
     */

    function afterFind($results) {
        return $results;
    }

    function beforeSave(&$data) {
        return true;
    }

    function afterSave($status) {}

    function beforeDelete($keyName, $values, $condition) {}

    function afterDelete($keyName, $values, $condition) {}

}
