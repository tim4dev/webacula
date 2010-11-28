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

class Wbresources extends Zend_Db_Table
{
	protected $_name    = 'webacula_resources';
    protected $_primary = 'id';
    protected $_parents = array();

    public $db;
    public $db_adapter;


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        parent::__construct($config);
    }



    public function fetchAllResources() {
        /*
         SELECT DISTINCT name
         FROM webacula_dt_resources;
         */
        $select = new Zend_Db_Select($this->db);
        $select->from('webacula_dt_resources', 'name')
                ->distinct();
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }



    public function fetchAllResourcesAndRoles() {
        /*
         SELECT res.id, res.role_id, dt.name, dt.description
         FROM webacula_resources AS res
         LEFT JOIN webacula_dt_resources AS dt ON res.dt_id = dt.id
         */
        $select = new Zend_Db_Select($this->db);
        $select->from(array('res' => 'webacula_resources'), array('id', 'role_id'))
                ->joinLeft(array('dt' => 'webacula_dt_resources'), 'res.dt_id = dt.id', array('resource_name'=>'name'));
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }

    

    /**
     * Update Webacula Resource ACLs
     * @param <type> $resources FK to 'webacula_dt_resources' table
     * @param <type> $role_id
     * @return int affected rows
     */
    public function updateResources($resources, $role_id)
    {
        // delete all ACLs
        $where = $this->getAdapter()->quoteInto('role_id = ?', $role_id);
        $this->delete($where);
        // set ACLs again
        $i = 0;
        if ($resources)
            foreach( $resources as $v ) {
                $data = array(
                    'role_id' => $role_id,
                    'dt_id'   => $v );
                $this->insert($data);
                $i++;
            }
    	return $i;
    }



}