<?php

class WbjobdescControllerTest extends ControllerTestCase
{

    /**
     * Check non english character in DB
     * @group jobdesc
     */
    public function testAdd()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $description = 'описание задания';
        // add new record
        $this->request->setPost(array(
            'form1'         => 1,
            'name_job'      => 'job name test 2',
            'description'   => "PHPUnit test $description\njob description\n",
            'retention_period' => '3 years'
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wbjobdesc/add');
        $this->assertController('wbjobdesc');
        $this->assertAction('add');
        // check
        $this->resetRequest();
        $this->resetResponse();
        $this->dispatch('wbjobdesc/index');
        // echo $this->response->outputBody(); // for debug !!!
        $this->assertModule('default');
        $this->assertController('wbjobdesc');
        $this->assertAction('index');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $pos = strpos($this->response->outputBody(), $description);
        // $this->assertQueryContentContains('td', $description);
        if ($pos === false) {
            $this->assertTrue(FALSE, "string '".$description.'" not found');
        }
    }


}