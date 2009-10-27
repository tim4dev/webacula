<?php
class HelpControllerTest extends ControllerTestCase
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
     * @group help
     */
    public function testHelp ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('help/index');
        $this->assertController('help');
        $this->assertAction('index');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
    }
}