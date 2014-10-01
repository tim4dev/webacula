<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


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
        $description = 'очень длинное UTF8 описание задания';
        // add new record
        $this->request->setPost(array(
            'form1'         => 1,
            'name_job'      => 'job.name.test.1',
            'short_desc'    => 'short description '. $description,
            'description'   => "PHPUnit test $description\njob description\n",
            'retention_period' => '3 years'
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wbjobdesc/add');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wbjobdesc');
        $this->assertAction('add');
        // check
        $this->resetRequest();
        $this->resetResponse();
        $this->dispatch('wbjobdesc/index');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
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