<?php
/**
 *
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
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class MyClass_Action_Helper_MyCache extends Zend_Controller_Action_Helper_Abstract
{
    protected $cache;



    public function init()
    {
        $this->cache = Zend_Registry::get('cache');
        parent::init();
    }


    /**
     * Cleaning cache by specified role_id : Zend_Cache and data/tmp files
     */
    public function clearAllCacheRole($role_id)
    {
	    // remove Bacula ACLs
        $bacula_acl = new MyClass_BaculaAcl();
        $bacula_acl->removeCache();
        // remove Webacula ACLs        
        $this->cache->remove('MyClass_WebaculaAcl');
        // main menu cache
        if ($role_id)
            $this->cache->remove($role_id . '_main_menu');
    }


    /**
     * Cleaning all cache data and data/tmp files
     */
    public function clearAllCache()
    {
        // remove all cache for all users
        $this->cache->clean(Zend_Cache::CLEANING_MODE_ALL);
    }



}