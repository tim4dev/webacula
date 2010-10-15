<?php
class HelpControllerTest extends ControllerTestCase
{

    /**
     * @group help
     */
    public function testHelp ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('help/index');
        $this->_isLogged($this->response->outputBody());
        $this->assertController('help');
        $this->assertAction('index');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
    }
}