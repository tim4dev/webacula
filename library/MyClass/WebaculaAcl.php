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
 * Fetch all Roles and all Resources.
 * And create Zend_Acl_Resource object which contains allow rules for all Roles.
 */
require_once 'Zend/Acl.php';

class MyClass_WebaculaAcl extends Zend_Acl
{

    public function __construct()
    {
        /*
         * fetch all roles
         */
        $table = new Wbroles();
        $roles = $table->fetchAllRoles();
        unset($table);
        // Loop roles and put them in an assoc array by ID
        $roleArray = array();
        foreach($roles as $r)   {
            $role = new Zend_Acl_Role($r['name']);
            // If inherit_name isn't null, have the role inherit from that, otherwise no inheriting
            if ( ( $r['inherit_name'] !== null) && ($r['inherit_name'] !== $r['name']) )
                $this->addRole($role, $r['inherit_name']);
            else
                $this->addRole($role);
            $roleArray[$r['id']] = $role;
        }
        /*
         *  fetch all resources
         *  because the Resource must be unique identifier
         */
        $table = new Wbresources();
        $resources = $table->fetchAllResources();
        foreach($resources as $r)   {
            $resource = new Zend_Acl_Resource($r['name']);
            $this->addResource($resource);
        }
        /*
         * establish a correspondence: roles => resources
         */
        $resources_roles = $table->fetchAllResourcesAndRoles();
        foreach($resources_roles as $r)   {
            if ($r['role_id'] !== null) {
                $role = $roleArray[$r['role_id']];
                // the Resource must be unique identifier
                $this->allow($role, $r['resource_name']);
            }
        }
        // Администратор не наследует ни от кого, но обладает всеми привилегиями
        $this->allow('root_role');
    }


}
