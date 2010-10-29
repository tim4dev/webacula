<?php

/*
 * http://www.zftutorials.com/zend-acl/Zend-Acl-and-storing-roles-and-resources-in-a-DB-l183.html
 */

require_once 'Zend/Acl.php';

class MyClass_Acl extends Zend_Acl
{

    public function __construct()
    {
        // fetch all resources
        $table = new Wbresources();
        $resources = $table->fetchAllRecources();
        unset($table);
        // fetch all roles
        $table = new Wbroles();
        $roles = $table->fetchAllRoles();
        unset($table);

        //Loop roles and put them in an assoc array by ID
        $roleArray = array();
        foreach($roles as $r)   {
            $role = new Zend_Acl_Role($r['name']);
            //If inherit_name isn't null, have the role inherit from that, otherwise no inheriting
            if($r['inherit_name'] !== null)
                $this->addRole($role, $r['inherit_name']);
            else
                $this->addRole($role);
            $roleArray[$r['id']] = $role;
        }

        foreach($resources as $r)   {
            $resource = new Zend_Acl_Resource($r['name']);
            if ($r['role_id'] !== null)
                $role = $roleArray[$r['role_id']];
            $this->add($resource);
            $this->allow($role, $resource);
        }
        // Администратор не наследует ни от кого, но обладает всеми привилегиями
        $this->allow('root_role');
    }

}
