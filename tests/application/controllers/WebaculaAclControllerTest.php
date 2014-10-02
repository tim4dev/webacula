<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
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
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = 'localhost';                      
        $this->getRequest()
             ->setParams(array(
                 'login' => 'user3',
                 'pwd'   => '1',
                 'rememberme' => '1') )
             ->setMethod('POST');
        $this->dispatch('auth/login');
        $this->logBody( $this->response->outputBody() ); // debug log
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
                 'pwd'   => '1234567' ) )
             ->setMethod('POST');
        $this->dispatch('auth/login');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('auth');
        $this->assertAction('login');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // zend form validate and error decorator output
        $this->assertQueryContentContains('div', 'Username or password is incorrect');
        // get captcha
        echo ".";
        $this->dispatch('auth/login');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        echo ".";
        $this->dispatch('auth/login');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        echo ".";
        $this->dispatch('auth/login');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        echo ".";
        $this->assertQueryContentContains('td', 'Type the characters');
    }



    /**
     * @group webacula-acl
     * @group webacula-acl-u3
     */
    public function testWebaculaAclUser3()
    {
        print "\n".__METHOD__.' ';
        $this->_user3Login();  // logon as 'user3'
        /*
         * доступно Menu Client
         */
        print "a";
        $this->dispatch('client/all');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertNotQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        $this->assertQueryContentContains('h1', 'Clients');
        /*
         * доступно Menu Storage
         */
        print "a";
        $this->dispatch('storage/storage');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertNotQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        $this->assertQueryContentContains('h1', 'Storages');
        /*
         * остальные меню недоступны
         */
        foreach ($this->arr_u3_webacula_denied as $menu) {
            print "d";
            $this->dispatch($menu);
            $this->logBody( $this->response->outputBody(), 'a' ); // debug log
            $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
            $this->assertQueryContentRegex('div', '/We.*bacula.* : Access denied/');
        }
    }


    /**
     * @group bacula-acl
     * @group bacula-acl-command-u2
     */
    public function testBaculaAclCommandUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        /*
         * закрыто Bacula ACLs
         */
        $this->logBody( '' ); // debug log
        foreach ($this->arr_u2_bacula_denied as $menu) {
            print "d";
            $this->dispatch($menu);
            $this->logBody( $this->response->outputBody(), 'a' ); // debug log
            $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
            $this->assertQueryContentRegex('div', '/Bacula ACLs : Access denied/');
        }
    }



    /**
     * @group bacula-acl
     * @group bacula-acl-job-u2
     */
    public function testBaculaAclJobUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role

        $actions = array(
            'job/terminated',
            'job/next'
        );
        $this->logBody( '' ); // debug log
        foreach ($actions as $act) {
            echo ".";
            $this->dispatch($act);
            $this->logBody( $this->response->outputBody(), 'a' ); // debug log
            $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
            // allowed
            $this->assertQueryContentContains('td', 'job.name.test.1');
            $this->assertQueryContentContains('td', 'job name test 2');
            // not allowed
            $this->assertNotQueryContentContains('td', 'job-name-test-3');
            $this->assertNotQueryContentContains('td', 'job.name.test.4');
            $this->assertNotQueryContentContains('td', 'job.name.test.autochanger.1');
            $this->assertNotQueryContentContains('td', 'restore.files');
        }
        // detail JobId
        $this->dispatch('job/detail/jobid/4');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentContains('div', 'No Jobs found');
    }


    /**
     * @group bacula-acl
     * @group bacula-acl-storage-u2
     */
    public function testBaculaAclStorageUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        $this->dispatch('storage/storage');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // allowed
        $this->assertQueryContentContains('td', 'storage.file.2');
        $this->assertQueryContentContains('td', 'LTO1');
        // not allowed
        $this->assertNotQueryContentContains('td', 'storage.file.1');
        $this->assertNotQueryContentContains('td', 'LTO2');
    }



    /**
     * @group bacula-acl
     * @group bacula-acl-pool-u2
     */
    public function testBaculaAclPoolUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        $this->dispatch('pool/all');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // allowed
        $this->assertQueryContentContains('td', 'Default');
        // not allowed
        $this->assertNotQueryContentContains('td', 'pool.file.7d');
    }



    /**
     * @group bacula-acl
     * @group bacula-acl-client-u2
     */
    public function testBaculaAclClientUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        $this->dispatch('client/all');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // allowed
        $this->assertQueryContentContains('td', 'local.fd');
        // not allowed
        $this->assertNotQueryContentContains('td', 'win32.fd');
    }



    /**
     * @group bacula-acl
     * @group bacula-acl-fileset-u2
     */
    public function testBaculaAclFilesetUser2()
    {
        print "\n".__METHOD__.' ';
        $this->_user2Login();  // logon as 'user2' -- operator role
        $this->dispatch('job/find-form');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // allowed
        $this->assertQueryContentContains('option', 'fileset.test.1');
        // not allowed
        $this->assertNotQueryContentContains('option', 'fileset test 2');
        $this->assertNotQueryContentContains('option', 'fileset_test_3');
        $this->assertNotQueryContentContains('option', 'fileset.test.4');
    }



    /**
     * @group bacula-acl
     * @group bacula-acl-where-u2
     */
    public function testBaculaAclWhereUser2()
    {
        print "\n".__METHOD__.' ';
        $jobid = 3;

        $this->_user2Login();  // logon as 'user2' -- operator role
        // start restore session
        $this->getRequest()
             ->setParams(array(
                'choice' => 'restore_all',
                'jobid'  => $jobid,
                'beginr' => 1
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/restore-choice');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('restorejob');
        $this->resetRequest()
             ->resetResponse();
        // restore options
        $this->getRequest()
             ->setParams(array(
                'where' => '/tmp/webacula_test_access_denied',
                'from_form' => 1,
                'jobid'  => $jobid
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/restore-all');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        $this->assertController('restorejob');
        $this->assertAction('restore-all');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentContains('div', 'Bacula ACLs : access denied for Where');
        $this->assertNotQueryContentContains('div', 'Session of Restore backup is expired');
    }





}