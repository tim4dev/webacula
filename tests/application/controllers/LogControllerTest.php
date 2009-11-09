<?php
class LogControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Notice:|Call Stack/'; // Zend Framework

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
        $this->dispatch('log/view-log-id/jobid/3/jobname/job name test 2');
        $this->assertController('log');
        $this->assertAction('view-log-id');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Console messages found');
        $this->assertQueryContentRegex('table', '/Termination:.*Backup OK/');
    }
}