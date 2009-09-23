<?php

class IndexControllerTest extends ControllerTestCase
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

	public function testTestAction()
   	{
   		print "\n".__CLASS__.'_Test ';
		$this->assertTrue(true);
	}
	 
	
}
