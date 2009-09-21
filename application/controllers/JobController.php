<?php
/**
 * Copyright 2007, 2008 Yuri Timofeev tim4dev@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see http://www.gnu.org/licenses/
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 * $Id: JobController.php 398 2009-08-13 23:07:32Z tim4dev $
 */

/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class JobController extends Zend_Controller_Action
{
	// for Zend Paginator
	const ROW_LIMIT_JOBS = 70;

	function init()
	{
	    $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->translate = Zend_Registry::get('translate');
		// load models
		Zend_Loader::loadClass('Job');
		Zend_Loader::loadClass('Timeline');
		Zend_Loader::loadClass('Director');
	}


	/**
	 * Terminated Jobs (executed in last 24 hours)
	 *
	 */
    function terminatedAction()
    {
    	$this->view->titleLastJobs = $this->view->translate->_("Terminated Jobs (executed in last 24 hours)");
		// get data from model
    	$jobs = new Job();
    	$this->view->resultLastJobs = $jobs->GetLastJobs();
    }



    /**
     * Running Jobs
     *
     */
    function runningAction()
    {
    	$this->view->titleRunningJobs = $this->view->translate->_("Information from DB Catalog : List of Running Jobs");
		// get data from model
    	$jobs = new Job();
    	$this->view->resultRunningJobs = $jobs->GetRunningJobs();
    	// получаем информацию от Директора
    	$this->view->titleDirRunningJobs  = $this->view->translate->_("Information from Director : List of Running Jobs");
		$this->view->resultDirRunningJobs = $jobs->GetDirRunningJobs();
    }
    
    /**
     * Running Jobs
     */
    function runningDashboardAction()
    {
    	$this->_helper->viewRenderer->setResponseSegment('job_running');
    	$this->view->titleRunningJobs = $this->view->translate->_("Information from DB Catalog : List of Running Jobs");
		// get data from model
    	$jobs = new Job();
    	$this->view->resultRunningJobs = $jobs->GetRunningJobs();
    	// получаем информацию от Директора
    	$this->view->titleDirRunningJobs  = $this->view->translate->_("Information from Director : List of Running Jobs");
		$this->view->resultDirRunningJobs = $jobs->GetDirRunningJobs();		
    }



	/**
	 * Scheduled Jobs (at 24 hours forward)
	 *
	 */
    function nextAction()
    {
    	$this->view->titleNextJobs = $this->view->translate->_("Scheduled Jobs (at 24 hours forward)");
    	// get static const
    	$this->view->unknown_volume_capacity = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
    	$this->view->new_volume = Zend_Registry::get('NEW_VOLUME');
    	$this->view->err_volume = Zend_Registry::get('ERR_VOLUME');

    	// get data from model
    	$jobs = new Job();
		$this->view->resultNextJobs = $jobs->GetNextJobs();
    }
    
    	/**
	 * Scheduled Jobs (at 24 hours forward)
	 *
	 */
    function nextDashboardAction()
    {
    	$this->_helper->viewRenderer->setResponseSegment('job_next');
    	$this->view->titleNextJobs = $this->view->translate->_("Scheduled Jobs (at 24 hours forward)");
    	// get static const
    	$this->view->unknown_volume_capacity = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
    	$this->view->new_volume = Zend_Registry::get('NEW_VOLUME');
    	$this->view->err_volume = Zend_Registry::get('ERR_VOLUME');

    	// get data from model
    	$jobs = new Job();
		$this->view->resultNextJobs = $jobs->GetNextJobs();
    }


    /**
     * Search Job on attributes : date begin/end, Client, FileSet, etc
     *
     */
    function findFiltersAction()
    {
    	$this->view->titleLastJobs = $this->view->translate->_("List Jobs with filters");
    	$this->view->title = $this->view->translate->_("List Jobs with filters");

		if ( $this->_request->isPost() ) {
			// данные из формы поиска
			$date_begin  = addslashes( trim( $this->_request->getPost('date_begin') ));
			$time_begin  = addslashes( trim( $this->_request->getPost('time_begin') ));
   			$date_end    = addslashes( trim( $this->_request->getPost('date_end') ));
   			$time_end    = addslashes( trim( $this->_request->getPost('time_end') ));
		} else {
			// данные от Paginator
   			$date_begin  = date('Y-m-d', intval($this->_request->getParam('date_begin')) );
   			$time_begin  = date('H:i:s', intval($this->_request->getParam('time_begin')) );
   			$date_end    = date('Y-m-d', intval($this->_request->getParam('date_end')) );
   			$time_end    = date('H:i:s', intval($this->_request->getParam('time_end')) );
		}
   		$client      = addslashes(trim( $this->_request->getParam('client') ));
   		$fileset     = addslashes(trim( $this->_request->getParam('fileset') ));
   		$jlevel		 = addslashes(trim( $this->_request->getParam('jlevel') ));
   		$jstatus	 = addslashes(trim( $this->_request->getParam('jstatus') ));
   		$jtype		 = addslashes(trim( $this->_request->getParam('jtype') ));
   		$volname	 = addslashes(trim( $this->_request->getParam('volname') ));
   		$orderp      = addslashes(trim($this->_request->getParam('order', '')));

   		$job = new Job();
   		$select = $job->getSelectFilteredJob($date_begin, $time_begin, $date_end, $time_end,
								$client, $fileset, $jlevel, $jstatus, $jtype, $volname,
								$orderp);

   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

		$this->view->date_begin = strtotime($date_begin);
		$this->view->time_begin = strtotime($time_begin);
   		$this->view->date_end   = strtotime($date_end);
   		$this->view->time_end   = strtotime($time_end);
   		$this->view->client     = $client;
   		$this->view->fileset    = $fileset;
   		$this->view->jlevel     = $jlevel;
   		$this->view->jstatus    = $jstatus;
   		$this->view->jtype      = $jtype;
   		$this->view->volname    = $volname;
   		
   		$paginator = Zend_Paginator::factory($select);
		Zend_Paginator::setDefaultScrollingStyle('Sliding');
		$paginator->setItemCountPerPage(self::ROW_LIMIT_JOBS);
		$paginator->setCurrentPageNumber($this->_getParam('page', 1));
		$this->view->paginator = $paginator;
		$paginator->setView($this->view);
    }



    /**
     * Search Job on attributes : JobId
     *
     */
    function findJobIdAction()
    {
    	$this->view->titleLastJobs = $this->view->translate->_("List Jobs by JobId") .
    			' : ' . $this->_request->getParam('jobid');
    	$this->view->title = $this->view->translate->_("List Jobs by JobId");
    	$jobid = intval(trim( $this->_request->getParam('jobid') ));
		$job = new Job();
   		$this->view->resultLastJobs = $job->getByJobId($jobid);
		echo $this->renderScript('job/terminated.phtml');
    }


    /**
     * Search Job on attributes : Volume Name
     * See <bacula>/src/dird/query.sql
     *
     */
    function findVolumeNameAction()
    {
    	$this->view->titleLastJobs = $this->view->translate->_("List Jobs by Volume Name") .
    		' : ' . $this->_request->getParam('volname');
    	$this->view->title = $this->view->translate->_("List Jobs by Volume Name");
    	$volname = addslashes(trim( $this->_request->getParam('volname') ));
		$job = new Job();
		$this->view->resultLastJobs = $job->getByVolumeName($volname);
		echo $this->renderScript('job/terminated.phtml');
    }



    /**
     * For FORM SELECT
     *
     */
    function findFormAction()
    {
    	$this->view->title = $this->view->translate->_("Search Jobs");
    	// get data for form
    	Zend_Loader::loadClass('Client');
    	$clients = new Client();
    	$this->view->clients = $clients->fetchAll();
    	Zend_Loader::loadClass('FileSet');
    	$filesets = new FileSet();
    	$this->view->filesets = $filesets->fetchAll();
    }


    /**
     * Detail information about Job
     *
     */
    function detailAction()
    {
    	//http://localhost/webacula/job/detail/jobid/4436
    	$this->view->titleJob = $this->view->translate->_("Detail JobId") . " " . $this->_request->getParam('jobid');
    	$jobid = intval(trim( $this->_request->getParam('jobid') ));
    	$this->view->jobid = $jobid;
    	$job = new Job();
    	$adetail = $job->getDetailByJobId($jobid);
		$this->view->resultJob = $adetail['job'];
		$this->view->resultVol = $adetail['volume'];
    }


    /**
     * Jobs with errors/problems (last 14 days)
     *
     */
    function problemAction()
    {
		$this->view->titleProblemJobs = $this->view->translate->_("Jobs with errors (last 7 days)");
		// get data from model
    	$jobs = new Job();
    	$this->view->resultProblemJobs = $jobs->GetProblemJobs();
    }
    
        /**
     * Jobs with errors/problems (last 14 days)
     *
     */
    function problemDashboardAction()
    {
    	$this->_helper->viewRenderer->setResponseSegment('job_problem');
		$this->view->titleProblemJobs = $this->view->translate->_("Jobs with errors (last 7 days)");
		// get data from model
    	$jobs = new Job();
    	$this->view->resultProblemJobs = $jobs->GetProblemJobs();
    }


    /**
     * Graph timeline for Jobs
     *
     */
    function timelineAction()
    {
    	// http://localhost/webacula/job/timeline/
    	$datetimeline = addslashes(trim( $this->_request->getParam('datetimeline', date('Y-m-d', time()-86400)) ));

    	Zend_Loader::loadClass('Zend_Validate_Date');
		$validator = new Zend_Validate_Date();
		if ( !$validator->isValid($datetimeline) ) {
            $this->view->err_msg = $validator->getMessages(); // сообщения валидатора
            $this->view->result = null;
            return;
		}
		
		if ( !extension_loaded('gd') ) {
		  	// No GD lib (php-gd) found
    		$this->view->result = null;
    		$this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8');
    		throw new Zend_Exception($this->view->translate->_('ERROR: The GD extension isn`t loaded. Please install php-gd package.'));
			return;
		}

    	$this->view->title = $this->view->translate->_("Timeline for date") . " " . $datetimeline;
   		$timeline = new Timeline;
   		$this->view->atime = $timeline->GetDataTimeline($datetimeline);
   		$this->view->result = $datetimeline;
   		// вызвать ChartController -> timelineAction() для проверки, что он сможет рисовать
    }


    /**
	 * Run Job
	 *
	 */
    function runJobAction()
    {
    	$this->view->title = $this->view->translate->_("Run Job");

		if( $this->_request->isPost() ) {
            $jobname = trim( $this->_request->getParam('jobname') );
            $this->view->jobname = $jobname;
            // запускаем задание
			$director = new Director();           
    	    if ( !$director->isFoundBconsole() )	{
				$this->view->result_error = 'NOFOUND_BCONSOLE';
    		  	$this->render();
    		  	return;
    	    }
			$astatusdir = $director->execDirector(
" <<EOF
run job=\"$jobname\" yes
.
@sleep 3
status dir
@quit
EOF"		
			); 
        	$this->view->command_output = $astatusdir['command_output'];
	        // check return status of the executed command
    	    if ( $astatusdir['return_var'] != 0 )	{
				$this->view->result_error = $astatusdir['result_error'];
			}
	   	    // показываем вывод Director
   		    echo $this->renderScript('/job/run-job-output.phtml');
   	    	return;
    	}
    	// get data from model
    	$jobs = new Job();
    	$this->view->result = $jobs->getListJobs();
    }


    /**
     * List last NN Jobs run
     * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
     *
     */
    function listLastJobsRunAction()
    {
    	$numjob = intval(trim( $this->_request->getParam('numjob', 20) ));
    	$num_max = 200;
    	if ( $numjob <= 0 ) { $numjob = 20;	}
    	if ( $numjob > $num_max ) { $numjob = $num_max;	}

	    $this->view->titleLastJobs = sprintf($this->view->translate->_("List last %s Jobs run"), $numjob);
	    $this->view->title = sprintf($this->view->translate->_("List last %s Jobs run"), $numjob);
		$job = new Job();
		$this->view->resultLastJobs = $job->getLastJobRun($numjob);	
		echo $this->renderScript('job/terminated.phtml');
    }
    
    /**
     * List last NN Jobs run
     * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
     */
    function terminatedDashboardAction()
    {
    	$this->_helper->viewRenderer->setResponseSegment('job_terminated');
	    $this->view->title = $this->view->translate->_("Terminated Jobs (executed in last 24 hours)");
		$job = new Job();
		$this->view->result = $job->GetLastJobs();
    }


    /**
     * List Jobs where a given File is saved
     *
     */
    function findFileNameAction()
    {
        $limit = 30;
    	$namefile = $this->_request->getParam('namefile'); // имя файла м.б. с лидирующими и концевыми пробелами
    	$client   = trim( $this->_request->getParam('client_nf') );
		$this->view->title = sprintf($this->view->translate->_("List Jobs where %s is saved (limit %s)"), $namefile, $limit);
		$job = new Job();
		$this->view->result = $job->getByFileName($namefile, $client, $limit);
    }


}
