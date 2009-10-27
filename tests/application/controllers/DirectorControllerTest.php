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
    	print "\n".__METHOD__.' ';
        $this->dispatch('/director/listjobtotals');
		$this->assertModule('default');
        $this->assertController('director');
        $this->assertAction('listjobtotals');
        $this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
        $this->assertQueryContentContains('div', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('div', '/Error/i');
        $this->assertResponseCode(200);
    }

   /**
    * @group use-bconsole
    */
	public function testDirectorStatusdir() {
		print "\n" . __METHOD__ . ' ';
		$this->dispatch ( 'director/statusdir' );
		$this->assertModule ( 'default' );
		$this->assertController ( 'director' );
		$this->assertAction ( 'statusdir' );
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
		$this->assertResponseCode ( 200 );
		$this->assertQueryContentContains ( 'div', '1000 OK: main.dir' );
		$this->assertNotQueryContentRegex ( 'div', '/Error/i' );
		// http://by.php.net/manual/en/function.preg-match.php
		//        $this->assertQueryContentRegex('div', "/1  Full      3,608    57.60 K  OK .* job.name.test.1/");
		//        $this->assertQueryContentRegex('div', "/3  Full          5    6.747 K  OK .* job-name-test-3/");
		//        $this->assertQueryContentRegex('div', "/4  Incr          2    1.448 K  OK .* job.name.test.1/");
		$this->assertQueryContentRegex ( 'div', "/5  Diff .* OK .* job_name_test_2/" );
		$this->assertQueryContentRegex ( 'div', "/6  Incr .* OK .* job-name-test-3/" );
		$this->assertQueryContentRegex ( 'div', "/7  Diff .* OK .* job.name.test.1/" );
		$this->assertQueryContentRegex ( 'div', "/8  Incr .* OK .* job_name_test_2/" );
		$this->assertQueryContentRegex ( 'div', "/9  Incr .* OK .* job-name-test-3/" );
		$this->assertQueryContentRegex ( 'div', "/10  Full.* OK .* job.name.test.4/" );
		$this->assertQueryContentRegex ( 'div', "/11  Full.* OK .* job.name.test.4/" );
	}


}
