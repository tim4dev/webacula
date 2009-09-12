<?php
/**
 *  Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This file is part of Webacula.
 *
 * Webacula is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Webacula is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webacula.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Class for Logbook
 *
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
 
/*
 * workaround http://zendframework.com/issues/browse/ZF-7070
 * http://www.zfforums.com/zend-framework-components-13/databases-20/problem-postgres-2151.html#post11910
 */

class Wblogbook extends Zend_Db_Table
{
	public $db_adapter;
	
	public function __construct($config = array())
	{
		$this->db_adapter  = Zend_Registry::get('DB_ADAPTER_WEBACULA');
		$config['db']      = Zend_Registry::get('db_webacula'); // database
		$config['sequence']= true;
		parent::__construct($config);
	}
	
	protected function _setupTableName()
    {
        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
            $this->_name = 'wbLogBook';
            break;
        case 'PDO_PGSQL':
            $this->_name = 'wblogbook';
            break;
        }
        parent::_setupTableName();
    }

    protected function _setupPrimaryKey()
    {
        $this->_primary = 'logid';
        parent::_setupPrimaryKey();
    }

	/**
     * LogBook view
     *
     */
    function IndexLogBook($date_begin, $date_end, $sort_order)
    {
    	if ( empty($date_begin) )	{
    		$date_begin = date('Y-m-d', time()-2678400); // 31 days ago
    	}

    	if ( empty($date_end) )	{
    		$date_end = date('Y-m-d', time());
    	}

    	if ( empty($sort_order) )	{
    		$sort_order = 'DESC';
    	}
		
    	$db = Zend_Db_Table::getAdapter('db_webacula');
    	switch ($this->db_adapter) {
            case 'PDO_MYSQL':    	
				$db->query('SET NAMES utf8');
				$db->query('SET CHARACTER SET utf8');
		        break;
            case 'PDO_PGSQL':
            	$db->query("SET NAMES 'UTF8'");
                break;
    	}

    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);
    	$select->distinct();
    	$select->from(array('l' => 'wbLogBook'), array('logId', 'logDateCreate', 'logDateLast', 'logTxt', 'logTypeId', 'logIsDel'));
    	$select->joinLeft(array('t' => 'wbLogType'), 'l.logTypeId = t.typeId', array('typeId', 'typeDesc'));
    	$select->where("('$date_begin' <= CAST(l.logDateCreate AS DATE)) AND (CAST(l.logDateCreate AS DATE) <= '$date_end')");
    	$select->order(array('l.logDateCreate ' . $sort_order));
    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    	$result = $select->query();
		return $result;
    }

    /**
     * LogBook find by logId
     *
     */
    function findLogBookById($id_begin, $id_end, $sort_order)
    {
    	if ( !isset($id_begin, $id_end) )	{
    		return;
    	}

    	$db = Zend_Db_Table::getAdapter('db_webacula');
    	switch ($this->db_adapter) {
            case 'PDO_MYSQL':    	
				$db->query('SET NAMES utf8');
				$db->query('SET CHARACTER SET utf8');
		        break;
            case 'PDO_PGSQL':
            	$db->query("SET NAMES 'UTF8'");
                break;
    	}
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);
    	$select->distinct();
    	$select->from(array('l' => 'wbLogBook'), array('logId', 'logDateCreate', 'logDateLast', 'logTxt', 'logTypeId', 'logIsDel'));
    	$select->joinLeft(array('t' => 'wbLogType'), 'l.logTypeId = t.typeId', array('typeId', 'typeDesc'));
    	$select->where("('$id_begin' <= l.logId) AND (l.logId <= '$id_end')");
    	$select->order(array('l.logId ' . $sort_order));
    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    	$result = $select->query();
		return $result;
    }

    /**
     * LogBook full text search
     *
     */
    function findLogBookByText($id_text, $sort_order)
    {
    	if ( !isset($id_text) )	{
    		return;
    	}
    	$id_text = trim($id_text);

    	$db = Zend_Db_Table::getAdapter('db_webacula');
    	switch ($this->db_adapter) {
            case 'PDO_MYSQL':    	
				$db->query('SET NAMES utf8');
				$db->query('SET CHARACTER SET utf8');
		        break;
            case 'PDO_PGSQL':
            	$db->query("SET NAMES 'UTF8'");
                break;
    	}
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);
    	$select->distinct();
    	$select->from(array('l' => 'wbLogBook'), array('logId', 'logDateCreate', 'logDateLast', 'logTxt', 'logTypeId', 'logIsDel'));
    	$select->joinLeft(array('t' => 'wbLogType'), 'l.logTypeId = t.typeId', array('typeId', 'typeDesc'));
    	switch ($this->db_adapter) {
            case 'PDO_MYSQL':
    			$select->where(' MATCH(logTxt) AGAINST ("' . $id_text . '" WITH QUERY EXPANSION)');
    			break;
            case 'PDO_PGSQL':
				$str = preg_replace('/\s+/', ' & ', $id_text);
				$select->where(" to_tsvector(logtxt) @@ to_tsquery(" . $db->quote($str) . ")" );
                break;
    	}
		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
    	$result = $select->query();
		return $result;
    }

}
