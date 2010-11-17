<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
require_once 'Zend/Layout.php';
require_once 'Zend/Layout/Controller/Action/Helper/Layout.php';
require_once 'Zend/Controller/Action/HelperBroker.php';

class WebaculaAclControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework



    /**
     * Test login form
     * @group login-form
     */
    public function testLoginForm()
    {
        print "\n".__METHOD__.' ';
        $this->getRequest()
             ->setParams(array(
                 'login' => 'user3',
                 'pwd'   => '1',
                 'rememberme' => '1') )
             ->setMethod('POST');
        $this->dispatch('auth/login');
echo $this->response->outputBody(); exit; // for debug !!!
        $this->assertController('auth');
        $this->assertAction('login');
//        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
//        $this->assertResponseCode(200);
        // zend form validate and error decorator output
//        $this->assertQueryContentContains('td', "'$jobname' was not found in the haystack");
    }



}