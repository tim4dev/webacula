<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

require_once 'Zend/Application.php';
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

/**
 * Controller Test case
 *
 * @category Tests
 */
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
	protected $_application;
	const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework errors

	public function setUp() {
		$this->bootstrap = array ($this, 'appBootstrap' );
		parent::setUp();
	}


    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        $this->_logout();
        parent::tearDown();
    }


	/**
	 * Boostrap Application
	 */
	public function appBootstrap() {
		$this->_application = new Zend_Application ( APPLICATION_PATH );
		$this->frontController->addControllerDirectory ( APPLICATION_PATH . '/controllers' );
		$this->_application->bootstrap();
	}


	protected function _rootLogin() {
		// php array to object
        $data = (object)$arr = array(
          'id'        => 1000,
          'login'     => 'root',
          'role_id'   => 1,
          'role_name' => 'root_role'
        );
        // write session
        $auth = Zend_Auth::getInstance();
        $storage = $auth->getStorage();
        $storage->write($data);
        Zend_Session::rememberMe();
        echo ' (login as '.$data->login.') ';
    }



    protected function _user3Login() {
		// php array to object
        $data = (object)$arr = array(
          'id'        => 1002,
          'login'     => 'user3',
          'role_id'   => 3,
          'role_name' => 'user_role'
        );
        // write session
        $auth = Zend_Auth::getInstance();
        $storage = $auth->getStorage();
        $storage->write($data);
        Zend_Session::rememberMe();
        echo ' (login as '.$data->login.') ';
    }



    protected function _user2Login() {
		// php array to object
        $data = (object)$arr = array(
          'id'        => 1001,
          'login'     => 'user2',
          'role_id'   => 2,
          'role_name' => 'operator_role'
        );
        // write session
        $auth = Zend_Auth::getInstance();
        $storage = $auth->getStorage();
        $storage->write($data);
        Zend_Session::rememberMe();
        echo ' (login as '.$data->login.') ';
    }



    protected function _isLogged($body) {
        if ( empty($body) )
            throw new RuntimeException('Login failed!');
    }

    protected function _logout()    {
        $this->dispatch('auth/logout');
    }


    protected function logBody($body, $mode = 'w')  {
        $fout = fopen('report/outputBody.html', $mode);
        fwrite($fout, $body);
        fclose($fout);
    }


}
