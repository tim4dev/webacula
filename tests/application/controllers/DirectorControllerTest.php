<?php

class DirectorControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

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
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
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
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode ( 200 );
        $this->assertQueryContentContains ( 'div', '1000 OK: main.dir' );
        $this->assertNotQueryContentRegex ( 'div', '/Error/i' );
        // http://by.php.net/manual/en/function.preg-match.php
        $this->assertQueryContentRegex ( 'div', "/5  Diff .* OK .* job_name_test_2/" );
        $this->assertQueryContentRegex ( 'div', "/6  Incr .* OK .* job-name-test-3/" );
        $this->assertQueryContentRegex ( 'div', "/7  Diff .* OK .* job.name.test.1/" );
        $this->assertQueryContentRegex ( 'div', "/8  Incr .* OK .* job_name_test_2/" );
        $this->assertQueryContentRegex ( 'div', "/9  Incr .* OK .* job-name-test-3/" );
        $this->assertQueryContentRegex ( 'div', "/10  Full.* OK .* job.name.test.4/" );
        $this->assertQueryContentRegex ( 'div', "/11  Full.* OK .* job.name.test.4/" );
    }


}
