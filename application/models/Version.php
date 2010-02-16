<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
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
                $this->_name = 'version';
                break;
            default: // including mysql, sqlite
                $this->_name = 'Version';
        }
        parent::_setupTableName();
    }

    function getVesion()
	{
   		$select = new Zend_Db_Select($this->db);
    	$select->from('Version', 'VersionId');
    	$select->limit(1);
    	$res = $this->db->fetchOne($select);
		return $res;
	}

	/**
	 * Check Catalog DB version
	 * Сравнивает версию БД Каталога Bacula
	 *
     * @param $ver  valid version
	 * @return TRUE if correct
	 */
	function checkVesion($ver)
	{
    	$res = $this->getVesion();
		return ( $res == $ver );
	}

}