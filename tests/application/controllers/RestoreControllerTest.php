<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class RestoreControllerTest extends ControllerTestCase
{

    protected $ttl_restore_session = 1800; // time to live session (30 min)


    /**
     * @access protected
     */
    protected function tearDown ()
    {
        session_write_close();
        parent::tearDown();
    }

    /**
     * @group restore-form
     * @group restore
     */
    public function testMainForm ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('restorejob/main-form/test/1');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('main-form');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('form', 'beginrecent'); // page load complete
    }


    /**
     * @group restore-ajax
     * @group restore
     * @group restore-select-job-id
     */
    public function testRestoreSelectJobId() {
        print "\n".__METHOD__;
        $this->_rootLogin();
        // setup
        $jobid = 4;
        $jobidhash = md5($jobid);
        $filename = 'file31.dat';
        $filepath = '/tmp/webacula/test/3/';
        // find fileid by filename
        $job = new Job();
        $result = $job->getByFileName($filepath, $filename, '', 1, 'ordinary');
        $fileid = $result[0]['fileid']; //1220

        $file31_dat = '/tmp/webacula/restore'. $filepath. $filename;
        $client_name = 'local.fd';
        $tsleep = 25; // sec. wait to restore

        // clear all tmp-tables
        $this->WbTmpTable = new WbTmpTable($jobidhash, $this->ttl_restore_session);
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
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('html', $jobidhash); // jobidhash for jobid = 3
        $this->resetRequest()
             ->resetResponse();

        // change directory - check routeDrawTreeToRestore()
        echo "\n\t* Change directory";
        $this->getRequest()
             ->setParams(array(
                'curdir' => '/tmp/webacula/',
                'beginr' => 0
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/draw-file-tree');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        // check button action
        $this->assertQueryContentContains('table', '<form method="POST" action="/restorejob/list-restore">');
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
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('list-restore');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        echo " Goto list-restore - OK. ";
        $this->resetRequest()
             ->resetResponse();
        // goto /restorejob/run-restore
        $this->getRequest()
             ->setParams(array(
                'client_name' => $client_name
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/run-restore');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('run-restore');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'Connecting to Director');
        $this->assertQueryContentContains('td', 'quit');
        $this->assertNotQueryContentRegex('td', '/Error|Expected/i');
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
     * @group restore-single-file
     * @group restore
     */
    public function testRestoreSingleFile() {
        print "\n".__METHOD__;
        $this->_rootLogin();
        // setup
        $jobid = 3;
        $filename   = 'file22.dat';
        $path       = '/tmp/webacula/test/2/';
        $client_name    = 'local.fd';
        $client_name_to = 'local.fd';
        // find fileid
        $job = new Job();
        $res = $job->getByFileName($path, $filename, $client_name, 1, 'ordinary');
        $fileid = $res[0]['fileid'];
        //print_r($res); exit; // for debug !!!
        $where      = '/tmp/webacula/restore';
        $file_full  = $path.$filename;
        $file_restore = $where.'/tmp/webacula/test/2/'.$filename;
        $tsleep = 25; // sec. wait to restore
        // form Restore Single File
        $this->getRequest()
             ->setParams(array(
                'fileid'  => $fileid
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/restore-single-file');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('restore-single-file');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', $file_full);
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
                'client_name'    => $client_name,
                'where' => $where
            ))
            ->setMethod('POST');
        $this->dispatch('restorejob/run-restore-single-file');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('run-restore-single-file');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('td', 'Connecting to Director');
        $this->assertQueryContentContains('td', 'quit');
        $this->assertNotQueryContentRegex('td', '/Error|Expected/i');
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



    /**
     * @group restore
     * @group restore-draw-single
     */
    public function testDrawFileTreeWithSingleFiles() {
        print "\n".__METHOD__;
        $this->_rootLogin();
        // setup
        $jobid = 13;
        $jobidhash = md5($jobid);
        
        // clear all tmp-tables
        $this->WbTmpTable = new WbTmpTable($jobidhash, $this->ttl_restore_session);
        $this->WbTmpTable->deleteAllTmpTables();
        $this->assertTrue(TRUE);
        // choice select to restore
        $this->getRequest()
             ->setParams(array(
                'choice' => 'restore_select',
                'jobid'  => $jobid,
                'beginr' => 1
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/restore-choice');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('html', $jobidhash); // jobidhash for jobid = 3
        $this->resetRequest()
             ->resetResponse();

        // change directory
        $this->getRequest()
             ->setParams(array(
                'curdir' => '/tmp/webacula/test/',
                'beginr' => 0
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/draw-file-tree');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains( 'td', 'value="/tmp/webacula/test/3/"' );
        $this->assertQueryContentContains( 'td', 'value="/tmp/webacula/test/4/"' );
        $this->resetRequest()
             ->resetResponse();
        
        // change directory
        $this->getRequest()
             ->setParams(array(
                'curdir' => '/tmp/webacula/test/3/subdir/',
                'beginr' => 0
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/draw-file-tree');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains( 'td', 'file_test41.dat' );
        $this->assertQueryContentContains( 'td', 'file_test42.dat' );
        $this->resetRequest()
             ->resetResponse();

        // change directory
        $this->getRequest()
             ->setParams(array(
                'curdir' => '/tmp/webacula/test/4/',
                'beginr' => 0
             ))
             ->setMethod('POST');
        $this->dispatch('restorejob/draw-file-tree');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('restorejob');
        $this->assertAction('draw-file-tree');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains( 'td', 'test41.txt' );
        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('restorejob/cancel-restore');
        $this->logBody( $this->response->outputBody() ); // debug log
    }


}
