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
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('help');
        $this->assertAction('index');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
    }
}