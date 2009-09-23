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

	public function testTest()
   	{
   		print "\n".__CLASS__."\t".__FUNCTION__.' ';
		$this->assertTrue(true);
	}
	 
	
}
