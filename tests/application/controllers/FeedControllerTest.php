<?php

class FeedControllerTest extends ControllerTestCase
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

	public function testRSS()
	{
		print "\n".__METHOD__.' ';
		$this->dispatch('feed/feed/test/1');
		$this->assertController('feed');
		$this->assertAction('feed');
		//echo $this->response->outputBody();exit; // for debug !!!
		$this->assertResponseCode(200);
		$this->assertQueryContentContains('title', '[CDATA[My Bacula backup server #1]]');
	}
	
}	