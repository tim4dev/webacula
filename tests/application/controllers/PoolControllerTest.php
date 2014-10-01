<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class PoolControllerTest extends ControllerTestCase
{

   /**
    * @group pool
    */
    public function testListAllPool ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('pool/all');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('pool');
        $this->assertAction('all');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'pool.file.7d');
    }
}