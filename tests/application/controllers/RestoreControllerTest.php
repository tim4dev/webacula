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

    /**
     * @group test1
     */
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
        $this->assertQueryContentContains('form', 'beginrecent'); // page load complete
    }


    /**
     * @group restore_ajax
     */
    public function testRestoreSelectJobId() {
        print "\n".__METHOD__;
        // setup
        $jobid = 4;
        $fileid = 3660;
        $filename = 'file31.dat';
        $file31_dat = '/tmp/webacula/restore/tmp/webacula/test/3/'.$filename;
        $tsleep = 15; // sec. wait to restore

        $jobidhash = md5($jobid);
        // clear all tmp-tables
        $this->WbTmpTable = new WbTmpTable(self::_PREFIX, $jobidhash, $this->ttl_restore_session);
        $this->WbTmpTable->deleteAllTmpTables();
        $this->assertTrue(TRUE);
        // choice select to restore
        echo "\n\t* Choice select to restore";
        $this->getRequest()
             ->setParams(array(
                'choice' => 'restore_select',
                'jobid'  => $jobid,
                'beginr' => 1
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/restore-choice');
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('html', $jobidhash); // jobidhash for jobid = 3
        $this->resetRequest()
             ->resetResponse();

        // mark dir ajax
        echo "\n\t* Mark dir (ajax). ";
        $json = Zend_Json::encode( array('path' => '/tmp/', 'jobidhash' => $jobidhash) );
        $this->getRequest()
             ->setParams(array(
                'data' => $json
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/mark-dir');
        // recieve json
        $data = Zend_Json::decode( $this->response->outputBody() );
        if ( ($data['allok'] != 1) || ($data['total_files'] < 1) )
            $this->assertTrue(FALSE, "\nMark dir fail!\n");
        echo "OK. Files affected = ", $data['total_files'];
        $this->resetRequest()
             ->resetResponse();

        // UNmark dir ajax
        echo "\n\t* Unmark dir (ajax). ";
        $json = Zend_Json::encode( array('path' => '/tmp/', 'jobidhash' => $jobidhash) );
        $this->getRequest()
             ->setParams(array(
                'data' => $json
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/unmark-dir');
        // recieve json
        $data = Zend_Json::decode( $this->response->outputBody() );
        if ( ($data['allok'] != 1) || ($data['total_files'] != 0) )
            $this->assertTrue(FALSE, "\nUnmark dir fail!\n");
        echo "OK. Files affected = ", $data['total_files'];
        $this->resetRequest()
             ->resetResponse();

        // mark file ajax
        echo "\n\t* Mark file (ajax). ";
        $json = Zend_Json::encode( array('fileid' => $fileid, 'jobidhash' => $jobidhash) );
        $this->getRequest()
             ->setParams(array(
                'data' => $json
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/mark-file');
        // recieve json
        $data = Zend_Json::decode( $this->response->outputBody() );
        if ( ($data['allok'] != 1) || ($data['total_files'] < 1) || ($data['filename'] != $filename) )
            $this->assertTrue(FALSE, "\nMark file fail!\n");
        echo "OK. File affected = ", $data['filename'];
        $this->resetRequest()
             ->resetResponse();

        // Unmark file ajax
        echo "\n\t* Unmark file (ajax). ";
        $json = Zend_Json::encode( array('fileid' => $fileid, 'jobidhash' => $jobidhash) );
        $this->getRequest()
             ->setParams(array(
                'data' => $json
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/unmark-file');
        // recieve json
        $data = Zend_Json::decode( $this->response->outputBody() );
        if ( ($data['allok'] != 1) || ($data['total_files'] = 0) || ($data['filename'] != $filename) )
            $this->assertTrue(FALSE, "\nUnmark file fail!\n");
        echo "OK. File affected = ", $data['filename'];
        $this->resetRequest()
             ->resetResponse();
        /*
         * Restore file
         */
        if (file_exists($file31_dat)) {
            unlink($file31_dat);
        }
        // mark file ajax
        echo "\n\t* Restore file: ";
        $json = Zend_Json::encode( array('fileid' => $fileid, 'jobidhash' => $jobidhash) );
        $this->getRequest()
             ->setParams(array(
                'data' => $json
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/mark-file');
        // recieve json
        $data = Zend_Json::decode( $this->response->outputBody() );
        if ( ($data['allok'] != 1) || ($data['total_files'] < 1) || ($data['filename'] != $filename) )
            $this->assertTrue(FALSE, "\nMark file fail!\n");
        $this->resetRequest()
             ->resetResponse();
        // goto restorejob/list-restore
        $this->dispatch('/restorejob/list-restore');
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('list-restore');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        echo " Goto list-restore - OK. ";
        $this->resetRequest()
             ->resetResponse();
        // goto /restorejob/run-restore
        $this->dispatch('restorejob/run-restore');
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('run-restore');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'Connecting to Director');
        $this->assertQueryContentContains('td', 'quit');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->resetRequest()
             ->resetResponse();
        echo " Goto run-restore - OK. Waiting  $tsleep sec. to restore ... ";
        sleep($tsleep);
        if ( !file_exists($file31_dat) ) {
            $this->assertTrue(FALSE, "\nFile not restore : $file31_dat\n");
        }
        echo " Restore file exists - OK.\n";
        unlink($file31_dat);
    }



    /**
     * @group restore_file
     */
    public function testRestoreSingleFile() {
        print "\n".__METHOD__;
        // setup
        $jobid = 3;
        $fileid = 50;
        $filename   = 'file22.dat';
        $where      = '/tmp/webacula/restore';
        $file_full  = '/tmp/webacula/test/2/'.$filename;
        $file_restore = $where.'/tmp/webacula/test/2/'.$filename;
        $client_name_to = 'local.fd';
        $tsleep = 10; // sec. wait to restore
        // form Restore Single File
        $this->getRequest()
             ->setParams(array(
                'fileid'  => $fileid
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/single-file-restore');
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('single-file-restore');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('table', $file_full);
        $this->resetRequest()
             ->resetResponse();
        echo "\n\t* Form Restore Single File - OK.";
        /*
         * Restore single file
         */
        if (file_exists($file_restore)) {
            unlink($file_restore);
        }
        $this->getRequest()
             ->setParams(array(
                'fileid'  => $fileid,
                'client_name_to' => $client_name_to,
                'where' => $where
            ))
            ->setMethod('POST');
        $this->dispatch('restorejob/run-restore-single-file');
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('run-restore-single-file');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'Connecting to Director');
        $this->assertQueryContentContains('td', 'quit');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        $this->resetRequest()
             ->resetResponse();
        echo "\n\t* Goto run-restore single file - OK. Waiting  $tsleep sec. to restore ... ";
        sleep($tsleep);
        if ( !file_exists($file_restore) ) {
            $this->assertTrue(FALSE, "\nSingle file not restore : $file_restore\n");
        }
        echo "\n\t* Restore single file exists - OK.\n";
        unlink($file_restore);
    }


}
