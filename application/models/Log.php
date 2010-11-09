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
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
class Log extends Zend_Db_Table
{
    public $db;
    public $db_adapter;


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        parent::__construct($config);
    }


    protected function _setupTableName ()
    {
        switch ($this->db_adapter) {
            case 'PDO_PGSQL':
                $this->_name = 'log';
                break;
            default: // including mysql, sqlite
                $this->_name = 'Log';
        }
        parent::_setupTableName();
    }


    protected function _setupPrimaryKey ()
    {
        $this->_primary = 'logid';
        parent::_setupPrimaryKey();
    }


    
    function getById ($jobid)
    {
        // do Bacula ACLs
        Zend_Loader::loadClass('Job');
		$table = new Job();
        if ( !$table->isJobIdExists($jobid) )
                return FALSE;
        $select = new Zend_Db_Select($this->db);
        switch ($this->db_adapter) {
            case 'PDO_SQLITE':
                // bug http://framework.zend.com/issues/browse/ZF-884
                $select->distinct();
                $select->from(array('l' => 'Log'), array('logid' => 'LogId' , 'jobid' => 'JobId' , 'LogTime' => 'Time' , 'logtext' => 'LogText'));
                $select->where("JobId = ?", $jobid);
                $select->order(array('LogId' , 'LogTime'));
                break;
            default: // mysql, postgresql
                $select->distinct();
                $select->from(array('l' => 'Log'), array('LogId' , 'JobId' , 'LogTime' => 'Time' , 'LogText'));
                $select->where("JobId = ?", $jobid);
                $select->order(array('LogId' , 'LogTime'));
        }
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        return $stmt->fetchAll();
    }

    
}