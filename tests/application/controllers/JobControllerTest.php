<?php

class JobControllerTest extends ControllerTestCase
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

	 
	public function testJobTerminated()
	{	
		print "\n".__CLASS__.'_JobTerminated ';
        $this->dispatch('job/terminated');
		$this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('terminated');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Jobs found');
		$this->assertQueryCount('tr', 11);  // 11 строк таблицы
	}
	
	public function testJobRunning()
	{	
		print "\n".__CLASS__.'_JobRunning ';
        $this->dispatch('job/running');
		$this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('running');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertQueryContentContains('div', 'Information from Director : No Running Jobs found');
		$this->assertNotQueryContentContains('div', 'Information from DB Catalog : No Running Jobs found');
		$this->assertQueryContentContains('td', 'job.name.test.4');
		$this->assertQueryCount('tr', 2);
	}
	
	
	public function testJobNext()
	{	
		print "\n".__CLASS__.'_JobNext ';
        $this->dispatch('job/next');
		$this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('next');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Scheduled Jobs found');
		$this->assertQueryContentContains('td', 'job.name.test.1');
		$this->assertQueryContentContains('td', 'job name test 2');
		$this->assertQueryContentContains('td', 'job-name-test-3');
		$this->assertQueryCount('tr', 4);
	}
	
	public function testJobProblem()
	{	
		print "\n".__CLASS__.'_JobProblem ';
        $this->dispatch('job/problem');
		$this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('problem');
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode(200);
		$this->assertNotQueryContentContains('div', 'No Jobs found');
		$this->assertQueryContentContains('td', 'job name test 2');
		$this->assertQueryContentContains('td', 'job.name.test.4');
		$this->assertQueryCount('tr', 3);
	}


	
}
