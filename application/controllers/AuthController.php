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

class AuthController extends Zend_Controller_Action
{
    protected $defNamespace;
    const MAX_LOGIN_ATTEMPT = 3;
    protected $_role_name = '';
    protected $_role_id   = null;



    public function init()
    {
    	parent::init();
        Zend_Loader::loadClass('FormLogin');
        Zend_Loader::loadClass('Zend_Auth_Adapter_DbTable');
        Zend_Loader::loadClass('Wbroles');
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->_helper->layout->setLayout('login');
        // translate
        $this->view->translate = Zend_Registry::get('translate');
        // для переадресаций
        $this->_redirector = $this->_helper->getHelper('Redirector');
        // для подсчета кол-ва неудачных логинов для вывода капчи
        $this->defNamespace = new Zend_Session_Namespace('Default');
        // Get current role_id, role_name
        $auth    = Zend_Auth::getInstance();
        if ($ident   = $auth->getIdentity() ) {
            $this->_role_id   = $ident->role_id;
            $this->_role_name = $ident->role_name;
        }
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

    public function loginAction()
    {
        if ( $this->isAuth() ) {
            $this->_redirector->gotoSimple('index', 'index', null, array()); // если уже залогинен: action, controller
            return;
        }
        $form = new formLogin();
        if( $this->_request->isPost() ) {
            /* Проверяем валидность данных формы */
            if ( $form->isValid($this->_getAllParams()) )
            {
                $db          = Zend_Registry::get('db_bacula');
                $authAdapter = new Zend_Auth_Adapter_DbTable($db);
                /**
                 * Настраиваем правила выборки пользователей из БД
                 * имя таблицы, название поля с идентификатором пользователя, название поля пароля
                 */
                $authAdapter->setTableName('webacula_users')
                             ->setIdentityColumn('login')
                             ->setCredentialColumn('pwd')
                             ->setCredentialTreatment('PASSWORD(?)');
                /* Передаем в адаптер данные пользователя */
                $authAdapter->setIdentity($form->getValue('login'));
                $authAdapter->setCredential($form->getValue('pwd'));
                /* Собственно, процесс аутентификации */
                $auth = Zend_Auth::getInstance();
                $resultAuth = $auth->authenticate($authAdapter);
                /* Проверяем валидность результата */
                if( $resultAuth->isValid() )
                {
                    /* Пишем в сессию (default) необходимые нам данные (пароль обнуляем) */
                    $storage = $auth->getStorage();
                    $data = $authAdapter->getResultRowObject(array(
                        'id',
                        'login',
                        'role_id'
                    ));
                    // find role name
                    $table = new Wbroles();
                    $row   = $table->find($data->role_id);
                    if ($row->count() == 1)
                        $data->role_name = $row[0]['name'];
                    $storage->write($data);
                    // обнуляем счетчик неудачных логинов
                    if (isset($this->defNamespace->numLoginFails))
                        $this->defNamespace->numLoginFails = 0;
                    // remember me
                    if ($form->getValue('rememberme'))
                        Zend_Session::rememberMe();
                    // goto home page
                    $this->_redirect('index/index');
                } else {
                    sleep(2);  // TODO increase this value
                    $this->view->msg = $this->view->translate->_("Username or password is incorrect");
                    // включаем счетчик, если кол-во неудачных логинов большое то включаем капчу
                    $this->defNamespace->numLoginFails++;
                }
           }
        }
        /* Если данные не передавались или неверный логин, то выводим форму для авторизации */
        $this->view->form = $form;
    }


    /**
     * "Выход" пользователя
     **/
	public function logoutAction()
	{
        /*
         * Cleaning cache
         */
	    // remove Bacula ACLs
        $bacula_acl = new MyClass_BaculaAcl();
        $bacula_acl->removeCache();
        // remove Webacula ACLs
        $cache = Zend_Registry::get('cache');
        $res = $cache->remove('MyClass_WebaculaAcl');
        /*
         * Final
         */
    	/* "Очищаем" данные об идентификации пользоваля */
		Zend_Auth::getInstance()->clearIdentity();
		Zend_Session::forgetMe();
		/*	Перебрасываем его на главную */
		$this->_redirect('/');
	}


}

