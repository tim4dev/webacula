<?php
class RestoreControllerTest extends ControllerTestCase
{

    const _PREFIX = '_'; // только в нижнем регистре
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework
    protected $ttl_restore_session = 600; // time to live session (10 min)


    /**
     * @access protected
     */
    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        session_write_close();
        parent::tearDown();
    }


    public function testMainForm ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('restorejob/main-form/test/1');
        //echo $this->response->outputBody();exit; // for debug !!!
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('main-form');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('h1', 'Restore Job');
        $this->assertQueryContentContains('li', 'JobId');
        $this->assertQueryContentContains('li', 'Most recent backup');
        $this->assertQueryContentContains('li', 'Before a time');
    }

// TODO
//    /**
//     * @group restore1
//     */
//    public function testRestoreSelectJobId() {
//        print "\n".__METHOD__;
//        $jobid = 3;
//        $jobidhash = md5($jobid);
//        // clear all tmp-tables
//        $this->WbTmpTable = new WbTmpTable(self::_PREFIX, $jobidhash, $this->ttl_restore_session);
//        $this->WbTmpTable->deleteAllTmpTables();
//        $this->assertTrue(TRUE);
//        // choice select to restore
//        echo "\n\t* Choice select to restore";
//        $this->getRequest()
//             ->setParams(array(
//                'choice' => 'restore_select',
//                'jobid'  => $jobid,
//                'beginr' => 1
//             ))
//             ->setMethod('POST');
//        $this->dispatch('restorejob/restore-choice');
//        $this->assertModule('default');
//        $this->assertController('restorejob');
//        $this->assertAction('draw-file-tree');
//        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
//        $this->assertResponseCode(200);
//        $this->assertQueryContentContains('html', $jobidhash); // jobidhash for jobid = 3
////echo $this->response->outputBody();exit; // for debug !!!
//        $this->resetRequest()
//             ->resetResponse();
//
//        // mark dir ajax
//        echo "\n\t* Mark dir (ajax)";
//        $data = '{"path":"/tmp/","jobidhash":"'.$jobidhash.'"}';
//echo "\ndata= ", $data, "\n\n";
//        $this->getRequest()
//             ->setParams(array(
//                'data' => '$data'
//             ))
//             ->setMethod('POST');
//        $this->dispatch('restorejob/mark-dir');
//echo $this->response->outputBody(); // for debug !!!
//    }
//
//
//    /**
//     * @group restore2
//     */
//    public function testRestoreSelectJobId2() {
//        print "\n".__METHOD__;
//        $jobid = 3;
//        $jobidhash = md5($jobid);
//        // clear all tmp-tables
//        $this->WbTmpTable = new WbTmpTable(self::_PREFIX, $jobidhash, $this->ttl_restore_session);
//        $this->WbTmpTable->deleteAllTmpTables();
//        $this->assertTrue(TRUE);
//        // mark dir ajax
//        echo "\n\t* Mark dir (ajax)\n";
//        $data = '{"path":"\/tmp\/","jobidhash":"'.$jobidhash.'"}';
//
//        $phpNative = array('path' => '/tmp/', 'jobidhash' => $jobidhash);
//        $json = Zend_Json::encode($phpNative);
////var_dump($json);
//        $this->getRequest()
//             ->setParams(array(
//                'data' => 1
//             ))
//             ->setMethod('POST');
//        $this->dispatch('restorejob/mark-dir');
////echo $this->response->outputBody(); // for debug !!!
//    }


}

