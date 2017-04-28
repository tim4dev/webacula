<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class ClientControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework


    /**
     * @group client
     */
	public function testList()
	{
		print "\n".__METHOD__.' ';
		$this->_rootLogin();
		$this->dispatch('client/all');
		$this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
		$this->assertController('client');
		$this->assertAction('all');
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Clients found');
		$this->assertQueryContentContains('td', 'local.fd');
	}

    /**
     * @group use-bconsole
     * @group client
     */
    public function testStatus()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->dispatch('client/status-client-id/id/1/name/local.fd');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('client');
        $this->assertAction('status-client-id');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK: .* main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertQueryContentRegex('td', "/local.fd Version:.*linux/");
        $this->assertQueryContentRegex('td', "/Daemon started/");
    }

}
