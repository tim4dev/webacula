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
    protected $parentIds   = array();
    protected $parentNames = array();

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
    public function getParentIds($id) {
        $this->parentIds = null;
        $this->_getAllParentIds($id);
        return (! empty($this->parentIds) ? $this->parentIds : FALSE);
    }



    /*
     * возвращает одномерный массив id и имен всех родителей данного $id, НЕ включая его самого:
     * key[role_id] => role_name
     */
    public function getParentNames($id) {
        $this->parentNames = null;
        $this->_getAllParentIds($id);
        // исключаем самого себя
        if ( $this->parentNames )
            unset($this->parentNames[$id]);
        return (! empty($this->parentNames) ? $this->parentNames : FALSE);
    }


    protected function _getAllParentIds($id) {
        $select = $this->select();
        $select->where('id = ?', $id)
           ->order('id');
        $row = $this->fetchRow($select);
        $this->parentIds[]   = $row->id;
        $this->parentNames[$row->id] = $row->name;
        if ( isset($row->inherit_id) && ($row->inherit_id != 0) && ($row->inherit_id != $row->id) )
            $this->_getAllParentIds($row->inherit_id);
    }


    public function fetchAllRoles() {
        /*
           SELECT roles.id, roles.name, inherits.name AS inherit_name
           FROM webacula_roles AS roles
           LEFT JOIN webacula_roles AS inherits ON inherits.id = roles.inherit_id
           ORDER BY roles.inherit_id, roles.order_role ASC
        */
        $select = new Zend_Db_Select($this->db);
        $select->from(array('roles' => 'webacula_roles'), array('id' , 'name', 'description', 'order_role', 'inherit_id'));
        $select->joinLeft(array('inherits' => 'webacula_roles'), 'inherits.id = roles.inherit_id', array('inherit_name' => 'name'));
        $select->order(array('roles.order_role, roles.id ASC'));
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt   = $select->query();
        return $stmt->fetchAll();
    }


    public function update(array $data, $where)
    {
        $row = $this->fetchRow($where);
        if ( empty($data['inherit_id']) )
            $data['inherit_id'] = null;
        // Не должно быть : role id != inherit_id в одной и той же записи
        if ( isset($row->id) && isset($data['inherit_id']) )
            if ( $row->id == $data['inherit_id'] ) {
                $translate = Zend_Registry::get('translate');
                throw new Zend_Exception( $translate->_('Role Id and Inherited Role Id must not be identical') );
                return 0;
            }
        return parent::update($data, $where);
    }


    public function delete($where, $role_id = null)
    {
        if ( empty($role_id) )
            throw new Exception(__METHOD__.' : "Empty input parameters"');
        /*
         * если есть ссылки из таблиц: webacula_roles или webacula_users
         * то удаление не производить
         */
        Zend_Loader::loadClass('Wbusers');
        $users = new Wbusers();
        //
        $select = $this->select()->where('inherit_id = ?', $role_id)
                        ->where('inherit_id != id');
        $rows = $this->fetchAll($select);
        if ( $users->fetchRow($this->getAdapter()->quoteInto('role_id = ?', $role_id)) ||
             ($rows->count() > 0) )
        {
            $translate = Zend_Registry::get('translate');
            throw new Zend_Exception( $translate->_('Can not delete. Role is used.') );
        } else {
            /*
             * delete cascade
             */
            $arr_table = array(
                'WbCommandACL',
                'Wbresources',
                'WbStorageACL',
                'WbPoolACL',
                'WbClientACL',
                'WbFilesetACL',
                'WbJobACL',
                'WbWhereACL'
            );
            $where_tbl = $this->getAdapter()->quoteInto('role_id = ?', $role_id);
            foreach ($arr_table as $tbl) {
                Zend_Loader::loadClass($tbl);
                $table = new $tbl();
                $table->delete($where_tbl);
                unset($table);
            }
            // delete main record
            return parent::delete($where);
        }
    }



    public function getBaculaFill($table_bacula, $table_webacula, $role_id)
    {
        if ($this->db_adapter == 'PDO_PGSQL')
            $table_bacula = strtolower($table_bacula);
        if ( ($table_bacula === 'FileSet') || $table_bacula === 'fileset' ) {
            $stmt = $this->db->query(
           'SELECT DISTINCT Fileset AS name
            FROM '. $this->db->quoteIdentifier($table_bacula) .'
            WHERE NOT Fileset IN
            (
                SELECT DISTINCT name FROM '. $this->db->quoteIdentifier($table_webacula) .
                ' WHERE role_id = '. $this->db->quote($role_id) .'
            )' );
        } else {
            $stmt = $this->db->query(
                'SELECT DISTINCT Name
                FROM '. $this->db->quoteIdentifier($table_bacula) .'
                WHERE NOT Name IN
                (
                    SELECT DISTINCT name FROM '. $this->db->quoteIdentifier($table_webacula) .
                    ' WHERE role_id = '. $this->db->quote($role_id) .'
                )' );
        }
        return $stmt->fetchAll();
    }



    public function insertBaculaFill($webacula, $role_id, $data)
    {
        Zend_Loader::loadClass($webacula);
        $table = new $webacula();
        foreach ($data as $v) {
            $select = $table->select()->where('name = ?', $v)
                        ->where('role_id = ?', $role_id);
            $rows = $table->fetchAll($select);
            unset($select);
            if ($rows->count() == 0) {
                /*
                 *  select MAX order_acl : select max(order_acl) from webacula_client_acl where role_id=2;
                 */
                $select = $table->select();
                $select->from($table, array('MAX(order_acl) AS '. $this->db->quoteIdentifier('max_order')) )
                      ->where('role_id = ?', $role_id);
                $row = $table->fetchRow($select);
                // insert data
                $data = array(
                    'role_id' => $role_id,
                    'name'    => $v
                );
                if ( $row->max_order )
                    $data['order_acl'] = round( ($row->max_order + 10)/10, 0 ) * 10;
                $table->insert($data);
            }
        }
        unset($table);
    }



    /**
     * List users of those who use this role
     * @param <type> $role_id
     */
    public function listWhoUsersUseRole($role_id) {
        if ( empty($role_id) )
            throw new Exception(__METHOD__.' : "Empty input parameters"');
        Zend_Loader::loadClass('Wbusers');
        $user_table = new Wbusers();
        return $user_table->fetchAll($this->getAdapter()->quoteInto('role_id = ?', $role_id));
    }



    /**
     * List roles of those who use this role
     * @param <type> $role_id
     */
    public function listWhoRolesUseRole($role_id) {
        if ( empty($role_id) )
            throw new Exception(__METHOD__.' : "Empty input parameters"');
        $select = $this->select()->where('inherit_id = ?', $role_id)
                                 ->where('inherit_id != id');
        return $this->fetchAll($select);
    }




}