<?php

require_once 'PHPUnit/Framework/TestCase.php';

/*
 * see also ControllerTestCase.php
 */
class ModelTestCase extends PHPUnit_Framework_TestCase {

    protected function _rootLogin() {
		// php array to object
        $data = (object)$arr = array(
          'id'        => 1000,
          'login'     => 'root',
          'role_id'   => 1,
          'role_name' => 'root_role'
        );
        // wtite session
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
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::forgetMe();
    }

}
