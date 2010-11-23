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
        // tables
        Zend_Loader::loadClass('Wbroles');
        Zend_Loader::loadClass('Wbusers');
        Zend_Loader::loadClass('WbStorageACL');
        Zend_Loader::loadClass('WbPoolACL');
        Zend_Loader::loadClass('WbClientACL');
        Zend_Loader::loadClass('WbFilesetACL');
        // forms
        Zend_Loader::loadClass('FormRole');
        Zend_Loader::loadClass('FormWebaculaACL');
        Zend_Loader::loadClass('FormStorageACL');
        Zend_Loader::loadClass('FormPoolACL');
        Zend_Loader::loadClass('FormClientACL');
        Zend_Loader::loadClass('FormFilesetACL');
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



    public function filesetAddAction()
    {
        if ( $this->_request->isPost() ) {
            $role_id   = $this->_request->getParam('role_id');
            $role_name = $this->_request->getParam('role_name');
            if ( empty ($role_id) ) {
                throw new Exception(__METHOD__.' : "Empty $role_id parameter"');
                return;
            }
            $form_fileset = new FormFilesetACL();
            // Проверяем валидность данных формы
            if ( $form_fileset->isValid($this->_getAllParams()) )
            {
                // insert data to table
                $table = new WbFilesetACL();
                $data = array(
                    'name'      => $this->_request->getParam('name'),
                    'order_acl' => $this->_request->getParam('order'),
                    'role_id'   => $role_id
                );
                try {
                    $table->insert($data);
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                }
            } else {
                $this->view->errors = $form_fileset->getMessages();
                $form_fileset->setAction( $this->view->baseUrl . '/admin/fileset-add' );
                $this->view->form_fileset = $form_fileset;
                $this->view->title = 'Webacula :: ' . $this->view->translate->_('Fileset ACL add') .
                    ' :: [' . $role_id . '] ' . $role_name;
                $this->renderScript('admin/form-fileset.phtml');
                return;
            }
            $this->_forward('role-update', 'admin', null, array(
                'role_id'   => $role_id,
                'role_name' => $role_name )); // action, controller
        }
    }

    

    public function filesetUpdateAction()
    {
        if ( $this->_request->isPost() ) {  // ???
            $fileset_id   = $this->_request->getParam('fileset_id');
            if ( empty ($fileset_id) ) {
                throw new Exception(__METHOD__.' : "Empty $fileset_id parameter"');
                return;
            }
            $role_id = $this->_request->getParam('role_id', null);
            if ( empty ($role_id) ) {
                throw new Exception(__METHOD__.' : "Empty $role_id parameter"');
                return;
            }
            // data from form
            $form_fileset = new FormFilesetACL();
            $table = new WbFilesetACL();
            // Проверяем валидность данных формы
            if ( $form_fileset->isValid($this->_getAllParams()) )
            {
                // update data
                $data = array(
                    'name'      => $this->_request->getParam('name'),
                    'order_acl' => $this->_request->getParam('order')
                );
                $where = $table->getAdapter()->quoteInto('id = ?', $fileset_id);
                try {
                    $table->insert($data);
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                }
            } else {
                // create form
                $this->view->rows_fileset = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
                // fill form
                $form_fileset->populate( array(
                    'action_id' => 'update',
                    'id'        => $fileset_id,
                    'name'      => $this->_request->getParam('name'),
                    'order_acl' => $this->_request->getParam('order')
                ));
                $form_fileset->setAction( $this->view->baseUrl . '/admin/fileset-update' );
                $this->view->form_fileset = $form_fileset;
                $this->view->title = 'Webacula :: ' . $this->view->translate->_('Fileset ACL update');
            }
            $this->renderScript('admin/form-fileset.phtml');
            return;
        }
        $this->_forward('role-update', 'admin', null, array(
            'role_id'   => $role_id
        )); // action, controller
    }



    public function roleUpdateAction() 
    {
        // TODO если были изменения, то очистить все кэши
        $role_id = $this->_request->getParam('role_id', null);
        if ( empty ($role_id) ) {
            throw new Exception(__METHOD__.' : "Empty $role_id parameter"');
            return;
        }
        /**********************************
         * Role form
         **********************************/
        // get Role name, inherited id
        $table = new Wbroles();
        $role  = $table->fetchRow($table->getAdapter()->quoteInto('id = ?', $role_id));
        unset ($table);
        // form
        $form_role = new FormRole();
        // fill form
        $form_role->populate( array(
                'action_id'  => 'update',
                'role_id'    => $role_id,
                'role_name'  => $role->name,
                'description_role' => $role->description,
                'order_role' => $role->order_role,
                'inherit_id' => $role->inherit_id
            ));
        $form_role->setAction( $this->view->url() );
        $this->view->form_role      = $form_role;
        /**********************************
         * Webacula ACL form
         **********************************/
        $form_webacula = new FormWebaculaACL();
        // get resources
        $table = new Wbresources();
        $wbresources = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        unset ($table);
        $webacula_resources = null;
        foreach( $wbresources as $v) {
            $webacula_resources[] = $v->dt_id;
        }
        // fill form
        $form_webacula->populate( array(
                'action_id'  => 'update',
                'role_id'    => $role_id,
                'role_name'  => $role->name
            ));
        if ( isset($webacula_resources) )
            $form_webacula->populate( array(
                'webacula_resources' => $webacula_resources,
                'role_id'            => $role_id
            ));
        $form_webacula->setAction( $this->view->url() );
        $this->view->form_webacula  = $form_webacula;
        /**********************************
         * Storage ACL form
         **********************************/
        $table = new WbStorageACL();
        $this->view->rows_storage = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_storage = new FormStorageACL();
        // fill form
        $form_storage->populate( array(
                'action_id' => 'add',
                'role_id'   => $role_id,
                'role_name'  => $role->name
            ));
        $form_storage->setAction( $this->view->baseUrl . '/admin/storage-add' );
        $this->view->form_storage = $form_storage;
        /**********************************
         * Pool ACL form
         **********************************/        
        $table = new WbPoolACL();
        $this->view->rows_pool = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_pool = new FormPoolACL();
        // fill form
        $form_pool->populate( array(
                'action_id' => 'add',
                'role_id'   => $role_id,
                'role_name'  => $role->name
            ));
        $form_pool->setAction( $this->view->baseUrl . '/admin/pool-add' );
        $this->view->form_pool = $form_pool;
        /**********************************
         * Client ACL form
         **********************************/        
        $table = new WbClientACL();
        $this->view->rows_client = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form       
        $form_client = new FormClientACL();
        // fill form
        $form_client->populate( array(
                'action_id' => 'add',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_client->setAction( $this->view->baseUrl . '/admin/client-add' );
        $this->view->form_client = $form_client;
        /**********************************
         * Fileset ACL form
         **********************************/       
        $table = new WbFilesetACL();
        $this->view->rows_fileset = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form        
        $form_fileset = new FormFilesetACL();
        // fill form
        $form_fileset->populate( array(
                'action_id' => 'add',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_fileset->setAction( $this->view->baseUrl . '/admin/fileset-add' );
        $this->view->form_fileset = $form_fileset;
        /**********************************
         * Where ACL form
         **********************************/
        // TODO как и Storage ACL

        /**********************************
         * Job ACL form
         **********************************/
        // TODO как и Storage ACL

        /**********************************
         * Command ACL form
         **********************************/
        // TODO как и Webacula ACL
        
        /**********************************
         * view
         **********************************/
        // Inherited roles name
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $this->view->roles = $table->getParentNames( $role_id );
        unset ($table);
        // title
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role update') .
                ' :: [' . $role_id . '] ' . $role->name;
        $this->view->role_id = $role_id;
    }



}