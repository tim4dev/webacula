<?php

class FileControllerTest extends ControllerTestCase
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

	public function testFileList()
	{
		print "\n".__CLASS__."\t".__FUNCTION__.' ';
		$this->dispatch('file/list/jobid/2/page/1');
		$this->assertController('file');
		$this->assertAction('list');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Files found');
		$this->assertQueryContentContains('td', 'file22.dat');
		$this->assertQueryContentContains('td', 'file21.dat');
	}
	
}	