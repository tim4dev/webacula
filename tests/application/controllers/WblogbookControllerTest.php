<?php

class WblogbookControllerTest extends ControllerTestCase
{
   /**
    * @access protected
    */
    protected function tearDown()
    {
        $this->resetRequest();
      $this->resetResponse();
      parent::tearDown();
    }

    /**
     * @group logbook
     */
    public function testPrintableLogbook()
    {
        print "\n".__METHOD__.' ';
        $this->request->setPost(array(
            "date_begin" => date('Y-m-d', time()-2678400),
            "date_end"   => date('Y-m-d', time()),
            "printable_by_date" => 1,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/filterbydate');
        $this->assertController('wblogbook');
        $this->assertAction('filterbydate');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertNotQueryContentContains('table', 'Warning:'); // Zend Framework warning
        $this->assertNotQueryContentContains('table', 'Notice:'); // Zend Framework notice
        $this->assertNotQueryContentContains('table', 'Call Stack'); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('div', 'Printable version');
        $this->assertQueryCountMin('tr', 5);  // не менее 3 строк таблицы
    }

	/**
     * @group logbook
     */
    public function testValidateDateTime()
    {
        print "\n".__METHOD__.' ';
        // add new record
        $this->request->setPost(array(
            "hiddenNew" => 1,
            "logDateCreate"   => date('Y-m-d', time()),
            "logTxt" => __METHOD__ ,
            "logTypeId" => 10,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->assertController('wblogbook');
        $this->assertAction('add');
        $this->assertQueryContentRegex('div', '/ERROR: Record has not been added. Reason.*is not of the format/');
    }

    /**
     * @group logbook
     */
    public function testValidateLogId()
    {
        print "\n".__METHOD__.' ';
        // add new record
        $this->request->setPost(array(
            "hiddenNew" => 1,
            "logDateCreate"   => date('Y-m-d H:i:s', time()),
            "logTxt" => __METHOD__.
                'Lorem ipsum LOGBOOK_ID=9999999 dolor sit amet, '.
                'consectetur adipiscing elit.',
            "logTypeId" => 10,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->assertController('wblogbook');
        $this->assertAction('add');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertQueryContentRegex('div', '/ERROR: Record has not been added. Reason .*Logbook.*is not found in Webacula database/');
    }

    /**
     * @group logbook
     */
    public function testValidateJobId()
    {
        print "\n".__METHOD__.' ';
        // add new record
        $this->request->setPost(array(
            "hiddenNew" => 1,
            "logDateCreate"   => date('Y-m-d H:i:s', time()),
            "logTxt" => __METHOD__.
                'Lorem ipsum dolor sit amet,'.
                'BACULA_JOBID=9999999',
            "logTypeId" => 10,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->assertController('wblogbook');
        $this->assertAction('add');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertQueryContentRegex('div', '/ERROR: Record has not been added. Reason.*JobId.*is not found in Bacula database/');
    }

    /**
     * @group logbook
     */
    public function testAdd()
    {
        print "\n".__METHOD__.' ';
        // add new record
        $this->request->setPost(array(
            "hiddenNew" => 1,
            "logDateCreate"   => date('Y-m-d H:i:s', time()),
            "logTxt" => __METHOD__.
                "\n\nLorem ipsum dolor sit amet,\n".
                "consectetur adipiscing elit.\n".
                "BACULA_JOBID=2\n".
                "Nullam eu magna ut diam egestas fringilla.\n".
                'LOGBOOK_ID=3',
            "logTypeId" => 10,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->assertController('wblogbook');
        $this->assertAction('add');
        $this->assertRedirectTo('/wblogbook/index');
    }




}