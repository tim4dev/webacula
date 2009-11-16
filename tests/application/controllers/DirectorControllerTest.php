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

    /**
    * @group director
    */
    public function testDirectorListjobtotals()
    {
        print "\n".__METHOD__.' ';
        $this->dispatch('/director/listjobtotals');
        $this->assertModule('default');
        $this->assertController('director');
        $this->assertAction('listjobtotals');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentContains('td', '1000 OK: main.dir');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertResponseCode(200);
    }

   /**
    * @group director
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
        $this->assertQueryContentContains ( 'td', '1000 OK: main.dir' );
        $this->assertNotQueryContentRegex ( 'td', '/Error/i' );
        // http://by.php.net/manual/en/function.preg-match.php
        $this->assertQueryContentRegex ( 'td', "/7  Incr .* OK .* job-name-test-3/" );
        $this->assertQueryContentRegex ( 'td', "/11  Full.* OK .* job.name.test.4/" );
    }


}
