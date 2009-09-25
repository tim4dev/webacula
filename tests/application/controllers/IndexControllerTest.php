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

	/**
	 * @group test-test
	 */
	public function testTest()
   	{
   		print "\n".__METHOD__.' ';
		$this->assertTrue(true);
	}
	 
	
}
