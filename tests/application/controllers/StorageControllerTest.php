<?php
class StorageControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    /**
     * @access protected
     */
    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        parent::tearDown();
    }


    public function testListAllStorage ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('storage/storage');
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
     */
    public function testStorageStatus ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('storage/status-id/id/1/name/storage.file.1');
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
     */
    public function testStorageUmount ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('storage/act-mount/act/umount/name/storage.file.1');
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
     */
    public function testStorageMount ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('storage/act-mount/act/mount/name/storage.file.1');
        $this->assertModule('default');
        $this->assertController('storage');
        $this->assertAction('act-mount');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentContains('td', '3906 File device "dev.file.storage.1" (/tmp/webacula/dev) is always mounted.');
    }

}
