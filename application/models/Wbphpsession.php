<?php
/**
 * Copyright 2011 Yuri Timofeev tim4dev@gmail.com
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

class Wbphpsession extends Zend_Db_Table
{
	protected $_name    = 'webacula_php_session';
    protected $_primary = 'id';

    public $db;
    public $db_adapter;


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        parent::__construct($config);
    }


    /**
     * Delete session record(s) when delete user login
     * @param int $user_id
     */
    public function deleteSession( $user_id )
    {
        // get user login by user Id
        Zend_Loader::loadClass('Wbusers');
        $users = new Wbusers();
        $where = $users->getAdapter()->quoteInto('id = ?', $user_id);
        $row = $users->fetchRow($where);
        unset($where);
        if ( isset($row->login) ) {
            $where = $this->getAdapter()->quoteInto('login = ?', $row->login);
            $this->delete($where);
        } else {
            throw new Exception(__METHOD__.' : User login not found');
        }
    }


}