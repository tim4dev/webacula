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
		Zend_Loader::loadClass('Zend_Paginator');
	    $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->translate = Zend_Registry::get('translate');
		// load model
		Zend_Loader::loadClass('Job');
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
    	$ret = $jobs->GetLastJobs();
    	$this->view->resultLastJobs = $ret->fetchAll();
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
    	$ret = $jobs->GetRunningJobs();
    	$this->view->resultRunningJobs = $ret->fetchAll();
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
    	$aret = $jobs->GetNextJobs();
		$this->view->resultNextJobs = $aret;
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
   		if ( $orderp ) {
   		    $order = array($orderp . ' ASC', 'JobId');
   		} else {
   		    $order = array('JobId');
   		}

   		//echo "<pre>begin: $date_begin $time_begin\nend: $date_end $time_end\nclient: $client\nfileset: $fileset\n" .
   		//		"level: $jlevel\nstatus: $jstatus\ntype: $jtype\nvol name: $volname"; // DEBUG

   		if ( isset($date_begin, $time_begin, $date_end, $time_end, $client, $fileset) )	{

   			$db = Zend_Registry::get('db_bacula');
   			// make select from multiple tables
   			$select = new Zend_Db_Select($db);

   			// !!! IMPORTANT !!! с Zend Paginator нельзя использовать DISTINCT иначе не работает в PDO_PGSQL

   			switch ($this->db_adapter) {
            case 'PDO_MYSQL':
            	//$select->distinct();
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				    'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
   				    'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
   				    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       			    'FileSetId', 'PurgedFiles', 'JobStatus',
       			    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				    'StartTime', 'EndTime',
   				    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       			    'FileSetId', 'PurgedFiles', 'JobStatus',
       			    'DurationTime' => '(EndTime - StartTime)'));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
					'StartTime', 'EndTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
					'FileSetId', 'PurgedFiles', 'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"));
				break;               
            }
   			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'));
   			$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'c.Name'));

			$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'p.Name'));
			$select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', array('FileSet'));


			$select->where( "('" . $date_begin . ' ' . $time_begin . "' <= j.StartTime) AND (j.StartTime <= '" . 
				$date_end . ' ' . $time_end . "')" );

			if (  $fileset != "" )	{
				$select->where("f.FileSet='$fileset'"); // AND
			}
			// Full, Incremental, Differential
			if ( $jlevel != "")	{
				$select->where("j.Level='$jlevel'"); // AND
			}

			if ( $client != "" )	{
				$select->where("c.Name='$client'"); // AND
			}

			// JobStatus
			switch ($jstatus) {
			case "R":
				// Running
   				$select->where("j.JobStatus='$jstatus'");
   				break;
			case "T":
				// Terminated normally
   				$select->where("j.JobStatus='$jstatus'");
   				break;
    		case "t":
				// Terminated in Error
   				$select->where("j.JobStatus  IN ('E', 'e', 'f')");
   				break;
   			case "A":
				// Canceled by the user
   				$select->where("j.JobStatus='$jstatus'");
   				break;
   			case "W":
				// Waiting
   				$select->where("j.JobStatus IN ('F', 'S', 'm', 'M', 's', 'j', 'c', 'd', 't', 'p')");
   				break;
			}

			// Backup, Restore, Verify
			if ( $jtype != "")	{
				$select->where("j.Type='$jtype'"); // AND
			}

			$select->order($order);
   		}

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
    	$this->view->titleLastJobs = $this->view->translate->_("List Jobs by JobId");
    	$this->view->title = $this->view->translate->_("List Jobs by JobId");

    	$jobid = intval(trim( $this->_request->getParam('jobid') ));

   		//echo "<pre>$jobid"; // DEBUG

   		if ( isset($jobid) )	{
   			$this->view->titleLastJobs .= ' : ' . $jobid;

   			$db = Zend_Db_Table::getDefaultAdapter();
   			// make select from multiple tables
   			$select = new Zend_Db_Select($db);

   			$select->distinct();

   			switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
                    'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)'));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('j.JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
					'StartTime', 'EndTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
					'FileSetId', 'PurgedFiles', 'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))" ));
				break;                
            }


   			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'));
   			$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));

			$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
			$select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', array('FileSet'));

			$select->where("j.JobId = '$jobid'");

   			$select->order(array("StartTime", "JobId"));

   		}

   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

   		$stmt = $select->query();
		$this->view->resultLastJobs = $stmt->fetchAll();

		echo $this->renderScript('job/terminated.phtml');
    }


    /**
     * Search Job on attributes : Volume Name
     * See <bacula>/src/dird/query.sql
     *
     */
    function findVolumeNameAction()
    {
    	$this->view->titleLastJobs = $this->view->translate->_("List Jobs by Volume Name");
    	$this->view->title = $this->view->translate->_("List Jobs by Volume Name");

    	$volname = addslashes(trim( $this->_request->getParam('volname') ));

   		//echo "<pre>$volname"; // DEBUG

   		if ( isset($volname) )	{
   			$this->view->titleLastJobs .= ' : ' . $volname;

   			$db = Zend_Db_Table::getDefaultAdapter();
   			// make select from multiple tables
   			$select = new Zend_Db_Select($db);

   			$select->distinct();

   			switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
    			array('j.JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
   				'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
   				'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       			'FileSetId', 'PurgedFiles', 'JobStatus',
       			'DurationTime' => 'TIMEDIFF(EndTime, StartTime)' ));
                break;
            case 'PDO_PGSQL':
            // PostgreSQL
            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
    			array('j.JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				'StartTime', 'EndTime',
   				'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       			'FileSetId', 'PurgedFiles', 'JobStatus',
       			'DurationTime' => '(EndTime - StartTime)' ));
                break;
            }
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');

            $select->joinLeft(array('o' => 'JobMedia'), 'j.JobId = o.JobId', array('JobId'));
            $select->joinLeft(array('m' => 'Media'), 'm.MediaId = o.MediaId', array('MediaId'));

			$select->where("m.VolumeName = '$volname'");

   			$select->order(array("StartTime", "j.JobId"));

   		}

   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

   		$stmt = $select->query();
		$this->view->resultLastJobs = $stmt->fetchAll();

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
    	$clients = new Client();
    	$this->view->clients = $clients->fetchAll();
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
    	$jobid = intval(trim( $this->_request->getParam('jobid') ));

    	if ( $jobid )	{
    		$this->view->titleJob = $this->view->translate->_("Detail JobId") . " " . $jobid;
    		$this->view->jobid = $jobid;

    		$db = Zend_Db_Table::getDefaultAdapter();

    		// make select from multiple tables
    		$select = new Zend_Db_Select($db);

    		$select->distinct();

    		switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Job', 'Name', 'Level', 'ClientId',
                    'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
    			    'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
    			    'SchedTime' => "DATE_FORMAT(j.SchedTime,   '%y-%b-%d %H:%i')",
    			    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        		    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
        		    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'
                ));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Job', 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime', 'SchedTime',
    			    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        		    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
        		    'DurationTime' => '(EndTime - StartTime)'
    			));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('JobId', 'Job', 'Name', 'Level', 'ClientId',
					'StartTime', 'EndTime', 'SchedTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
					'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				break;
            }
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId',
                array('ClientName' => 'Name', 'ClientUName' => 'UName'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');
        	$select->where("j.JobId = $jobid");

    		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$this->view->resultJob = $stmt->fetchAll();

			$select->reset();
			unset($select);
			unset($stmt);

			// list volumes
			$select = new Zend_Db_Select($db);
    		$select->distinct();
    		$select->from(array('j' => 'JobMedia'),	array('MediaId'));
	    	$select->joinInner(array('m' => 'Media'), 'j.MediaId = m.MediaId', array('VolumeName'));

        	$select->where("j.JobId=$jobid");

        	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

        	$stmt = $select->query();
			$this->view->resultVol = $stmt->fetchAll();

			$select->reset();
			unset($select);
			unset($stmt);
    	}
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
    	$ret = $jobs->GetProblemJobs();
    	$this->view->resultProblemJobs = $ret->fetchAll();
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
   		$res = new MyClass_GetDataTimeline;
   		$this->view->atime = $res->GetDataTimeline($datetimeline);
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
            $config = Zend_Registry::get('config');

    	    if ( !file_exists($config->bacula->bconsole))	{
    		  $this->view->result_error = 'NOFOUND_BCONSOLE';
    		  $this->render();
    		  return;
    	    }

    	    $bconsolecmd = '';
            if ( isset($config->bacula->sudo))	{
                // run with sudo
                $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            } else {
                $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            }

            exec($bconsolecmd . " <<EOF
run job=\"$jobname\" yes
.
@sleep 10
status dir
@quit
EOF",
$command_output, $return_var);

            //echo '<pre>command_output:<br>' . var_dump($command_output) . '<br><br>return_var = ' . var_dump($return_var) . '</pre>'; exit;

            $this->view->command_output = $command_output;

            // check return status of the executed command
            if ( $return_var != 0 )	{
                $this->view->result_error = 'ERROR_BCONSOLE';
            }

    	    // показываем вывод Director
    	    echo $this->renderScript('/job/run-job-output.phtml');// _redirect('/job/run-job-output');
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

    	if ( $numjob <= 0 ) {
    	    $numjob = 20;
    	}

    	if ( $numjob > $num_max ) {
    	    $numjob = $num_max;
    	}

   		//echo "<pre>num = $numjob"; exit; // DEBUG !!!

	    $this->view->titleLastJobs = sprintf($this->view->translate->_("List last %s Jobs run"), $numjob);
	    $this->view->title = sprintf($this->view->translate->_("List last %s Jobs run"), $numjob);

		$db = Zend_Db_Table::getDefaultAdapter();
		// make select from multiple tables
		$select = new Zend_Db_Select($db);

		$select->distinct();
		$select->limit($numjob);

		switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'sortStartTime' => 'StartTime',
			        'StartTime', 'EndTime',
			        'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
   			         'FileSetId', 'PurgedFiles', 'JobStatus',
   			         'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'
   		        ));
            break;
        	case 'PDO_PGSQL':
            	// PostgreSQL
	            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
    	        $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'sortStartTime' => 'StartTime',
			        'StartTime', 'EndTime',
			        'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
   			         'FileSetId', 'PurgedFiles', 'JobStatus',
   			         'DurationTime' => '(EndTime - StartTime)'
   		        ));
            break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
					'sortStartTime' => 'StartTime',
					'StartTime', 'EndTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
					'FileSetId', 'PurgedFiles', 'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
			break;
        }
		$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
		$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId',       array('PoolName' => 'Name'));
		$select->joinInner(array('s' => 'Status'), "j.JobStatus = s.JobStatus" , array('JobStatusLong'));

		$select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W')");
		$select->where("j.Type = 'B'");
   		$select->order(array("sortStartTime DESC"));

   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!DEBUG!!!

   		$stmt = $select->query();
		$this->view->resultLastJobs = $stmt->fetchAll();

		echo $this->renderScript('job/terminated.phtml');
    }


    /**
     * List Jobs where a given File is saved
     *
     */
    function findFileNameAction()
    {
        $limit = 30;
    	$this->view->title = "";

    	$namefile = addslashes( $this->_request->getParam('namefile') ); // имя файла м.б. с лидирующими и концевыми пробелами
    	$client   = addslashes(trim( $this->_request->getParam('client_nf') ));

   		//echo "<pre>$namefile"; // DEBUG

   		if ( isset($namefile, $client) )	{
   			$this->view->title = sprintf($this->view->translate->_("List Jobs where %s is saved (limit %s)"), $namefile, $limit);

   			$db = Zend_Db_Table::getDefaultAdapter();
   			// make select from multiple tables
   			$select = new Zend_Db_Select($db);

   			$select->distinct();
   			$select->limit($limit);

   			switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
    			array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
   				'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
   				'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       			'FileSetId', 'PurgedFiles', 'JobStatus',
       			'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
	            // PostgreSQL
    	        // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
    				array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   					'StartTime', 'EndTime',
   					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
       				'FileSetId', 'PurgedFiles', 'JobStatus',
       				'DurationTime' => '(EndTime - StartTime)'));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
					'StartTime', 'EndTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
					'FileSetId', 'PurgedFiles', 'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"));
                 break;
            }

       		$select->joinLeft('File', 'j.JobId = File.JobId', array('File.JobId'));
       		$select->joinLeft('Filename', 'File.FilenameId = Filename.FilenameId', array('FileName' => 'Filename.Name'));
       		$select->joinLeft('Path', 'File.PathId = Path.PathId', array('Path' => 'Path.Path'));

   			$select->joinLeft('Status', 'j.JobStatus = Status.JobStatus', array('JobStatusLong' => 'Status.JobStatusLong'));
   			$select->joinLeft('Client', 'j.ClientId = Client.ClientId',   array('ClientName' => 'Client.Name'));

			$select->joinLeft('Pool',	 'j.PoolId = Pool.PoolId',          array('PoolName' => 'Pool.Name'));
			$select->joinLeft('FileSet', 'j.FileSetId = FileSet.FileSetId', array('FileSet' => 'FileSet.FileSet'));

			if ( $client != "" )	{
				$select->where("Client.Name='$client'"); // AND
			}

			$select->where("Filename.Name = '$namefile'");

   			$select->order(array("StartTime"));

   		}

   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

   		$stmt = $select->query();
		$this->view->result = $stmt->fetchAll();
    }


}
