<?php
/**
 * Copyright 2007, 2008, 2009, 2010 Yuri Timofeev tim4dev@gmail.com
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
    public $db;
    public $db_adapter;
    protected $_name = 'webacula_logbook';
    protected $_primary = 'logid';


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $config['db'] = $this->db;
        $config['sequence'] = true;
        parent::__construct($config);
    }



    /**
     * LogBook view
     *
     */
    function IndexLogBook ($date_begin, $date_end, $sort_order)
    {
        if (empty($date_begin)) {
            $date_begin = date('Y-m-d', time() - 2678400); // 31 days ago
        }
        if (empty($date_end)) {
            $date_end = date('Y-m-d', time());
        }
        if (empty($sort_order)) {
            $sort_order = 'DESC';
        }
        $db = Zend_Db_Table::getAdapter('db_bacula');
        $select = new Zend_Db_Select($db);
        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $select->where("('$date_begin' <= CAST(l.logDateCreate AS DATE)) AND (CAST(l.logDateCreate AS DATE) <= '$date_end')");
                $select->order(array('l.logDateCreate ' . $sort_order));
                break;
            case 'PDO_PGSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $select->where("('$date_begin' <= CAST(l.logDateCreate AS DATE)) AND (CAST(l.logDateCreate AS DATE) <= '$date_end')");
                $select->order(array('l.logDateCreate ' . $sort_order));
                break;
            case 'PDO_SQLITE':
                if (empty($date_begin)) {
                    $date_begin = date('Y-m-d H:i:s', time() - 2678400); // 31 days ago
                } else {
                    $date_begin = $date_begin . ' 00:00:00';
                }
                if (empty($date_end)) {
                    $date_end = date('Y-m-d H:i:s', time());
                } else {
                    $date_end = $date_end . ' 23:59:59';
                }
                // bug http://framework.zend.com/issues/browse/ZF-884
                // http://sqlite.org/pragma.html
                //$res = $db->query('PRAGMA short_column_names=1'); // not affected
                //$res = $db->query('PRAGMA full_column_names=0'); // not affected
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logid' => 'logId' , 'logdatecreate' => 'logDateCreate' , 'logdatelast' => 'logDateLast' , 'logtxt' => 'logTxt' , 'logtypeid' => 'logTypeId' , 'logisdel' => 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeid' => 'typeId' , 'typedesc' => 'typeDesc'));
                //$select->where("(CAST('$date_begin' AS DATETIME) <= l.logDateCreate) AND (l.logDateCreate <= CAST('$date_end' AS DATETIME))");
                $select->where("('$date_begin' <= l.logDateCreate) AND (l.logDateCreate <= '$date_end')");
                //$select->where("(datetime('now','-31 day') <= l.logDateCreate) AND (l.logDateCreate <= datetime('now'))");
                $select->order(array('l.logDateCreate ' . $sort_order));
                break;
        }
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }


    /**
     * LogBook find by logId
     *
     */
    function findLogBookById ($id_begin, $id_end, $sort_order)
    {
        if (! isset($id_begin, $id_end)) {
            return;
        }
        $db = Zend_Db_Table::getAdapter('db_bacula');
        $select = new Zend_Db_Select($db);
        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $select->where("('$id_begin' <= l.logId) AND (l.logId <= '$id_end')");
                $select->order(array('l.logId ' . $sort_order));
                break;
            case 'PDO_PGSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $select->where("('$id_begin' <= l.logId) AND (l.logId <= '$id_end')");
                $select->order(array('l.logId ' . $sort_order));
                break;
            case 'PDO_SQLITE':
                // bug http://framework.zend.com/issues/browse/ZF-884
                // http://sqlite.org/pragma.html
                //$res = $db->query('PRAGMA short_column_names=1'); // not affected
                //$res = $db->query('PRAGMA full_column_names=0'); // not affected
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logid' => 'logId' , 'logdatecreate' => 'logDateCreate' , 'logdatelast' => 'logDateLast' , 'logtxt' => 'logTxt' , 'logtypeid' => 'logTypeId' , 'logisdel' => 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeid' => 'typeId' , 'typedesc' => 'typeDesc'));
                $select->where("('$id_begin' <= l.logId) AND (l.logId <= '$id_end')");
                $select->order(array('l.logId ' . $sort_order));
                break;
        }
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }


    /**
     * LogBook full text search
     *
     */
    function findLogBookByText ($id_text, $sort_order)
    {
        if (! isset($id_text)) {
            return;
        }
        $id_text = trim($id_text);
        $db = Zend_Db_Table::getAdapter('db_bacula');
        $select = new Zend_Db_Select($db);
        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $select->where(' MATCH(logTxt) AGAINST ("' . $id_text . '" WITH QUERY EXPANSION)');
                break;
            case 'PDO_PGSQL':
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logId' , 'logDateCreate' , 'logDateLast' , 'logTxt' , 'logTypeId' , 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeId' , 'typeDesc'));
                $str = preg_replace('/\s+/', ' & ', $id_text);
                $select->where(" to_tsvector(logtxt) @@ to_tsquery(" . $db->quote($str) . ")");
                break;
            case 'PDO_SQLITE':
                // see also http://www.sqlite.org/cvstrac/wiki?p=FtsOne "FTS1 module is available in SQLite version 3.3.8 and later
                $select->distinct();
                $select->from(array('l' => 'webacula_logbook'), array('logid' => 'logId' , 'logdatecreate' => 'logDateCreate' , 'logdatelast' => 'logDateLast' , 'logtxt' => 'logTxt' , 'logtypeid' => 'logTypeId' , 'logisdel' => 'logIsDel'));
                $select->joinLeft(array('t' => 'webacula_logtype'), 'l.logTypeId = t.typeId', array('typeid' => 'typeId' , 'typedesc' => 'typeDesc'));
                $select->where(' logTxt LIKE  "%' . $id_text . '%"');
                break;
        }
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }
}
