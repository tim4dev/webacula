<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class DirectorControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    /**
    * @group director
    */
    public function testDirectorListjobtotals()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->dispatch('/director/listjobtotals');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        //echo $body;exit; // for debug !!!
        $this->assertModule('default');
        $this->assertController('director');
        $this->assertAction('listjobtotals');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->assertResponseCode(200);
    }

   /**
    * @group director
    */
    public function testDirectorStatusdir() {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch ( 'director/statusdir' );
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule ( 'default' );
        $this->assertController ( 'director' );
        $this->assertAction ( 'statusdir' );
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode ( 200 );
        $this->assertQueryContentRegex('td', '/1000 OK.*main.dir/');
        $this->assertNotQueryContentRegex ( 'td', '/Error/i' );
        // http://by.php.net/manual/en/function.preg-match.php
        $this->assertQueryContentRegex( 'td', "/Full.* OK .* job.name.test.autochanger.1/" );
        $this->assertQueryContentRegex( 'td', "/Diff.* OK .* job_name_test_2/" );
    }


}
