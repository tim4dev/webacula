<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class WblogbookControllerTest extends ControllerTestCase
{
    /**
     * @group logbook
     */
    public function testPrintableLogbook()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            "date_begin" => date('Y-m-d', time()-2678400),
            "date_end"   => date('Y-m-d', time()),
            "printable_by_date" => 1,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/filterbydate');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wblogbook');
        $this->assertAction('filterbydate');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
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
        $this->_rootLogin();
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
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
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
        $this->_rootLogin();
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
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wblogbook');
        $this->assertAction('add');
        $this->assertQueryContentRegex('div', '/ERROR: Record has not been added. Reason .*Logbook.*is not found in Webacula database/');
    }

    /**
     * @group logbook
     */
    public function testValidateJobId()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
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
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wblogbook');
        $this->assertAction('add');
        $this->assertQueryContentRegex('div', '/ERROR: Record has not been added. Reason.*JobId.*is not found in Bacula database/');
    }

    /**
     * @group logbook
     */
    public function testAdd()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        // add new record
        $this->request->setPost(array(
            "hiddenNew" => 1,
            "logDateCreate"   => date('Y-m-d H:i:s', time()),
            "logTxt" => __METHOD__.
                "\n\nLorem ipsum dolor sit amet,\n".
                "consectetur adipiscing elit.\n".
                "BACULA_JOBID=2\n".
                "Nullam eu magna ut diam egestas fringilla. Русский текст.\n".
                'LOGBOOK_ID=3',
            "logTypeId" => 10,
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wblogbook');
        $this->assertAction('add');
        $this->assertRedirectTo('/wblogbook/index');
    }


    /**
     * @group logbook
     */
    public function testJobReviewed()
    {
        $jobid = 3;
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            'hiddenNew' => 1,
            'reviewed'  => 1,
            'joberrors' => 1111,
            'jobid'     => $jobid,
            'logDateCreate' => date("Y-m-d H:i:s", time()),
            'logTxt' => __METHOD__."\nJob Reviewed",
            "test" => 1
        ));
        $this->request->setMethod('POST');
        $this->dispatch('wblogbook/add');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('wblogbook');
        $this->assertAction('add');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertRedirectTo('/wblogbook/index');
        $this->resetRequest()
            ->resetResponse();
        // проверить есть ли запись в Job
        Zend_Loader::loadClass('Job');
        $table = new Job();
        $row   = $table->fetchRow("JobId = $jobid");
        if ( $row->reviewed == 0 )
            $this->assertTrue(FALSE, "\nJob Reviewed fail!\n");
        if ( !strpos($row->comment, 'Reviewed') )
            $this->assertTrue(FALSE, "\nComment of Job Reviewed fail!\n");
        // проверить, что запись теперь не показывается в job/problem
        $this->dispatch('job/problem');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('problem');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('td', '/job/detail/jobid/3"');
        // возвращаем все назад
        $data = array(
            'Reviewed' => 0,
            'Comment'  => ''
        );
        $res = $table->update($data, "JobId = $jobid");
        if ( !$res )
            $this->assertTrue(FALSE, "\nBackwards of Job Reviewed fail!\n");
    }


}
