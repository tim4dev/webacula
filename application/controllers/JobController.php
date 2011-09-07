<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuri Timofeev tim4dev@gmail.com
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
 */


require_once 'Zend/Controller/Action.php';

class JobController extends MyClass_ControllerAclAction
{
    // for Zend Paginator
    const ROW_LIMIT_JOBS = 70;

    function init()
    {
        parent::init();
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
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
        $this->view->title = $this->view->translate->_("Terminated Jobs (executed in last 24 hours)");
        // get data from model
        $jobs = new Job();
        $this->view->result = $jobs->getTerminatedJobs();
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }


    /**
     * Running Jobs
     *
     */
    function runningAction()
    {
        $this->view->titleRunningJobs = $this->view->translate->_("Information from DB Catalog : List of Running Jobs");
        $this->view->title = $this->view->translate->_("List of Running Jobs");
        // get data from model
        $jobs = new Job();
        $this->view->resultRunningJobs = $jobs->getRunningJobs();
        // получаем информацию от Директора
        $this->view->titleDirRunningJobs  = $this->view->translate->_("Information from Director : List of Running Jobs");
        $this->view->resultDirRunningJobs = $jobs->getDirRunningJobs();
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

    /**
     * Running Jobs
     */
    function runningDashboardAction()
    {
    	if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        $this->view->titleRunningJobs = $this->view->translate->_("Information from DB Catalog : List of Running Jobs");
        // get data from model
        $jobs = new Job();
        $this->view->resultRunningJobs = $jobs->getRunningJobs();
        // получаем информацию от Директора
        $this->view->titleDirRunningJobs  = $this->view->translate->_("Information from Director : List of Running Jobs");
        $this->view->resultDirRunningJobs = $jobs->getDirRunningJobs();
        if ( empty($this->view->resultRunningJobs) && empty($this->view->resultDirRunningJobs) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('job_running');
        }
    }


    /**
     * Scheduled Jobs (at 24 hours forward)
     *
     */
    function nextAction()
    {
        $this->view->title = $this->view->translate->_("Scheduled Jobs (at 24 hours forward)");
        // get static const
        $this->view->unknown_volume_capacity = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
        $this->view->new_volume = Zend_Registry::get('NEW_VOLUME');
        $this->view->err_volume = Zend_Registry::get('ERR_VOLUME');
        // get data from model
        $jobs = new Job();
        $this->view->result = $jobs->getScheduledJobs();
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

    /**
     * Scheduled Jobs (at 24 hours forward)
     *
     */
    function nextDashboardAction()
    {
    	if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        $this->view->title = $this->view->translate->_("Scheduled Jobs (at 24 hours forward)");
        // get static const
        $this->view->unknown_volume_capacity = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
        $this->view->new_volume = Zend_Registry::get('NEW_VOLUME');
        $this->view->err_volume = Zend_Registry::get('ERR_VOLUME');
        // get data from model
        $jobs = new Job();
        $this->view->result = $jobs->getScheduledJobs();
        if ( empty($this->view->result) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('job_next');
        }
    }



    /**
     * Search Job on attributes : date begin/end, Client, FileSet, etc
     *
     */
    function findFiltersAction()
    {
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
        $client  = addslashes(trim( $this->_request->getParam('client') ));
        $fileset = addslashes(trim( $this->_request->getParam('fileset') ));
        $jlevel  = addslashes(trim( $this->_request->getParam('jlevel') ));
        $jstatus = addslashes(trim( $this->_request->getParam('jstatus') ));
        $jtype   = addslashes(trim( $this->_request->getParam('jtype') ));
        $volname = addslashes(trim( $this->_request->getParam('volname') ));
        $orderp  = addslashes(trim($this->_request->getParam('order', '')));

        $job = new Job();
        $jobs = $job->getSelectFilteredJob($date_begin, $time_begin, $date_end, $time_end,
            $client, $fileset, $jlevel, $jstatus, $jtype, $volname,
            $orderp);
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

        if ($jobs) {
            $paginator = Zend_Paginator::factory($jobs);
            Zend_Paginator::setDefaultScrollingStyle('Sliding');
            $paginator->setItemCountPerPage(self::ROW_LIMIT_JOBS);
            $paginator->setCurrentPageNumber($this->_getParam('page', 1));
            $this->view->paginator = $paginator;
            $paginator->setView($this->view);
        }
    }



    /**
     * Search Job on attributes : JobId
     *
     */
    function findJobIdAction()
    {
        $this->view->title = $this->view->translate->_("List Jobs by JobId") .
                ' : ' . $this->_request->getParam('jobid');
        $this->view->title = $this->view->translate->_("List Jobs by JobId");
        $jobid = intval(trim( $this->_request->getParam('jobid') ));
        $job = new Job();
        $this->view->result = $job->getByJobId($jobid);
        echo $this->renderScript('job/terminated.phtml');
    }



    /**
     * Search Job on attributes : Job Name
     *
     */
    function findJobNameAction()
    {
        $this->view->title = $this->view->translate->_("List Jobs by Job Name") .
                ' : ' . $this->_request->getParam('jobname');
        $this->view->title = $this->view->translate->_("List Jobs by Job Name");
        $jobname = trim( $this->_request->getParam('jobname') );
        $job = new Job();
        $this->view->result = $job->getByJobName($jobname);
        echo $this->renderScript('job/terminated.phtml');
    }



    /**
     * Search Job on attributes : Volume Name
     * See <bacula>/src/dird/query.sql
     */
    function findVolumeNameAction()
    {
        $this->view->title = $this->view->translate->_("List Jobs by Volume Name") .
            ' : ' . $this->_request->getParam('volname');
        $this->view->title = $this->view->translate->_("List Jobs by Volume Name");
        $volname = addslashes(trim( $this->_request->getParam('volname') ));
        $job = new Job();
        $this->view->result = $job->getByVolumeName($volname);
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
        $order  = array('Name');
        $this->view->clients = $clients->fetchAll(null, $order);
        Zend_Loader::loadClass('FileSet');
        $filesets = new FileSet();
        $order  = array('Fileset');
        $this->view->filesets = $filesets->fetchAll(null, $order);
        // type search
        switch ($this->db_adapter) {
            case 'PDO_SQLITE':
                //regexp not implemented by default
                $this->view->atype_file_search = array(
                    'ordinary' => $this->view->translate->_("Ordinary"),
                    'like'     => $this->view->translate->_("LIKE operator") );
                break;
            default:
                $this->view->atype_file_search = array(
                    'ordinary' => $this->view->translate->_("Ordinary"),
                    'like'     => $this->view->translate->_("LIKE operator"),
                    'regexp'   => $this->view->translate->_("Regular expression") );
                break;
        }
    }

    /**
     * Detail information about Job
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


    public function getLastDays() {
        if ( empty($this->view->config->general->days_to_show_jobs_with_errors) )
            return 7;
        return $this->view->config->general->days_to_show_jobs_with_errors;
    }


    /**
     * Jobs with errors/problems (last 14 days)
     */
    function problemAction()
    {
        $last_days = $this->getLastDays();
        $this->view->title = sprintf( $this->view->translate->_("Jobs with errors (last %s days)"), $last_days);
        // get data from model
        $jobs = new Job();
        $this->view->result = $jobs->getProblemJobs($last_days);
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

    /**
     * Jobs with errors/problems (last 14 days)
     */
    function problemDashboardAction()
    {
    	if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        $last_days = $this->getLastDays();
        $this->view->title = sprintf( $this->view->translate->_("Jobs with errors (last %s days)"), $last_days);
        // get data from model
        $jobs = new Job();
        $this->view->result = $jobs->getProblemJobs($last_days);
        if ( empty($this->view->result) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('job_problem');
        }
    }

    /**
     * Graph timeline for Jobs
     */
    function timelineAction()
    {
        // http://localhost/webacula/job/timeline/
        $datetimeline = addslashes(trim( $this->_request->getParam('datetimeline', date('Y-m-d', time()) ) ));
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
        $this->view->datetimeline = $datetimeline;
        // for image map
        $this->view->img_map = $timeline->createTimelineImage($datetimeline, false, null, 'normal');
    }



    function timelineDashboardAction()
    {
    	if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        if ( !extension_loaded('gd') ) {
            // No GD lib (php-gd) found
            $this->view->result = null;
            $this->_helper->viewRenderer->setNoRender();
            return;
        }
        $datetimeline = date('Y-m-d', time());
        $this->view->title = $this->view->translate->_("Timeline for date") . " " . $datetimeline;
        $timeline = new Timeline;
        $this->view->img_map = $timeline->createTimelineImage($datetimeline, false, null, 'small');
        if ( empty($this->view->img_map) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('job_timeline');
        }
    }



    /**
     * Run Job
     */
    function runJobAction()
    {
        // do Bacula ACLs
        $command = 'run';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        /*
         * Job run form
         */
        Zend_Loader::loadClass('FormJobrun');
        $form = new formJobrun();
        $form->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'decorators/formJobrun.phtml',
                'form'=> $form
            ))
        ));

        $from_form = intval( $this->_request->getParam('from_form', 0) );
        if ($from_form == 1) { //if( $this->_request->isPost() ) {
            if ( $form->isValid($this->_getAllParams()) )  {
                // данные из формы
                $jobname = trim( $this->_request->getParam('jobname') );
                $client  = addslashes(trim( $this->_request->getParam('client', '') ));
                $fileset = addslashes(trim( $this->_request->getParam('fileset', '') ));
                $storage = addslashes(trim( $this->_request->getParam('storage', '') ));
                $level   = addslashes(trim( $this->_request->getParam('level', '') ));
                $spool   = addslashes(trim( $this->_request->getParam('spool', 'yes') ));
                $checkbox_now = addslashes(trim( $this->_request->getParam('checkbox_now') ));
                if ($checkbox_now) {
                    $when = '';
                } else {
                    $date_when  = addslashes( trim( $this->_request->getPost('date_when') ));
                    $time_when  = addslashes( trim( $this->_request->getPost('time_when') ));
                    $when = $date_when . ' ' . $time_when;
                }

                $this->view->jobname = $jobname;
                // run Job
                $director = new Director();
                if ( !$director->isFoundBconsole() )    {
                    $this->view->result_error = 'NOFOUND_BCONSOLE';
                    $this->render();
                    return;
                }
                // make options
                $cmdrun = ' ';
                if ( !empty($client) )  $cmdrun .= 'client="'.$client.'" ';
                if ( !empty($fileset) ) $cmdrun .= 'fileset="'.$fileset.'" ';
                if ( !empty($level) )   $cmdrun .= 'level="'.$level.'" ';
                if ( !empty($storage) ) $cmdrun .= 'storage="'.$storage.'" ';
                if ( !empty($when) )    $cmdrun .= 'when="'.$when.'" ';
                if ( !empty($spool) )   $cmdrun .= 'spooldata="'.$spool.'" ';
                /*
                 * run job=<job-name>
                 *     client=<client-name>
                 *     fileset=<FileSet-name>
                 *     level=<level-keyword Full, Incremental, Differential>
                 *     storage=<storage-name>
                 *     where=<directory-prefix>
                 *     when=<universal-time-specification YYYY-MM-DD HH:MM:SS>
                 *     spooldata=yes|no
                 *     yes
                 */
                $astatusdir = $director->execDirector(
" <<EOF
run job=\"$jobname\" $cmdrun yes
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
        } else {
            $form->init();
        }
        /*
         * fill form
         */
        // if re-run job
        $jobname = trim( $this->_request->getParam('jobname') );
        if ( !empty($jobname) ) {
            // fill form
            $form->populate( array(
                'jobname' => $jobname
            ));
        }

        $this->view->form = $form;
    }


    /**
     * List last NN Jobs run
     * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
     */
    function listLastJobsRunAction()
    {
        $numjob = intval(trim( $this->_request->getParam('numjob', 20) ));
        $num_max = 200;
        if ( $numjob <= 0 ) { $numjob = 20;	}
        if ( $numjob > $num_max ) { $numjob = $num_max;	}

        $this->view->title = sprintf($this->view->translate->_("List last %s Jobs run"), $numjob);
        $job = new Job();
        $this->view->result = $job->getLastJobRun($numjob);
        echo $this->renderScript('job/terminated.phtml');
    }


    /**
     * List last NN Jobs run
     * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
     */
    function terminatedDashboardAction()
    {
    	if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        $this->view->title = $this->view->translate->_("Terminated Jobs (executed in last 24 hours)");
        $job = new Job();
        $this->view->result = $job->getTerminatedJobs();
        if ( empty($this->view->result) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('job_terminated');
        }
    }

    /**
     * List Jobs where a given File is saved
     *
     */
    function findFileNameAction()
    {
        $limit    = 100;
        $path     = rtrim( $this->_request->getParam('path') );
        $namefile = $this->_request->getParam('namefile'); // NO trim!!
        // $namefile required
        if ( empty($namefile) ) {
            $this->view->msg = $this->view->translate->_("Filename is required field.");
            //$this->_forward('find-form', null, null, null);
            return;
        }
        // $path will be with trailing slash
        if ( !empty($path) && ( substr($path, -1) != '/') ) {
            $this->view->msg = $this->view->translate->_("Path must have a trailing slash.");
            //$this->_forward('find-form', null, null, null);
            return;
        }
        $client   = addslashes( trim( $this->_request->getParam('client_nf') ));
        $type_search = addslashes( $this->_request->getParam('type_file_search') );
        $job = new Job();
        $this->view->result = $job->getByFileName($path, $namefile, $client, $limit, $type_search);
        $this->view->title = sprintf($this->view->translate->_("List Jobs (%s found) where '%s' is saved (limit %s)"),
            sizeof($this->view->result), $namefile, $limit);
    }


    /**
     * Cancel Job
     * http://www.bacula.org/3.0.x-manuals/en/console/console/Bacula_Console.html
     * cancel [jobid=<number> job=<job-name> ujobid=<unique-jobid>]
     */
    function cancelJobAction()
    {
        $this->view->title = $this->view->translate->_("Cancel Job");
        $jobid = trim( $this->_request->getParam('jobid') );
        $this->view->jobid = $jobid;
        // run Job
        $director = new Director();
        if ( !$director->isFoundBconsole() )    {
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }
        $astatusdir = $director->execDirector(
" <<EOF
cancel jobid=$jobid
.
@sleep 7
status dir
@quit
EOF"
        );
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] != 0 )   {
            $this->view->result_error = $astatusdir['result_error'];
        }
        // показываем вывод Director
        echo $this->renderScript('job/run-job-output.phtml');
    }


}
