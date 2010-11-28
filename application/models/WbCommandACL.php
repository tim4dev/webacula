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

class WbCommandACL extends Zend_Db_Table
{
	protected $_name    = 'webacula_command_acl';
    protected $_primary = 'id';
    protected $_parents = array();


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        parent::__construct($config);
    }



    /**
     * Update Bacula Command ACLs
     * @param <type> $commands FK to 'webacula_dt_commands' table
     * @param <type> $role_id
     * @return int affected rows
     */
    public function updateCommands($commands, $role_id)
    {
        // delete all ACLs
        $where = $this->getAdapter()->quoteInto('role_id = ?', $role_id);
        $this->delete($where);
        // set ACLs again
        $i = 0;
        if ($commands)
            foreach( $commands as $v ) {
                $data = array(
                    'role_id' => $role_id,
                    'dt_id'   => $v );
                $this->insert($data);
                $i++;
            }
    	return $i;
    }



}