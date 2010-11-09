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

class Client extends Zend_Db_Table
{
    public $db;
    public $db_adapter;
    protected $bacula_acl; // bacula acl

    public function __construct($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->bacula_acl = new MyClass_BaculaAcl();
        parent::__construct($config);
    }

   protected function _setupTableName()
    {
        switch ($this->db_adapter) {
        case 'PDO_PGSQL':
            $this->_name = 'client';
            break;
        default:  // including mysql, sqlite
            $this->_name = 'Client';
        }
        parent::_setupTableName();
    }

    protected function _setupPrimaryKey()
    {
        $this->_primary = 'clientid';
        parent::_setupPrimaryKey();
    }


    public function fetchAll($where = null, $order = null, $count = null, $offset = null)
    {
        $res = parent::fetchAll($where, $order, $count, $offset)
                    ->toArray();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl($res, 'name', 'client');
    }


    /**
      * Get Client name
      *
      * @return Client name, or "" if not exist
      * @param integer $jobid
      */
    function getClientName($jobid)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from(array('j' => 'Job'), array('JobId', 'ClientId'));
        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('c.Name'));
        $select->where("j.JobId = ?", $jobid);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        $res  = $stmt->fetchAll();
        return $res[0]['name'];
    }


    function getClientId($client_name)
    {
        $select = new Zend_Db_Select($this->_db);
        $select->from('Client');
        $select->where("Name = ?", $client_name);
        $select->limit(1);
        $stmt = $select->query();
        $res = $stmt->fetch();
        return $res['clientid'];
    }

}