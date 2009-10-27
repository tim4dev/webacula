<?php
class LogControllerTest extends ControllerTestCase
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


    public function testViewConsoleLog ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('log/view-log-id/jobid/2/jobname/job name test 2');
        $this->assertController('log');
        $this->assertAction('view-log-id');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Console messages found');
        $this->assertQueryContentRegex('table', '/Termination:.*Backup OK/');
    }
}