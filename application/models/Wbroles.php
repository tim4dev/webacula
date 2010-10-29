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

class Wbroles extends Zend_Db_Table
{
	protected $_name    = 'webacula_roles';
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



    /*
     * возвращает одномерный массив id всех родителей данного $id, включая его самого
     */
    public function getParents($id) {
        $this->_getAllParents($id);
        return (! empty($this->parents) ? $this->parents : FALSE);
    }


    protected function _getAllParents($id) {
        $select = $this->select();
        $select->where('id = ?', $id)
           ->order('id');
        $row = $this->fetchRow($select);
        $this->parents[] = $row->id;
        if ($row->inherit_id != NULL)
            $this->_getAllParents($row->inherit_id);
    }

    public function fetchAllRoles() {
        /*
           SELECT roles.id, roles.name, inherits.name AS inherit_name
           FROM webacula_roles AS roles
           LEFT JOIN webacula_roles AS inherits ON inherits.id = roles.inherit_id
           ORDER BY roles.inherit_id, roles.order_roles ASC
        */
        $select = new Zend_Db_Select($this->db);
        $select->from(array('roles' => 'webacula_roles'), array('id' , 'name'));
        $select->joinLeft(array('inherits' => 'webacula_roles'), 'inherits.id = roles.inherit_id', array('inherit_name' => 'name'));
        $select->order(array('roles.inherit_id, roles.order_role ASC'));
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query();
        return $result;
    }

}
?>