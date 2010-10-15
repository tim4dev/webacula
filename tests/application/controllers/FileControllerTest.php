<?php
class FileControllerTest extends ControllerTestCase
{

   /**
    * @group file
    */
    public function testFileList ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('file/list/jobid/3/page/1');
        $this->_isLogged($this->response->outputBody());
        $this->assertController('file');
        $this->assertAction('list');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Files found');
        $this->assertQueryContentContains('td', 'file22.dat');
        $this->assertQueryContentContains('td', 'file21.dat');
    }

}