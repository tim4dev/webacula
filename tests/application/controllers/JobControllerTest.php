<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class JobControllerTest extends ControllerTestCase
{

    const DELAY_AFTER_JOB = 15;
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework


    protected function mySleep($t = self::DELAY_AFTER_JOB)
    {
        echo " sleep ", $t, " seconds ";
        sleep($t); // подождать пока выполнится
    }


   /**
    * @group job1
    */
   public function testJobTerminated()
   {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('job/terminated');
      $this->_isLogged($this->response->outputBody());
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('job');
      $this->assertAction('terminated');
      $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
      $this->assertResponseCode(200);
      $this->assertNotQueryContentContains('div', 'No Jobs found');
      $this->assertQueryCountMin('tr', 11);  // не менее 11-ти строк таблицы
      // test show Job short description
      $this->assertQueryContentContains('td', 'short description');
   }


   /**
    * @group job1
    */
   public function testJobRunning()
   {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('job/running');
      $this->_isLogged($this->response->outputBody());
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('job');
      $this->assertAction('running');
      $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
      $this->assertResponseCode(200);
      $this->assertQueryContentContains('div', 'Information from Director : No Running Jobs found');
      $this->assertNotQueryContentContains('div', 'Information from DB Catalog : No Running Jobs found');
      $this->assertQueryContentContains('td', 'job.name.test.4');
      $this->assertQueryCount('tr', 2);
      // test show Job short description
      $this->assertQueryContentContains('td', 'short desc2');
   }

   /**
    * @group job1
    */
	public function testJobNext()
   {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('job/next');
      $this->_isLogged($this->response->outputBody());
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('job');
      $this->assertAction('next');
      $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
      $this->assertResponseCode(200);
      $this->assertNotQueryContentContains('div', 'No Scheduled Jobs found');
      $this->assertQueryContentContains('td', 'job.name.test.1');
      $this->assertQueryContentContains('td', 'job name test 2');
      $this->assertQueryContentContains('td', 'job-name-test-3');
      $this->assertQueryCount('tr', 4);
      // test show Job short description
      $this->assertQueryContentContains('td', 'short description');
   }

   /**
    * @group job1
    */
   public function testJobProblem()
   {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('job/problem');
      $this->_isLogged($this->response->outputBody());
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('job');
      $this->assertAction('problem');
      $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
      $this->assertResponseCode(200);
      $this->assertNotQueryContentContains('div', 'No Jobs found');
      $this->assertQueryContentContains('td', 'job name test 2');
      $this->assertQueryContentContains('td', 'job.name.test.4');
      $this->assertQueryCount('tr', 3);
      // test show Job short description
      $this->assertQueryContentContains('td', 'short desc2');
   }


    /**
     * run Job with incorrect Job name
     * @group job1
     */
    public function testRunJobWrong()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $jobname = 'wrong job name';
        $this->getRequest()
             ->setParams(array(
                 'jobname' => $jobname,
                 'checkbox_now' => 'on',
                 'from_form' => '1') )
             ->setMethod('POST');
        $this->dispatch('job/run-job');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('run-job');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        // zend form validate and error decorator output
        $this->assertQueryContentContains('td', "'$jobname' was not found in the haystack");
    }


    /**
     * run job.name.test.1
     * @group job-nonreusable
     * @group run-job1
     */
    public function testRunJob1()
    {
        print "\n".__METHOD__.' (nonreusable) ';
        $this->_rootLogin();
        // create new file
        $new_file = '/tmp/webacula/test/1/'.__METHOD__;
        $file = fopen("$new_file", 'w');
        if( !$file ) {
            $this->assertTrue(FALSE, "Unable to write file '$new_file'");
        }
        fwrite($file, __METHOD__."\n");
        fclose($file);
        // test
        $this->getRequest()
             ->setParams(array(
                'jobname' => 'job.name.test.1',
                'checkbox_now' => 'on',
                'from_form' => '1') )
             ->setMethod('POST');
        $this->dispatch('job/run-job');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('run-job');        
        $this->mySleep(18); // подождать пока выполнится
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK: .* main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        // http://php.net/manual/en/function.preg-match.php
        $pattern = '/Increme.*job.name.test.1.*is running|'.
                   'Increme.*job.name.test.1.*is waiting for Client|'.
                   'Increme.*job.name.test.1.*has terminated|'.
                   '1[0-9]  Incr.*0.*0.*OK.*job.name.test.1|'.
                   '1[1-9].*Increme.*job.name.test.1.*is waiting on Storage|'.
                   '1[1-9].*Increme.*job.name.test.1.*SD despooling Attributes/';
        $this->assertQueryContentRegex('td', $pattern);
    }


    /**
     * run 'job name test 2'
     * @group job-nonreusable
     * @group run-job2
     */
    public function testRunJob2()
    {
        print "\n".__METHOD__.' (nonreusable) ';
        $this->_rootLogin();
        $this->getRequest()
             ->setParams(array(
                'jobname'   => 'job name test 2',
                'checkbox_now' => 'on',
                'from_form' => '1') )
             ->setMethod('POST');
        $this->dispatch('job/run-job');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('run-job');
        $this->mySleep(); // подождать пока выполнится
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK: .* main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        //echo $this->response->outputBody();// for debug !!!
        // http://by.php.net/manual/en/function.preg-match.php
        $pattern = '/Diff.*job name test 2.*is running|'.
                   'Diff.*job name test 2.*has terminated|'.
                   '1[0-9].*Diff.*OK.*job name test 2|'.
                   '1[0-9].*Diff.*job name test 2.*is waiting on Storage/';
        $this->assertQueryContentRegex('td', $pattern);
    }


    /**
     * run Job with options
     * @group job-nonreusable
     */
    public function testRunJobWithOptions()
    {
        print "\n".__METHOD__.' (nonreusable) ';
        $this->_rootLogin();
        $this->getRequest()
             ->setParams(array(
                'jobname' => 'job.name.test.4',
                'client'  => 'local.fd',
                'fileset' => 'fileset.test.4',
                'storage' => 'storage.file.2',
                'level'   => 'Full',
                'spool'   => 'no',
                'checkbox_now' => 'on',
                'from_form' => '1') )
             ->setMethod('POST');
        $this->dispatch('job/run-job');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('run-job');
        $this->mySleep(); // подождать пока выполнится
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentRegex('td', '/1000 OK: .* main.dir/');
        $this->assertNotQueryContentRegex('td', '/Error/i');
        //echo $this->response->outputBody();// for debug !!!
        $pattern = '/Full.*job.name.test.4.*is running|'.
                   'Full.*job.name.test.4.*has terminated|'.
                   '1[0-9].*Full.*OK.*job.name.test.4|'.
                   '1[0-9].*Full.*job.name.test.4.*is waiting on Storage/';
        $this->assertQueryContentRegex('td', $pattern);
    }


    /**
     * find Job by incorrect Id
     * @group job2
     */
    public function testJobFindByIdWrong()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->getRequest()
             ->setParams(array('jobid'     => '1111'))
             ->setMethod('POST');
        $this->dispatch('job/find-job-id');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('find-job-id');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('div', 'No Jobs found');
    }

   /**
    * @group job2
    */
    public function testJobFindById()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->getRequest()
             ->setParams(array('jobid'     => '5'))
             ->setMethod('POST');
        $this->dispatch('job/find-job-id');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('find-job-id');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'job.name.test.1');
        $this->assertQueryCount('tr', 2);
        // test show Job short description
        $this->assertQueryContentContains('thead', 'Short Job Description');
    }

    /**
     * find Jobs with Level = Diff
     * @group job2
     */
    public function testJobFindByFilters()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            "jlevel" => "D",
            "date_begin" => date('Y-m-d', time()-86400),
            "time_begin" => date('H:i:s', time()-86400),
            "date_end"   => date('Y-m-d', time()),
            "time_end"   => date('H:i:s', time())
        ));
        $this->request->setMethod('POST');
        $this->dispatch('job/find-filters');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('find-filters');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'job name test 2');
        $this->assertQueryContentContains('td', 'job.name.test.1');
        $this->assertQueryCountMin('tr', 3);
        // test show Job short description
        $this->assertQueryContentContains('td', 'short description');
    }


   /**
    * find Jobs with Volume name
    * @group job2
    */
    public function testJobFindByVolumeName()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            "volname" => "pool.file.7d.0001"
        ));
        $this->request->setMethod('POST');
        $this->dispatch('job/find-volume-name');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('find-volume-name');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'job.name.test.1');
    }


   /**
    * find last NN Jobs
    * @group job2
    */
    public function testFindLastJobs()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            "numjob" => 5
        ));
        $this->request->setMethod('POST');
        $this->dispatch('job/list-last-jobs-run');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('list-last-jobs-run');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryCount('tr', 6);
        $this->assertQueryContentContains('td', 'job name test 2');
        $this->assertQueryContentContains('td', 'job.name.test.1');
        // test show Job short description
        $this->assertQueryContentContains('td', 'short desc2');
    }

   /**
    * @group job2
    */
    public function testJobFindByFileName()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            "namefile" => "0 Файл'.txt"
        ));
        $this->request->setMethod('POST');
        $this->dispatch('job/find-file-name');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertModule('default');
        $this->assertController('job');
        $this->assertAction('find-file-name');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'job.name.test.1');
        $this->assertQueryCount('tr', 2);
    }

   /**
    * @group job2
    */
    public function testDetailJob()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->dispatch('job/detail/jobid/3');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('job');
        $this->assertAction('detail');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'job name test 2');
        // test show Job short description
        $this->assertQueryContentContains('td', 'short description');
    }
    
   /**
    * @group job3
    */
    public function testShowJob()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->dispatch('job/show-job/jobname/job.name.test.1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('job');
        $this->assertAction('show-job');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Jobs found');
        $this->assertQueryContentContains('td', 'Job: name=job.name.test.1 JobType=66 level=Incremental Priority=10 Enabled=1');
    }

}
