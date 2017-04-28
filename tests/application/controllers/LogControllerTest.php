<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class LogControllerTest extends ControllerTestCase
{

   /**
    * @group log
    */
    public function testViewConsoleLog ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('log/view-log-id/jobid/3/jobname/job name test 2');
        $this->logBody( $this->response->outputBody() ); // debug log
        $body = $this->response->outputBody();
        $this->_isLogged($body);
        if ( preg_match('/символы в кодировке utf8.*������� � ��������� cp1251/', $body) )
            echo ' Non-english characters found OK ';
        else
            throw new RuntimeException('Non-english characters not found!');
        $this->assertController('log');
        $this->assertAction('view-log-id');
        $this->assertNotQueryContentRegex('table', '/Exception:|Notice:|Call Stack/'); // иногда Bacula глючит и выдает Warning в логах
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Console messages found');
        $this->assertQueryContentRegex('table', '/Termination:.*Backup OK/');
        // non-english chars - phpunit not worked !!!
        //$this->assertQueryContentRegex('table', '/символы в кодировке utf8/');
        //$this->assertQueryContentRegex('table', '/������� � ��������� cp1251/');
    }
}