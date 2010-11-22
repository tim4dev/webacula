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
         * get Role name
         */
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $row   = $table->fetchRow($table->getAdapter()->quoteInto('id = ?', $role_id));
        $role_name = $row->name;
        unset ($table);
        /*
         * form
         */
        Zend_Loader::loadClass('FormRole');
        $form = new FormRole();
        // get resources
        Zend_Loader::loadClass('WbDtResources');
        $table = new Wbresources();
        $wbresources = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        $webacula_resources = null;
        foreach( $wbresources as $v) {
            $webacula_resources[] = $v->dt_id;
        }
        // fill form
        $form->populate( array(
                'role_id' => $role_id
            ));
        if ( isset($webacula_resources) )
            $form->populate( array(
                'webacula_resources' => $webacula_resources,
                'role_id'            => $role_id
            ));
        // form
        $form->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'decorators/formRole.phtml',
                'form'=> $form
            ))
        ));
        // view
        // roles names
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $this->view->roles = $table->getParentNames( $role_id );
        // other
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role update') .
                ' :: [' . $role_id . '] ' . $role_name;
        $this->view->form  = $form;
    }



}