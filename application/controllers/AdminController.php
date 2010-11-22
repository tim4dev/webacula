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
require_once 'Zend/Controller/Action.php';

class AdminController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Wbroles');
        Zend_Loader::loadClass('Wbusers');
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('admin');
    }



    public function indexAction() {

    }



    public function roleIndexAction() {
        $roles = new Wbroles();
        $this->view->result = $roles->fetchAllRoles();
        $this->view->title  = 'Webacula :: ' . $this->view->translate->_('Roles');
    }



    public function roleUpdateAction() 
    {
        $role_id = $this->_request->getParam('role_id', 0);
        /*
         * get Role name, inherited id
         */
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $role  = $table->fetchRow($table->getAdapter()->quoteInto('id = ?', $role_id));
        unset ($table);
        /**********************************
         * Role form
         **********************************/
        Zend_Loader::loadClass('FormRole');
        $form_role = new FormRole();
        // fill form
        $form_role->populate( array(
                'action_id'  => 'update',
                'role_id'    => $role_id,
                'name_role'  => $role->name,
                'description_role' => $role->description,
                'order_role' => $role->order_role,
                'inherit_id' => $role->inherit_id
            ));
        $form_role->setAction( $this->view->url() );
        $this->view->form_role      = $form_role;
        /**********************************
         * WebaculaACL form
         **********************************/
        Zend_Loader::loadClass('FormWebaculaACL');
        $form_webacula = new FormWebaculaACL();
        // get resources
        Zend_Loader::loadClass('WbDtResources');
        $table = new Wbresources();
        $wbresources = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        $webacula_resources = null;
        foreach( $wbresources as $v) {
            $webacula_resources[] = $v->dt_id;
        }
        // fill form
        $form_webacula->populate( array(
                'role_id'    => $role_id,
                'action_id'  => 'update',
                'role_id'    => $role_id
            ));
        if ( isset($webacula_resources) )
            $form_webacula->populate( array(
                'webacula_resources' => $webacula_resources,
                'role_id'            => $role_id
            ));
        $form_webacula->setAction( $this->view->url() );
        $this->view->form_webacula  = $form_webacula;
        /**********************************
         * view
         **********************************/
        // Inherited roles name
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $this->view->roles = $table->getParentNames( $role_id );
        // title
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role update') .
                ' :: [' . $role_id . '] ' . $role->name;
    }



}