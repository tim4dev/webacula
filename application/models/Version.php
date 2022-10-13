<?php
/**
 * Copyright 2010 Yuriy Timofeev tim4dev@gmail.com
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
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
class Version extends Zend_Db_Table
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
                $this->_name = 'webacula_version';
                break;
            default: // including mysql, sqlite
                $this->_name = 'webacula_version';
        }
        parent::_setupTableName();
    }

    function getVersion()
    {
   	$select = new Zend_Db_Select($this->db);
    	$select->from($this->_name, 'VersionId');
    	$select->limit(1);
    	$res = $this->db->fetchOne($select);
	return $res;
    }

    /**
     * Check Catalog DB version
     * Compare the version of the Bacula Catalog DB
     *
     * @param $ver  valid version
     * @return TRUE if correct
     */
     function checkVersion($ver)
     {
    	$res = $this->getVersion();
	return ( $res == $ver );
     }

     function getDatabaseSize()
     {
        $config = new Zend_Config_Ini('../application/config.ini');
        $db_name = $config->general->db->config->dbname;
        if($this->db_adapter == 'PDO_MYSQL'){
            if (version_compare($this->db->getServerVersion(), '5.0.0') >= 0) {
               $query = "select sum(data_length + index_length) as db_size from information_schema.tables where table_schema = '$db_name'";
               $stmt   = $this->_db->query($query);
               $db_size = $stmt->fetch();
               return $db_size['db_size'];
            } else {
               return 0;
            }
        } else if($this->db_adapter == 'PDO_PGSQL'){
            $query = "SELECT pg_database_size('$db_name') AS db_size";
            $stmt   = $this->_db->query($query);
            $db_size = $stmt->fetch();
            return $db_size['db_size'];
        } else {
             return 0;
        }
     }
}
