<?php
class PoolControllerTest extends ControllerTestCase
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


    public function testListAllPool ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('pool/all');
        $this->assertController('pool');
        $this->assertAction('all');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'pool.file.7d');
    }
}