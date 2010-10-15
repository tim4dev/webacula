<?php
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
        $this->assertController('storage');
        $this->assertAction('storage');
        //echo $this->response->outputBody(); // for debug !!!
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
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('status-id');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        // http://by.php.net/manual/en/function.preg-match.php
        $this->assertQueryContentRegex('td', "/Storage1 Version:.*linux/");
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
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentContains('td', '3901 Device "dev.file.storage.1" (/tmp/webacula/dev) is already unmounted');
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
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentContains('td', '3906 File device "dev.file.storage.1" (/tmp/webacula/dev) is always mounted.');
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
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
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
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', "/Device.*LTO2_2.*mounted/");
    }


}
