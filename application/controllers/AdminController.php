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
    
    private $cache_helper;


    /*
     * jQuery UI Tabs, Options, selected:
     * Zero-based index of the tab to be selected on initialization.
     * To set all tabs to unselected pass -1 as value.
     * $('.selector').tabs({ selected: 3 });
     */
    private $_jQueryUItabSelected = array(
        'role'     => 0,
        'webacula' => 1,
        'client'   => 2,
        'command'  => 3,
        'fileset'  => 4,
        'job'      => 5,
        'pool'     => 6,
        'storage'  => 7,
        'where'    => 8
    );


    
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
        Zend_Loader::loadClass('WbJobACL');
        Zend_Loader::loadClass('WbWhereACL');
        Zend_Loader::loadClass('WbCommandACL');
        // forms
        Zend_Loader::loadClass('FormRole');
        Zend_Loader::loadClass('FormUser');
        Zend_Loader::loadClass('FormWebaculaACL');
        Zend_Loader::loadClass('FormBaculaACL');
        Zend_Loader::loadClass('FormBaculaCommandACL');
        Zend_Loader::loadClass('FormBaculaFill');
        // helpers
        $this->cache_helper = $this->_helper->getHelper('MyCache');
    }



    public function indexAction() {
        $this->_redirect('admin/role-index');
    }



    /***************************************************************************
     * Role actions
     ***************************************************************************/
    public function roleIndexAction() {
        $roles = new Wbroles();
        $this->view->result = $roles->fetchAllRoles();
        $this->view->title  = 'Webacula :: ' . $this->view->translate->_('Roles');
    }


    public function roleAddAction()
    {
        $form = new FormRole();
        $table = new Wbroles();
        $role_name = $this->_request->getParam('role_name');
        if ( $this->_request->isPost() && isset($role_name) ) {
            // validate form
            if ( $form->isValid($this->_getAllParams()) )
            {
                // insert data to table
                $data = array(
                    'name'        => $role_name,
                    'order_role'  => $this->_request->getParam('order'),
                    'description' => $this->_request->getParam('description'),
                    'inherit_id'  => $this->_request->getParam('inherit_id')
                );
                try {
                    $role_id = $table->insert($data);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id'  => $role_id,
                    'role_name'=> $role_name
                )); // action, controller
                return;
            }
        }
        $form->setAction( $this->view->baseUrl . '/admin/role-add' );
        $this->view->form = $form;
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role add');
        $this->renderScript('admin/form-role.phtml');
    }


    public function roleUpdateAction()
    {
        $role_id    = $this->_request->getParam('role_id');
        $role_name  = $this->_request->getParam('role_name');
        if ( empty($role_id) || empty ($role_name) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $form = new FormRole(null, $role_id);
        $table = new Wbroles();
        if ( $this->_request->isPost() ) {
            // Проверяем валидность данных формы
            if ( $form->isValid($this->_getAllParams()) )
            {
                // update data
                $data = array(
                    'name'        => $role_name,
                    'order_role'  => $this->_request->getParam('order'),
                    'description' => $this->_request->getParam('description'),
                    'inherit_id'  => $this->_request->getParam('inherit_id')
                );
                $where = $table->getAdapter()->quoteInto('id = ?', $role_id);
                try {
                    $table->update($data, $where);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id'  => $role_id,
                    'role_name'=> $role_name
                )); // action, controller
                return;
            }
        }
        // create form
        $row = $table->find($role_id)->current();
        // fill form
        $form->populate( array(
            'acl'        => 'role',
            'role_id'    => $role_id,
            'name'       => $row->name,
            'order'      => $row->order_role,
            'description'=> $row->description,
            'inherit_id' => $row->inherit_id
        ));
        $form->submit->setLabel($this->view->translate->_('Update'));
        $form->setAction( $this->view->baseUrl . '/admin/role-update' );
        $this->view->form = $form;
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role update');
        $this->renderScript('admin/form-role.phtml');
    }


    public function roleDeleteAction()
    {
        $role_id    = $this->_request->getParam('role_id');
        if ( empty($role_id) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $table = new Wbroles();
        $where = $table->getAdapter()->quoteInto('id = ?', $role_id);
        try {
            $table->delete($where, $role_id);
        } catch (Zend_Exception $e) {
            $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
        }
        // clear all cache
        $this->cache_helper->clearAllCache();
        // render
        $this->_forward('role-index', 'admin'); // action, controller
    }



    /***************************************************************************
     * Webacula ACLs actions
     ***************************************************************************/
    public function webaculaUpdateAction()
    {
        $role_id    = $this->_request->getParam('role_id');
        $role_name  = $this->_request->getParam('role_name');
        if ( empty($role_id) || empty ($role_name) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $form  = new FormWebaculaACL();
        $table = new Wbresources();
        if ( $this->_request->isPost() ) {
            // Проверяем валидность данных формы
            if ( $form->isValid($this->_getAllParams()) )
            {
                // update data               
                try {
                    $table->updateResources($this->_request->getParam('webacula_resources'), $role_id);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id'       => $role_id,
                    'role_name'     => $role_name,
                    'tabs_selected' => 'webacula'
                )); // action, controller
                return;
            }
        }
        // create form
        // get resources
        $wbresources = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        $webacula_resources = null;
        foreach( $wbresources as $v) {
            $webacula_resources[] = $v->dt_id;
        }
        // fill form
        $form->populate( array(
            'acl'       => 'webacula',
            'role_id'   => $role_id,
            'role_name' => $role_name
        ));
        if ( isset($webacula_resources) )
            $form->populate( array(
                'webacula_resources' => $webacula_resources
            ));
        $form->setAction( $this->view->baseUrl . '/admin/role-main-form' );
        $this->view->form = $form;
        $this->view->tabs_selected = $this->_jQueryUItabSelected['webacula']; // jQuery UI Tabs
        $this->renderScript('admin/role-main-form.phtml');
    }



    /***************************************************************************
     * Commands Bacula actions
     ***************************************************************************/
    public function commandsUpdateAction()
    {
        $role_id    = $this->_request->getParam('role_id');
        $role_name  = $this->_request->getParam('role_name');
        if ( empty($role_id) || empty ($role_name) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $form  = new FormBaculaCommandACL();
        $table = new WbCommandACL();
        if ( $this->_request->isPost() ) {
            // Проверяем валидность данных формы
            if ( $form->isValid($this->_getAllParams()) )
            {
                // update data
                try {
                    $table->updateCommands($this->_request->getParam('bacula_commands'), $role_id);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id'       => $role_id,
                    'role_name'     => $role_name,
                    'tabs_selected' => 'command'
                )); // action, controller
                return;
            }
        }
        // create form
        // get resources
        $commands = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        $bacula_commands = null;
        foreach( $commands as $v) {
            $bacula_commands[] = $v->dt_id;
        }
        // fill form
        $form->populate( array(
            'acl'       => 'command',
            'role_id'   => $role_id,
            'role_name' => $role_name
        ));
        if ( isset($bacula_commands) )
            $form->populate( array(
                'bacula_commands' => $bacula_commands
            ));
        $form->setAction( $this->view->baseUrl . '/admin/role-main-form' );
        $this->view->form = $form;
        $this->view->tabs_selected = $this->_jQueryUItabSelected['command']; // jQuery UI Tabs
        $this->renderScript('admin/role-main-form.phtml');
    }


    
    /*
     * Add dispatcher for :
     * Client, FileSet, Job, Pool, Storage, Where ACLs
     */
    public function aclAddDispatcher($acl)
    {
        switch ($acl) {
            case 'client':
                $table = new WbClientACL();
                break;
            case 'fileset':
                $table = new WbFilesetACL();
                break;
            case 'job':
                $table = new WbJobACL();
                break;
            case 'pool':
                $table = new WbPoolACL();
                break;
            case 'storage':
                $table = new WbStorageACL();
                break;
            case 'where':
                $table = new WbWhereACL();
                break;
            default:
                throw new Exception(__METHOD__.' : Invalid $acl parameter');
                break;
        }
        if ( $this->_request->isPost() ) {
            $role_id   = $this->_request->getParam('role_id');
            $role_name = $this->_request->getParam('role_name');
            if ( empty ($role_id) )
                throw new Exception(__METHOD__.' : Empty $role_id parameter');
            $form = new FormBaculaACL();
            // validate form
            if ( $form->isValid($this->_getAllParams()) )
            {
                // insert data to table
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
                // clear all cache
                $this->cache_helper->clearAllCache();
            } else {
                $this->view->errors = $form->getMessages();
                $form->setAction( $this->view->baseUrl . '/admin/'.$acl.'-add' );
                $this->view->form = $form;
                $this->view->title = 'Webacula :: ' . $this->view->translate->_(ucfirst($acl).' ACL add') .
                    ' :: [' . $role_id . '] ' . $role_name;
                $this->renderScript('admin/form-bacula-acl.phtml');
                return;
            }
            $this->_forward('role-main-form', 'admin', null, array(
                'role_id'       => $role_id,
                'role_name'     => $role_name,
                'tabs_selected' => $acl) ); // action, controller
        }
    }
    

    /*
     * Update dispatcher for :
     * Client, FileSet, Job, Pool, Storage, Where ACLs
     */
    public function aclUpdateDispatcher($acl)
    {
        switch ($acl) {
            case 'client':
                $table = new WbClientACL();
                break;
            case 'fileset':
                $table = new WbFilesetACL();
                break;
            case 'job':
                $table = new WbJobACL();
                break;
            case 'pool':
                $table = new WbPoolACL();
                break;
            case 'storage':
                $table = new WbStorageACL();
                break;
            case 'where':
                $table = new WbWhereACL();
                break;
            default:
                throw new Exception(__METHOD__.' : Invalid $acl parameter');
                break;
        }
        $this->_helper->viewRenderer->setNoRender(); // disable autorendering
        $id = $this->_request->getParam('id');
        $role_id    = $this->_request->getParam('role_id', null);
        if ( empty($id) || empty($role_id) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $form = new FormBaculaACL();
        if ( $this->_request->isPost() ) {
            // Проверяем валидность данных формы
            if ( $form->isValid($this->_getAllParams()) )
            {
                // update data
                $data = array(
                    'name'      => $this->_request->getParam('name'),
                    'order_acl' => $this->_request->getParam('order')
                );
                $where = $table->getAdapter()->quoteInto('id = ?', $id);
                try {
                    $table->update($data, $where);
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id' => $role_id,
                    'tabs_selected' => $acl
                )); // action, controller
                return;
            }
        }
        // create form
        $row = $table->find($id)->current();
        // fill form
        $form->populate( array(
            'acl'    => $acl,
            'id'     => $id,
            'name'   => $row->name,
            'order'  => $row->order_acl,
            'role_id'=> $role_id
        ));
        $form->submit->setLabel($this->view->translate->_('Update'));
        $form->setAction( $this->view->baseUrl . '/admin/'.$acl.'-update' );
        $this->view->form = $form;
        $this->view->title = 'Webacula :: ' . $this->view->translate->_(ucfirst($acl).' ACL update');
        $this->renderScript('admin/form-bacula-acl.phtml');
    }



    /*
     * Delete dispatcher for :
     * Client, FileSet, Job, Pool, Storage, Where ACLs
     */
    public function aclDeleteDispatcher($acl)
    {       
        switch ($acl) {
            case 'client':
                $table = new WbClientACL();
                break;
            case 'fileset':
                $table = new WbFilesetACL();
                break;
            case 'job':
                $table = new WbJobACL();
                break;
            case 'pool':
                $table = new WbPoolACL();
                break;
            case 'storage':
                $table = new WbStorageACL();
                break;
            case 'where':
                $table = new WbWhereACL();
                break;
            default:
                throw new Exception(__METHOD__.' : Invalid $acl parameter');
                break;
        }
        $id = $this->_request->getParam('id');
        $role_id    = $this->_request->getParam('role_id', null);
        if ( empty($id) || empty($role_id) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $where = $table->getAdapter()->quoteInto('id = ?', $id);
        $table->delete($where);
        // clear all cache
        $this->cache_helper->clearAllCache();
        // render
        $this->_forward('role-main-form', 'admin', null, array(
                'role_id'       => $role_id,
                'tabs_selected' => $acl) ); // action, controller
    }



    /*
     * Fill data from Bacula database dispatcher for :
     * Client, FileSet, Job, Pool, Storage
     */
    public function fillAction()
    {
        $acl = trim( $this->_request->getParam('acl') );
        switch ($acl) {
            case 'client':
                $table_webacula = 'WbClientACL';
                break;
            case 'fileset':
                $table_webacula = 'WbFilesetACL';
                break;
            case 'job':
                $table_webacula = 'WbJobACL';
                break;
            case 'pool':
                $table_webacula = 'WbPoolACL';
                break;
            case 'storage':
                $table_webacula = 'WbStorageACL';
                break;
            default:
                throw new Exception(__METHOD__.' : Invalid input parameter.');
                break;
        }
        if ( $this->_request->isPost() ) {
            $role_id     = $this->_request->getParam('role_id');
            $role_name   = $this->_request->getParam('role_name');
            $bacula_fill = $this->_request->getParam('bacula_fill');
            if ( empty ($role_id) )
                throw new Exception(__METHOD__.' : Empty $role_id parameter');
            if ( !empty($bacula_fill) ) {
                $table = new Wbroles();
                // insert data to table
                try {
                    // inserts data
                    $table->insertBaculaFill($table_webacula, $role_id, $bacula_fill);
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                $this->_forward('role-main-form', 'admin', null, array(
                    'role_id'       => $role_id,
                    'role_name'     => $role_name,
                    'tabs_selected' => $acl) ); // action, controller
            }
        }
    }




    /***************************************************************************
     * Fileset
     ***************************************************************************/
    public function filesetAddAction()
    {
        $this->aclAddDispatcher('fileset');
    }
    
    public function filesetUpdateAction()
    {
        $this->aclUpdateDispatcher('fileset');
    }
    
    public function filesetDeleteAction()
    {
        $this->aclDeleteDispatcher('fileset');
    }



    /***************************************************************************
     * Client
     ***************************************************************************/
    public function clientAddAction()
    {
        $this->aclAddDispatcher('client');
    }

    public function clientUpdateAction()
    {
        $this->aclUpdateDispatcher('client');
    }

    public function clientDeleteAction()
    {
        $this->aclDeleteDispatcher('client');
    }

    

    /***************************************************************************
     * Job
     ***************************************************************************/
    public function jobAddAction()
    {
        $this->aclAddDispatcher('job');
    }

    public function jobUpdateAction()
    {
        $this->aclUpdateDispatcher('job');
    }

    public function jobDeleteAction()
    {
        $this->aclDeleteDispatcher('job');
    }


    /***************************************************************************
     * Pool
     ***************************************************************************/
    public function poolAddAction()
    {
        $this->aclAddDispatcher('pool');
    }

    public function poolUpdateAction()
    {
        $this->aclUpdateDispatcher('pool');
    }

    public function poolDeleteAction()
    {
        $this->aclDeleteDispatcher('pool');
    }


    /***************************************************************************
     * Storage
     ***************************************************************************/
    public function storageAddAction()
    {
        $this->aclAddDispatcher('storage');
    }

    public function storageUpdateAction()
    {
        $this->aclUpdateDispatcher('storage');
    }

    public function storageDeleteAction()
    {
        $this->aclDeleteDispatcher('storage');
    }


    /***************************************************************************
     * Where
     ***************************************************************************/
    public function whereAddAction()
    {
        $this->aclAddDispatcher('where');
    }

    public function whereUpdateAction()
    {
        $this->aclUpdateDispatcher('where');
    }

    public function whereDeleteAction()
    {
        $this->aclDeleteDispatcher('where');
    }

    


    /***************************************************************************
     * Role update
     ***************************************************************************/
    public function roleMainFormAction()
    {
        $role_id = $this->_request->getParam('role_id');
        if ( empty ($role_id) )
            throw new Exception(__METHOD__.' : Empty $role_id parameter');
        /**********************************
         * Role form
         **********************************/
        // get Role name, inherited id
        $table = new Wbroles();
        $role  = $table->fetchRow($table->getAdapter()->quoteInto('id = ?', $role_id));
        unset ($table);
        // form
        $form_role = new FormRole(null, $role_id);
        // fill form
        $form_role->populate( array(
                'acl'        => 'role',
                'role_id'    => $role_id,
                'role_name'  => $role->name,
                'description'=> $role->description,
                'order'      => $role->order_role,
                'inherit_id' => $role->inherit_id
            ));
        $form_role->setAction( $this->view->baseUrl . '/admin/role-update' );
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
                'acl'        => 'webacula',
                'role_id'    => $role_id,
                'role_name'  => $role->name
            ));
        if ( isset($webacula_resources) )
            $form_webacula->populate( array(
                'webacula_resources' => $webacula_resources,
                'role_id'            => $role_id
            ));
        $form_webacula->setAction( $this->view->baseUrl . '/admin/webacula-update' );
        $this->view->form_webacula  = $form_webacula;
        /**********************************
         * Command ACL form
         **********************************/
        $form_commands = new FormBaculaCommandACL();
        // get resources
        $table = new WbCommandACL();
        $wbcommands = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'id');
        unset ($table);
        $bacula_commands = null;
        foreach( $wbcommands as $v) {
            $bacula_commands[] = $v->dt_id;
        }
        // fill form
        $form_commands->populate( array(
                'acl'        => 'command',
                'role_id'    => $role_id,
                'role_name'  => $role->name
            ));
        if ( isset($bacula_commands) )
            $form_commands->populate( array(
                'bacula_commands' => $bacula_commands,
                'role_id'         => $role_id
            ));
        $form_commands->setAction( $this->view->baseUrl . '/admin/commands-update' );
        $this->view->form_commands  = $form_commands;
        /**********************************
         * Client ACL form
         **********************************/
        $table = new WbClientACL();
        $this->view->rows_client = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_client = new FormBaculaACL();
        // fill form
        $form_client->populate( array(
                'acl'       => 'client',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_client->setAction( $this->view->baseUrl . '/admin/client-add' );
        $this->view->form_client = $form_client;
        // Fill data from Bacula database
        $this->view->form_bacula_fill_client = new FormBaculaFill('Client', 'webacula_client_acl', $role_id, $role->name);
        $this->view->form_bacula_fill_client->setAction( $this->view->baseUrl . '/admin/fill/acl/client' );
        /**********************************
         * Fileset ACL form
         **********************************/
        $table = new WbFilesetACL();
        $this->view->rows_fileset = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_fileset = new FormBaculaACL();
        // fill form
        $form_fileset->populate( array(
                'acl'       => 'fileset',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_fileset->setAction( $this->view->baseUrl . '/admin/fileset-add' );
        $this->view->form_fileset = $form_fileset;
        // Fill data from Bacula database
        $this->view->form_bacula_fill_fileset = new FormBaculaFill('FileSet', 'webacula_fileset_acl', $role_id, $role->name);
        $this->view->form_bacula_fill_fileset->setAction( $this->view->baseUrl . '/admin/fill/acl/fileset' );
        /**********************************
         * Job ACL form
         **********************************/
        $table = new WbJobACL();
        $this->view->rows_job = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_job = new FormBaculaACL();
        // fill form
        $form_job->populate( array(
                'acl'       => 'job',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_job->setAction( $this->view->baseUrl . '/admin/job-add' );
        $this->view->form_job = $form_job;
        // Fill data from Bacula database
        $this->view->form_bacula_fill_job = new FormBaculaFill('Job', 'webacula_job_acl', $role_id, $role->name);
        $this->view->form_bacula_fill_job->setAction( $this->view->baseUrl . '/admin/fill/acl/job' );
        /**********************************
         * Pool ACL form
         **********************************/
        $table = new WbPoolACL();
        $this->view->rows_pool = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_pool = new FormBaculaACL();
        // fill form
        $form_pool->populate( array(
                'acl'       => 'pool',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_pool->setAction( $this->view->baseUrl . '/admin/pool-add' );
        $this->view->form_pool = $form_pool;
        // Fill data from Bacula database
        $this->view->form_bacula_fill_pool = new FormBaculaFill('Pool', 'webacula_pool_acl', $role_id, $role->name);
        $this->view->form_bacula_fill_pool->setAction( $this->view->baseUrl . '/admin/fill/acl/pool' );
        /**********************************
         * Storage ACL form
         **********************************/
        $table = new WbStorageACL();
        $this->view->rows_storage = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_storage = new FormBaculaACL();
        // fill form
        $form_storage->populate( array(
                'acl'       => 'storage',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_storage->setAction( $this->view->baseUrl . '/admin/storage-add' );
        $this->view->form_storage = $form_storage;
        // Fill data from Bacula database
        $this->view->form_bacula_fill_storage = new FormBaculaFill('Storage', 'webacula_storage_acl', $role_id, $role->name);
        $this->view->form_bacula_fill_storage->setAction( $this->view->baseUrl . '/admin/fill/acl/storage' );
        /**********************************
         * Where ACL form
         **********************************/
        $table = new WbWhereACL();
        $this->view->rows_where = $table->fetchAll($table->getAdapter()->quoteInto('role_id = ?', $role_id), 'order_acl');
        unset ($table);
        // form
        $form_where = new FormBaculaACL();
        // fill form
        $form_where->populate( array(
                'acl'       => 'where',
                'role_id'   => $role_id,
                'role_name' => $role->name
            ));
        $form_where->setAction( $this->view->baseUrl . '/admin/where-add' );
        $this->view->form_where = $form_where;
        
        /**********************************
         * view
         **********************************/
        // title
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role') .' :: '. $role->name;
        $this->view->role_id = $role_id;
        // jQuery UI Tabs
        $tabs_selected = $this->_request->getParam('tabs_selected', 'role');
        $this->view->tabs_selected = $this->_jQueryUItabSelected[$tabs_selected];
    }


    public function roleMoreInfoAction()
    {
        $role_id = $this->_request->getParam('role_id');
        if ( empty ($role_id) )
            throw new Exception(__METHOD__.' : Empty $role_id parameter');
        $this->view->role_id = $role_id;
        // get Role name
        $table = new Wbroles();
        $role  = $table->fetchRow($table->getAdapter()->quoteInto('id = ?', $role_id));
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('Role') .' :: '. $role->name;
        // inherited roles
        $this->view->inherited_roles = $table->getParentNames( $role_id );
        // who use
        $this->view->roles = $table->listWhoRolesUseRole($role_id);
        $this->view->users = $table->listWhoUsersUseRole($role_id);
        
    }



    /***************************************************************************
     * Users
     **************************************************************************/
    public function userIndexAction() {
        $order  = addslashes( $this->_request->getParam('order') );
        $users = new Wbusers();
        $this->view->result = $users->fetchAllUsers($order);
        $this->view->title  = 'Webacula :: ' . $this->view->translate->_('Users');
    }



    public function userUpdateAction()
    {
        $user_id = $this->_request->getParam('user_id');
        if ( empty($user_id) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        $form = new FormUser(null, 'update');
        $table = new Wbusers();
        if ( $this->_request->isPost() ) {
            // Проверяем валидность данных формы
            if ( $form->isValid($this->_getAllParams()) )
            {
                // update data
                $data = array(
                    'login'   => $this->_request->getParam('login'),
                    'name'    => $this->_request->getParam('name'),
                    'email'   => $this->_request->getParam('email'),
                    'active'  => intval( $this->_request->getParam('active') ),
                    'role_id' => $this->_request->getParam('role_id')
                );
                // password
                $pwd = trim( $this->_request->getParam('pwd') );
                if (isset($pwd))
                    if ( Zend_Registry::get('DB_ADAPTER') == 'PDO_SQLITE')
                        // Sqlite do not have MD5 function
                        $data['pwd'] = $pwd;
                    else
                        $data['pwd'] = md5($pwd);
                $where = $table->getAdapter()->quoteInto('id = ?', $user_id);
                try {
                    $table->update($data, $where);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('user-index', 'admin'); // action, controller
                return;
            }
        }
        // create form
        $row = $table->find($user_id)->current();
        // fill form
        $form->populate( array(
            'user_id' => $user_id,
            'login'   => $row->login,
            'name'    => $row->name,
            'email'   => $row->email,
            'active'  => $row->active,
            'role_id' => $row->role_id
        ));
        $form->submit->setLabel($this->view->translate->_('Update'));
        $form->setAction( $this->view->baseUrl . '/admin/user-update' );
        $this->view->form = $form;
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('User update');
        $this->renderScript('admin/form-user.phtml');
    }


    public function userAddAction()
    {
        $form = new FormUser(null, 'add');
        $table = new Wbusers();
        if ( $this->_request->isPost() && ( $this->_request->getParam('action_id') == 'add' ) ) {
            // validate form
            if ( $form->isValid($this->_getAllParams()) )
            {
                // insert data to table
                $data = array(
                    'login'   => $this->_request->getParam('login'),
                    'name'    => $this->_request->getParam('name'),
                    'pwd'     => md5($this->_request->getParam('pwd')),
                    'email'   => $this->_request->getParam('email'),
                    'active'  => intval( $this->_request->getParam('active') ),
                    'role_id' => $this->_request->getParam('role_id')
                );
                try {
                    $user_id = $table->insert($data);
                } catch (Zend_Exception $e) {
                    $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
                }
                // clear all cache
                $this->cache_helper->clearAllCache();
                // render
                $this->_forward('user-index', 'admin'); // action, controller
                return;
            }
        }
        $form->populate( array('action_id' => 'add') );
        $form->submit->setLabel($this->view->translate->_('Add'));
        $form->setAction( $this->view->baseUrl . '/admin/user-add' );
        $this->view->form = $form;
        $this->view->title = 'Webacula :: ' . $this->view->translate->_('User add');
        $this->renderScript('admin/form-user.phtml');
    }


    public function userDeleteAction()
    {
        $user_id = $this->_request->getParam('user_id');
        if ( empty($user_id) )
            throw new Exception(__METHOD__.' : Empty input parameters');
        // clear session data
        Zend_Loader::loadClass('Wbphpsession');
        $table_session = new Wbphpsession();
        $table_session->deleteSession($user_id);
        // delete user account
        $table = new Wbusers();
        $where = $table->getAdapter()->quoteInto('id = ?', $user_id);
        try {
            $table->delete($where);
        } catch (Zend_Exception $e) {
            $this->view->exception = $this->view->translate->_('Exception') . ' : ' . $e->getMessage();
        }
        // clear all cache
        $this->cache_helper->clearAllCache();
        // render
        $this->_forward('user-index', 'admin'); // action, controller
    }



}