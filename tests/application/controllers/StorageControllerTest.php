<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class StorageControllerTest extends ControllerTestCase
{

   /**
    * @group storage
    */
    public function testListAllStorage ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('storage/storage');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('storage');
        $this->assertAction('storage');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'storage.file.1');
        $this->assertQueryContentContains('td', 'storage.file.2');
    }


    /**
     * @group use-bconsole
     * @group storage
     */
    public function testStorageStatus ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('storage/status-id/id/1/name/storage.file.1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('status-id');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        // http://by.php.net/manual/en/function.preg-match.php
        $this->assertQueryContentRegex('td', "/Storage1 Version.*linux/");
    }


    /**
     * @group use-bconsole
     * @group storage
     */
    public function testStorageUmount ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('storage/act-mount/act/umount/name/storage.file.1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', '/3901 Device.*dev.file.storage.1.*\/tmp\/webacula\/dev.*is already unmounted/');
    }


    /**
     * @group use-bconsole
     * @group storage
     */
    public function testStorageMount ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('storage/act-mount/act/mount/name/storage.file.1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', '/3906 File device.*dev.file.storage.1.*\/tmp\/webacula\/dev.*is always mounted/');
    }

    /**
     * @group autochanger
     * @group storage
     */
    public function testStorageUmountAutochanger ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            'autochanger' => 1,
            'act'  => 'umount',
            'name' => 'LTO2',
            'drive'=> 2
        ));
        $this->request->setMethod('POST');
        $this->dispatch('storage/act-mount');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', "/Device.*LTO2_2.*unmounted/");
    }



    /**
     * @group autochanger
     * @group storage
     */
    public function testStorageMountAutochanger ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            'autochanger' => 1,
            'act'  => 'mount',
            'name' => 'LTO2',
            'drive'=> 2,
            'slot' => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('storage/act-mount');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', "/Device.*LTO2_2.*mounted/");
    }


}
