<?php
class FileControllerTest extends ControllerTestCase
{

    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    /**
     * @access protected
     */
    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        parent::tearDown();
    }


    public function testFileList ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('file/list/jobid/3/page/1');
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