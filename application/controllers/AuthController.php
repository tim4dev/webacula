<?php
/**
 * Copyright 2010, 2011, 2014 Yuriy Timofeev tim4dev@gmail.com
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
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

class AuthController extends Zend_Controller_Action
{
    protected $defNamespace;
    protected $identity;
    const MAX_LIFETIME = 1209600; // 14 days



    public function init()
    {
    	parent::init();
        Zend_Loader::loadClass('FormLogin');
        Zend_Loader::loadClass('Zend_Auth_Adapter_DbTable');
        Zend_Loader::loadClass('Wbroles');
        Zend_Loader::loadClass('Wbusers');
        $this->view->baseUrl = $this->_request->getBaseUrl();
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('login');
        // translate
        $this->view->translate = Zend_Registry::get('translate');
        // для переадресаций
        $this->_redirector = $this->_helper->getHelper('Redirector');
        // для подсчета кол-ва неудачных логинов для вывода капчи
        $this->defNamespace = new Zend_Session_Namespace('Default');
        // Get current role_id, role_name
        $auth    = Zend_Auth::getInstance();
        if ($ident   = $auth->getIdentity() )
            $this->identity = $ident;
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
            $this->_forward('index', 'index'); // если уже залогинен: action, controller
            return;
        }                    
        $form = new formLogin();
        if ( $this->_request->isPost() ) {
            /* Проверяем валидность данных формы */
            if ( $form->isValid($this->_getAllParams()) )
            {
                $users = new Wbusers();
                $login = $form->getValue('login');
                if( $users->checkPassword( $login, $form->getValue('pwd') ) )
                {
                    $user = $users->fetchUser($login);
                    $user[0]['pwd'] = ''; // пароль обнуляем
                    $user = (object)$user[0];
                    /* Пишем в сессию (default) необходимые нам данные */
                    $auth = Zend_Auth::getInstance();
                    $storage = $auth->getStorage();
                    // find role name
                    $storage->write( $user );
                    // обнуляем счетчик неудачных логинов
                    if (isset($this->defNamespace->numLoginFails))
                        $this->defNamespace->numLoginFails = 0;
                    // remember me
                    if ($form->getValue('rememberme')) {
                        Zend_Session::rememberMe(self::MAX_LIFETIME);
                        Zend_Session::getSaveHandler()->setLifetime(self::MAX_LIFETIME);
                    }
                    // update user statistics
                    $users->updateLoginStat($user->login);
                    // goto home page
                    $this->_redirect('index/index');
                } else {
                    sleep(7);
                    $this->view->msg = $this->view->translate->_("Username or password is incorrect");
                    // включаем счетчик, если кол-во неудачных логинов большое то включаем капчу
                    $this->defNamespace->numLoginFails++;
                }
           }
        }
        /* Если данные не передавались или неверный логин, то выводим форму для авторизации */
        $this->view->caption = sprintf( $this->view->translate->_("Login with your %sWe%sbacula%s account"),
                        '<font color="#00008B">', '</font><font color="#A80000">', '</font>');
        $this->view->title   = $this->view->translate->_('Login with your Webacula account');
        $this->view->form = $form;

        // workaround for unit tests 'Action Helper by name Layout not found'
        if ( !$this->_helper->hasHelper('layout') )
            $this->render();
    }



    /**
     * "Выход" пользователя
     **/
	public function logoutAction()
	{
        $cache_helper = $this->_helper->getHelper('MyCache');
        $cache_helper->clearAllCacheRole($this->identity->role_id);
        /*
         * Final
         */
    	/* "Очищаем" данные об идентификации пользоваля */
		Zend_Auth::getInstance()->clearIdentity();
		Zend_Session::forgetMe();
		/*	Перебрасываем его на главную */
		$this->_redirect('/');
	}



    protected function emailForgotPassword($user_email, $user_name = '', $pwd)
    {
        Zend_Loader::loadClass('MyClass_SendEmail');
        $config = Zend_Registry::get('config');
        $email = new MyClass_SendEmail();
        $body = sprintf( $this->view->translate->_(
"Hello,

This is an automated message from site %s, please do not reply!

You have or someone impersonating you has requested to change your password
from IP : %s

New Password: %s

Once logged in you can change your password.

If you are not the person who made this request
send email to the site admin : %s

Thanks! \n"),
            $this->_request->getServer('SERVER_NAME'),
            $this->_request->getServer('REMOTE_ADDR'),
            $pwd,
            $config->webacula->email->to_admin);
        // $from_email, $from_name, $to_email, $to_name, $subj, $body
        return $email->mySendEmail(
            $config->webacula->email->from,
            $this->view->translate->_('Webacula password manager'),
            $user_email,
            $user_name,
            $this->view->translate->_('New Webacula password'),
            $body
        );
        
    }


    
    public function forgotPasswordAction()
	{
        Zend_Loader::loadClass('FormForgotPassword');
        $form = new formForgotPassword();
        if( $this->_request->isPost() ) {
            /* Проверяем валидность данных формы */
            if ( $form->isValid($this->_getAllParams()) )
            {
                $db = Zend_Registry::get('db_bacula');
                Zend_Loader::loadClass('Wbusers');
                $table = new Wbusers();
                // ищем email
                $select  = $table->select()->where('login = ?', $this->_getParam('login'))
                                ->where('email = ?', $this->_getParam('email'));
                $row = $table->fetchRow($select);
                /* login + email найдены ? */
                if( $row )
                {
                    // генерируем новый пароль
                    $new_password = md5( uniqid( rand() ) );                   
                    // высылаем пароль
                    $res = $this->emailForgotPassword($row->email, $row->name, $new_password);
                    if ( $res ) {
                        // сохраняем пароль в БД
                        $data = array(
                            'pwd' => $new_password  // password hash
                        );
                        $where = $table->getAdapter()->quoteInto('id = ?', $row->id);
                        $table->update($data, $where);
                        // goto home page
                        $this->view->msg = $this->view->translate->_("New password set");
                        $this->_redirector->gotoSimple('login', 'auth', null, array('from_forgot' => 1)); // action, controller
                    } else {
                        $this->view->msg = $this->view->translate->_("Error while sending email. Email not send");
                    }
                } else {
                    sleep(2);  // TODO increase this value
                    $this->view->msg = $this->view->translate->_("Username or email is incorrect");
                }
           }
        }
        /* Если данные не передавались или неверный логин, то выводим форму для авторизации */
        $this->view->title = $this->view->translate->_('Reset password');
        $this->view->form  = $form;
    }

}