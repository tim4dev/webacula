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
    protected $_role_name = '';
    protected $_role_id   = null;
    protected $_cache_id  = null;


	public function __construct()
	{
		$this->config     = Zend_Registry::get('config');
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        Zend_Loader::loadClass('Wbroles');
        // Get current role_id, role_name
        $auth    = Zend_Auth::getInstance();
        $ident   = $auth->getIdentity();
        $this->_role_id   = $ident->role_id;
        $this->_role_name = $ident->role_name;
        $this->_cache_id  = $this->_role_id.'_acls2dim';
	}



	public function cleanCache() {
		/*
         * Cleaning cache. See also MyClass_BaculaAcl
         */
        $cache = Zend_Registry::get('cache');
        $cache->remove($this->_cache_id);
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
	    /*
	     * Cache
	     */
	    $cache = Zend_Registry::get('cache');
        // проверка, есть ли уже запись в кэше:
        if( !$acls2dim = $cache->load( $this->_cache_id ) ) {
            // промах кэша
            // get current role and all parents roles
            $table = new Wbroles();
            $roles = $table->getParents( $this->_role_id );
            // get all Bacula ACLs and all parents Bacula ACLs
            switch ($acl) {
                case 'command':
                    $select = $this->db->select()
                              ->from( array('c'=> $this->getAclTableName($acl) ), array() )
                              ->joinInner( array('dt'=>'webacula_dt_commands'), 'dt.id = c.dt_id', array('name'))
                              ->where('c.role_id IN (?)', $roles)
                              ->order('c.order_acl');
                break;

                default:
                    $select = $this->db->select()
                              ->from( $this->getAclTableName($acl), array('name') )
                              ->where('role_id IN (?)', $roles)
                              ->order('order_acl');
                break;
            }
            $stmt = $select->query();
            $acls2dim = $stmt->fetchAll(); // array
            // save to cache
            $cache->save($acls2dim, $this->_cache_id, array('bacula_acl') );
        }
        /* convert $acls2dim to one dimension array $acls1dim
         * and check '*' keyword ( '*all*' - allowed everything all )
         */
        $acls1dim = array();
        foreach($acls2dim as $acl2) {
            $acls1dim[] = $acl2['name'];
            if ( $acl2['name'] == '*all*' )
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
     * @return boolean
     */
    public function doOneBaculaAcl($resource, $field, $acl)
    {
        // check input parameters
        if ( empty($resource) ) return TRUE;
        if ( empty($field))
           throw new Exception(__METHOD__.' : "$field can not be empty"');
        /*
         * Cache
         */
        $cache = Zend_Registry::get('cache');
        // проверка, есть ли уже запись в кэше:
        if( !$acls2dim = $cache->load( $this->_cache_id ) ) {
            // промах кэша
            // get current role and all parents roles
            $table = new Wbroles();
            $roles = $table->getParents( $this->_role_id );
            // get all Bacula ACLs and all parents Bacula ACLs
            switch ($acl) {
            	case 'command':
                    $select = $this->db->select()
                              ->from( array('c'=> $this->getAclTableName($acl) ), array() )
                              ->joinInner( array('dt'=>'webacula_dt_commands'), 'dt.id = c.dt_id', array('name'))
                              ->where('c.role_id IN (?)', $roles)
                              ->order('c.order_acl');
            	break;

                default:
                    $select = $this->db->select()
                              ->from( $this->getAclTableName($acl), array('name') )
                              ->where('role_id IN (?)', $roles)
                              ->order('order_acl');
                break;
            }
            $stmt = $select->query();
            $acls2dim = $stmt->fetchAll(); // array
            // save to cache
            $cache->save($acls2dim,  $this->_cache_id, array('bacula_acl') );
        }
        /* convert $acls2dim to one dimension array $acls1dim
         * and check '*' keyword ( '*all*' - allowed everything all )
         */
        $acls1dim = array();
        foreach($acls2dim as $acl2) {
            $acls1dim[] = $acl2['name'];
            if ( $acl2['name'] == '*all*' )
                // allowed everything all
                return TRUE;
        }
        // do acls
        return (in_array($resource, $acls1dim)) ? TRUE : FALSE;
    }


}