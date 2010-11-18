<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
class WebaculaAclControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    // пункты меню, недоступные для user3
    protected $arr_u3_webacula_denied = array(
        'director/listjobtotals',
        'bconsole/wterminal',
        'director/statusdir',
        'job/terminated',
        'job/running',
        'job/next',
        'job/problem',
        'job/run-job',
        'job/timeline',
        'job/find-form',
        'restorejob/main-form',
        'pool/all',
        'volume/problem',
        'wblogbook/index',
        'wblogbook/add',
        'wbjobdesc/index',
        'feed/feed',
        'webacula/help'
    );
    // 'command' закрытые для user2 посредством Bacula ACLs
    protected $arr_u2_bacula_denied = array(
        'director/listjobtotals',
        'job/run-job',
        'job/run-job/jobname/job.name.test.1',
        'storage/act-mount/act/umount/name/storage.file.2',
        'storage/act-mount/act/mount/name/storage.file.2'
    );



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
        $this->assertController('auth');
        $this->assertAction('login');
        $this->assertRedirectTo('/index/index');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
    }



    /**
     * Test login form
     * @group login-form
     * @group login-wrong
     */
    public function testWrongLogin()
    {
        print "\n".__METHOD__.' ';
        $this->getRequest()
             ->setParams(array(
                 'login' => 'hacker',
                 'pwd'   => '123' ) )
             ->setMethod('POST');
        $this->dispatch('auth/login');
        $this->assertController('auth');
        $this->assertAction('login');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // zend form validate and error decorator output
        $this->assertQueryContentContains('div', 'Username or password is incorrect');
        // get captcha
        echo ".";
        $this->dispatch('auth/login');
        echo ".";
        $this->dispatch('auth/login');
        echo ".";
        $this->dispatch('auth/login');
        echo ".";
        $this->assertQueryContentContains('td', 'Type the characters');
    }



    /**
     * @group webacula-acl
     * @group webacula-acl-u3
     */
    public function testWebaculaAcl3()
    {
        print "\n".__METHOD__.' ';
        $this->_user3Login();  // logon as 'user3'
        /*
         * доступно Menu Client
         */
        print "a";
        $this->dispatch('client/all');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertNotQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        $this->assertQueryContentContains('h1', 'Clients');
        /*
         * доступно Menu Storage
         */
        print "a";
        $this->dispatch('storage/storage');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertNotQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        $this->assertQueryContentContains('h1', 'Storages');
        /*
         * остальные меню недоступны
         */
        foreach ($this->arr_u3_webacula_denied as $menu) {
            print "d";
            $this->dispatch($menu);
            $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
            $this->assertQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        }
    }


    /**
     * @group bacula-acl
     * @group bacula-acl-u2
     */
    public function testBaculaAcl2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        /*
         * закрыто Bacula ACLs
         */
        foreach ($this->arr_u2_bacula_denied as $menu) {
            print "d";
            $this->dispatch($menu);
            $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
            $this->assertQueryContentRegex('div', '/Bacula ACLs : Access denied/');
        }
    }

    
}