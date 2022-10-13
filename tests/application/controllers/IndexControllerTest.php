<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class IndexControllerTest extends ControllerTestCase
{

    /**
     * @group test-test
     */
    public function testTest ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->assertTrue(true);
    }


    /**
     * @group index
     */
    public function testIndex ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('index/index');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('h1', 'Terminated Jobs');
        $this->assertQueryContentContains('h1', 'Scheduled Jobs');
        $this->assertQueryContentContains('h1', 'List of Running Jobs');
        $this->assertQueryContentContains('h1', 'Jobs with errors');
        $this->assertNotQueryContentContains('h1', 'Volumes with errors');
    }
}
