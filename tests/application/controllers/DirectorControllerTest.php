<?php

class DirectorControllerTest extends ControllerTestCase
{
   /**
    * @access protected
    */
	protected function tearDown()
	{
		$this->reset;
        parent::tearDown();
	}
 
	public function testDirectorListjobtotals()
    {   
    	print "\n".__CLASS__.'_DirectorListjobtotals ';
        $this->dispatch('/director/listjobtotals');
		$this->assertModule('default');
        $this->assertController('director');
        $this->assertAction('listjobtotals');
        $this->assertQueryContentContains('div', '1000 OK: main.dir');
        $this->assertResponseCode(200);
    }

	public function testDirectorStatusdir()
	{	
		print "\n".__CLASS__.'_DirectorStatusdir ';
        $this->dispatch('director/statusdir');
		$this->assertModule('default');
        $this->assertController('director');
        $this->assertAction('statusdir');
		//echo $this->response->outputBody(); // for debug !!!        
        $this->assertQueryContentContains('div', '1000 OK: main.dir');
        $this->assertQueryContentRegex('div', "/1  Full      3,608    57.60 K  OK .* job.name.test.1/");
        $this->assertQueryContentRegex('div', "/3  Full          5    6.747 K  OK .* job-name-test-3/");
        $this->assertQueryContentRegex('div', "/4  Incr          2    1.448 K  OK .* job.name.test.1/");
        $this->assertQueryContentRegex('div', "/5  Diff          2    1.937 K  OK .* job_name_test_2/");
        $this->assertQueryContentRegex('div', "/6  Incr          2    2.405 K  OK .* job-name-test-3/");
        $this->assertQueryContentRegex('div', "/7  Diff          3    3.141 K  OK .* job.name.test.1/");
        $this->assertQueryContentRegex('div', "/8  Incr          2    2.178 K  OK .* job_name_test_2/");
        $this->assertQueryContentRegex('div', "/9  Incr          2    2.649 K  OK .* job-name-test-3/");
        $this->assertQueryContentRegex('div', "/10  Full          7    16.62 K  OK .* job.name.test.4/");
        $this->assertQueryContentRegex('div', "/11  Full          7    16.62 K  OK .* job.name.test.4/");
        $this->assertResponseCode(200);
	}
	
	
	
	
	
}
