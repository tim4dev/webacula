<?php
class IndexControllerTest extends ControllerTestCase
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


    /**
     * @group test-test
     */
    public function testTest ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->assertTrue(true);
    }


    /**
     * @group index
     */
    public function testIndex ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('index/index');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('h1', 'Terminated Jobs');
        $this->assertQueryContentContains('h1', 'Scheduled Jobs');
        $this->assertQueryContentContains('h1', 'List of Running Jobs');
        $this->assertQueryContentContains('h1', 'Jobs with errors');
        $this->assertNotQueryContentContains('h1', 'Volumes with errors');
    }
}
