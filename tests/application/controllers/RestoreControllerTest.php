<?php
class RestoreControllerTest extends ControllerTestCase
{


    /**
     * @access protected
     */
    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        session_write_close();
        parent::tearDown();
    }


    public function testMainForm ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('restorejob/main-form/test/1');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('main-form');
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
        $this->assertQueryContentContains('h1', 'Restore Job');
        $this->assertQueryContentContains('li', 'JobId');
        $this->assertQueryContentContains('li', 'Most recent backup');
        $this->assertQueryContentContains('li', 'Before a time');
    }
}
