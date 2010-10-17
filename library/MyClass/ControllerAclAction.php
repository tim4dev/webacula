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
/**
 * Common code for all Controllers with ACLs
 */

require_once 'Zend/Controller/Action.php';

class MyClass_ControllerAclAction extends Zend_Controller_Action
{

	const DEBUG_LOG = '/tmp/webacula_debug.log';
    protected $_config;
    public $debug_level;

    // ACL
    protected $_acl;        // webacula acl
    protected $_identity;
    protected $_role_name = '';
    protected $_role_id   = null;


    public function init()
    {
        parent::init();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->view->translate = Zend_Registry::get('translate');
        $this->view->language  = Zend_Registry::get('language');

        $this->_config = Zend_Registry::get('config');
        // authentication
        $this->view->identity  = '';
        $this->view->role_name = '';
        if ( $this->isAuth() )  {
            $this->_identity  = Zend_Auth::getInstance()->getIdentity();
            // find current user ACL role
            Zend_Loader::loadClass('Wbroles');
            $table = new Wbroles();
            $row   = $table->find($this->_identity->role_id);
            if ($row->count() == 1) {
                $this->role_name = $row[0]['name'];
                $this->role_id   = $row[0]['id'];
            }
            // for view
            $this->view->role_name = $this->_role_name;
            $this->view->role_id   = $this->_role_id;
            $this->view->identity  = $this->_identity;
        }
        // для переадресаций
        $this->_redirector = $this->_helper->getHelper('Redirector');
        // ACL
        $this->_acl        = new MyClass_Acl();
        // debug
        if ( $this->_config->debug_level > 0 ) {
            Zend_Loader::loadClass('Zend_Log_Writer_Stream');
            Zend_Loader::loadClass('Zend_Log');
            $writer = new Zend_Log_Writer_Stream(self::DEBUG_LOG);
            $this->logger = new Zend_Log($writer);
        }
    }


    public function preDispatch()
    {
        $this->view->access = 0; // access denied
        // этот контроллер недоступен без регистрации
        if ( !$this->isAuth() ) {
            $this->_redirector->gotoSimple('login', 'auth', null, array()); // action, controller
            return;
        }
        if ($this->role_name)
            if( $this->_acl->isAllowed( $this->role_name, 'index') )
                $this->view->access = 1;
        parent::preDispatch();
    }


    /*
     * @return true если юзер залогинился
     */
    /* Использование :
     * // это действие недоступно без регистрации
        if ( !$this->isAuth() ) {
            $this->_redirect('auth/login');
        }
     */
    function isAuth()
    {
        $auth = Zend_Auth::getInstance();
        return $auth->hasIdentity();
    }


}
