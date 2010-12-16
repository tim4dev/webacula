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

    // ACL
    protected $webacula_acl;   // webacula acl
    protected $bacula_acl;     // bacula acl
    protected $identity;


    public function init()
    {
        parent::init();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->view->translate = Zend_Registry::get('translate');
        $this->view->language  = Zend_Registry::get('language');

        $this->view->config = Zend_Registry::get('config');
        // authentication
        if ( $this->isAuth() )  {
            $this->identity  = Zend_Auth::getInstance()->getIdentity();
            // for view
            $this->view->identity  = $this->identity;
            /*
             *  ACLs and cache
             */
            $cache = Zend_Registry::get('cache');
            // проверка, есть ли уже запись в кэше:
            if( !$this->webacula_acl = $cache->load('MyClass_WebaculaAcl') ) {
                // промах кэша
                $this->webacula_acl  = new MyClass_WebaculaAcl();
                $cache->save($this->webacula_acl, 'MyClass_WebaculaAcl');
            }
            $this->view->webacula_acl  = $this->webacula_acl;
        }
        // для переадресаций
        $this->_redirector = $this->_helper->getHelper('Redirector');
        // acl
        $this->bacula_acl = new MyClass_BaculaAcl();
    }


    public function preDispatch()
    {
        // этот контроллер недоступен без регистрации
        if ( !$this->isAuth() ) {
            $this->_redirect('/auth/login');
            return;
        }
        if ( !$this->webacula_acl->hasRole('root_role') ) {
            throw new Exception( $this->view->translate->_('Webacula error. Role mechanism is broken. Check Webacula tables.') );
            return;
        }
        $controller = $this->getRequest()->getControllerName();
        if ($this->identity->role_name)
            if( !$this->webacula_acl->isAllowed( $this->identity->role_name, $controller) ) {
                $msg = sprintf( $this->view->translate->_('You try to use Webacula menu "%s".'), $controller );
                $this->_forward('webacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
                return;
            }
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
