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
        $select->from(array('roles' => 'webacula_roles'), array('id' , 'name', 'description', 'order_role'));
        $select->joinLeft(array('inherits' => 'webacula_roles'), 'inherits.id = roles.inherit_id', array('inherit_name' => 'name'));
        $select->order(array('roles.order_role, roles.id ASC'));
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt   = $select->query();
        $result = $stmt->fetchAll();
        return $result;
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
        if ( $users->fetchRow($this->getAdapter()->quoteInto('role_id = ?',    $role_id)) ||
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

    
}
