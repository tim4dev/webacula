<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

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
        // write session
        $auth = Zend_Auth::getInstance();
        $storage = $auth->getStorage();
        $storage->write($data);
        Zend_Session::rememberMe();
        echo ' (login as '.$data->login.') ';
    }

    protected function _logout()    {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::forgetMe();
    }

}
