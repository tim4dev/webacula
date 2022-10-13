<?php
/**
 * Copyright 2010, 2014 Yuriy Timofeev tim4dev@gmail.com
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

class Wbusers extends Zend_Db_Table
{
    protected $_name    = 'webacula_users';
    protected $_primary = 'id';
    protected $_hasher;

    public $db;
    public $db_adapter;


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->_hasher    = new MyClass_PasswordHash();
        parent::__construct($config);
    }

    public function updateLoginStat($login)
    {
        if ( empty($login) )
            throw new Exception(__METHOD__.' : "Empty input parameters"');
        $where = $this->getAdapter()->quoteInto('login = ?', $login);
        $data = array(
            'last_login' => date("Y-m-d H:i:s", time()),
            'last_ip'    => $_SERVER['REMOTE_ADDR']
        );
        $this->update($data, $where);
    }

    public function fetchUser($login)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from(array('user1' => 'webacula_users'),
                array('id' , 'login', 'name', 'email', 'create_login', 'last_login', 'last_ip', 'active', 'role_id'));
        $select->joinLeft(array('role1' => 'webacula_roles'), 'user1.role_id = role1.id',
                array('role_name' => 'name', 'role_id' => 'id'));
        $select->where("login = ?", $login);
        //$sql = $select->__toString(); var_dump($sql); exit; // for !!!debug!!!
        $stmt   = $select->query();
        $result = $stmt->fetchAll();
        return $result;
    }    
    
    public function fetchAllUsers($order = 'id')
    {
        $select = new Zend_Db_Select($this->db);
        $select->from(array('user1' => 'webacula_users'),
                array('id' , 'login', 'name', 'email', 'create_login', 'last_login', 'last_ip', 'active', 'role_id'));
        $select->joinLeft(array('role1' => 'webacula_roles'), 'user1.role_id = role1.id',
                array('role_name' => 'name', 'role_id' => 'id'));
        if ($order)
            $select->order(array($order.' ASC'));
        //$sql = $select->__toString(); var_dump($sql); exit; // for !!!debug!!!
        $stmt   = $select->query();
        $result = $stmt->fetchAll();
        return $result;
    }

    /**
     * @param type $login
     * @param type $password
     * return TRUE if Authentication succeeded
     */
    public function checkPassword($login, $password)
    {
        $select = $this->select();
        $select->where('login = ?', $login)
               ->where('active = 1');
        $row = $this->fetchRow($select);
        if ( isset($row->pwd)) {
            return $this->_hasher->CheckPassword($password, $row->pwd);
        } else {
            return FALSE;
        }
        
    }
    
    public function insert(array $data)
    {
        $data['create_login'] = date("Y-m-d H:i:s", time());
        /* password hash */
        if ( ! empty($data['pwd']) )
        {
            $data['pwd'] = $this->_hasher->HashPassword( $data['pwd'] );
            if ( strlen($data['pwd']) < 20 ) {
                throw new Exception(__METHOD__.' : "Internal error. Failed to hash new password"');
            }
        }
        parent::insert($data);
    }
    
    public function update(array $data, $where)
    {
        /* password hash */
        if ( ! empty($data['pwd']) )
        {
            $data['pwd'] = $this->_hasher->HashPassword( $data['pwd'] );
            if ( strlen($data['pwd']) < 20 ) {
                throw new Exception(__METHOD__.' : "Internal error. Failed to hash new password"');
            }
        }
        parent::update($data, $where);
    }
   
}
