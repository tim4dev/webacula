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
/*
 * Webacula realizes of the following ACLs features of Bacula:
 * JobACL
 * ClientACL
 * StorageACL
 * PoolACL
 * FileSetACL
 * CommandACL
 * WhereACL
 * ScheduleACL not used, not implemented
 * CatalogACL  not used, not implemented
 *
 * The special keyword '*' (asterisk) can be specified in any of the access control lists.
 * When this keyword (asterisk) is present, any resource will be accepted.
 *
 * See also "Bacula Main Reference -> Configuring the Director -> The Console Resource".
 */

class MyClass_BaculaAcl
{

	protected $config;
	protected $db;
    protected $db_adapter;
    protected $bacula_acls = array(
        'job',
        'client',
        'storage',
        'pool',
        'fileset',
        'command',
        'where'
    );


	public function __construct()
	{
		$this->config     = Zend_Registry::get('config');
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        Zend_Loader::loadClass('Wbroles');
	}


	/**
	 * Return ACLs Tablename
	 *
	 * @param  string  $acl
	 * @return string  ACLs Tablename like 'webacula_job_acl' and so on
	 */
	protected function getAclTableName($acl) {
        return (in_array($acl, $this->bacula_acls)) ? 'webacula_'.$acl.'_acl' : FALSE;
	}


	protected function getCurrentRoleId() {
        // current user id
        $auth    = Zend_Auth::getInstance();
        $ident   = $auth->getIdentity();
        return ($ident) ? $ident->role_id : FALSE;
	}


	/**
	 * Main function of BaculaAcl
	 * Get all Bacula ACLs from Database by current $user_id and apply to all elements of $list
	 *
	 * @param  array[0-9][keys]  $list
	 * @param  string            $field
	 * @param  string            $acl  one of 'job', 'client', 'storage', etc.
	 * @return array
	 */
	public function doBaculaAcl($list, $field, $acl)
	{
	    // check input parameters
	    if ( empty($list) ) return NULL;
	    if ( !in_array($acl, $this->bacula_acls) )
	       throw new Exception(__METHOD__.' : "Invalid $acl parameter"');
	    if ( empty($field))
	       throw new Exception(__METHOD__.' : "$field can not be empty"');
	    // get current role and all parents roles
	    $table = new Wbroles();
	    $roles = $table->getParents( $this->getCurrentRoleId() );
	    // get all Bacula ACLs and all parents Bacula ACLs
        $select = $this->db->select()
                        ->from( $this->getAclTableName($acl), array('name') )
                        ->where('role_id IN (?)', $roles)
                        ->order('order_acl');
        $stmt = $select->query();
        $acls2dim = $stmt->fetchAll(); // array
        /* convert $acls2dim to one dimension array $acls1dim
         * and check '*' keyword ( '*' - allowed everything all )
         */
        $acls1dim = array();
        foreach($acls2dim as $acl2) {
            $acls1dim[] = $acl2['name'];
            if ( $acl2['name'] == '*' )
                // allowed everything all
                return $list;
        }
        // do acls
	    $new_list = array();
        foreach($list as $line) {
            if ( in_array($line[$field], $acls1dim) )
                $new_list[] = $line;
        }
        return $new_list;
	}



	/**
     * Get all Bacula ACLs from Database by current $user_id and apply to $resource
     *
     * @param  string  $resource
     * @param  string  $field
     * @param  string  $acl  one of 'job', 'client', 'storage', etc.
     * @return array
     */
    public function doOneBaculaAcl($resource, $field, $acl)
    {
        // check input parameters
        if ( empty($resource) ) return TRUE;
        if ( empty($field))
           throw new Exception(__METHOD__.' : "$field can not be empty"');
        // get current role and all parents roles
        $table = new Wbroles();
        $roles = $table->getParents( $this->getCurrentRoleId() );
        // get all Bacula ACLs and all parents Bacula ACLs
        $select = $this->db->select()
                        ->from( $this->getAclTableName($acl), array('name') )
                        ->where('role_id IN (?)', $roles)
                        ->order('order_acl');
        $stmt = $select->query();
        $acls2dim = $stmt->fetchAll(); // array
        /* convert $acls2dim to one dimension array $acls1dim
         * and check '*' keyword ( '*' - allowed everything all )
         */
        $acls1dim = array();
        foreach($acls2dim as $acl2) {
            $acls1dim[] = $acl2['name'];
            if ( $acl2['name'] == '*' )
                // allowed everything all
                return $list;
        }
        // do acls
        return (in_array($resource, $acls1dim)) ? TRUE : FALSE;
    }


}