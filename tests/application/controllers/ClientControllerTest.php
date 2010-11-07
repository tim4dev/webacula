<?php

class ClientControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

   /**
    * @access protected
    */
	protected function tearDown()
	{
		$this->resetRequest();
      $this->resetResponse();
      parent::tearDown();
	}

	public function testList()
	{
		print "\n".__METHOD__.' ';
		$this->dispatch('client/all');
		$this->assertController('client');
		$this->assertAction('all');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Clients found');
		$this->assertQueryContentContains('td', 'local.fd');
	}

    /**
     * @group use-bconsole
    */
    public function testStatus()
    {
        print "\n".__METHOD__.' ';
        $this->dispatch('client/status-client-id/id/1/name/local.fd');
      $this->assertController('client');
      $this->assertAction('status-client-id');
      //  echo $this->response->outputBody(); // for debug !!!
      $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
      $this->assertResponseCode(200);
      $this->assertQueryContentContains('td', '1000 OK: main.dir');
      $this->assertNotQueryContentRegex('td', '/Error/i');
      $this->assertQueryContentRegex('td', "/local.fd Version:.*linux/");
      $this->assertQueryContentRegex('td', "/Daemon started/");
   }

}
