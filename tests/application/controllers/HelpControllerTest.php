<?php

class HelpControllerTest extends ControllerTestCase
{
   /**
    * @access protected
    */
	protected function tearDown()
	{
		$this->resetRequest();
      $this->resetResponse();
      parent::tearDown();
	}

	/**
	 * @group help
	 */
	public function testHelp()
	{
		print "\n".__METHOD__.' ';
		$this->dispatch('help/index/test/1');
		$this->assertController('help');
		$this->assertAction('index');
        //echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
	}
	
}	