<?php
class IndexControllerTest extends ControllerTestCase
{


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
        $this->dispatch('index/index/test/1');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('h1', 'Terminated Jobs');
        $this->assertQueryContentContains('h1', 'Scheduled Jobs');
        $this->assertQueryContentContains('h1', 'List of Running Jobs');
        $this->assertQueryContentContains('h1', 'Jobs with errors');
        $this->assertNotQueryContentContains('h1', 'Volumes with errors');
    }
}
