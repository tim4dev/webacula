<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuriy Timofeev tim4dev@gmail.com
 *
 * Webacula is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Webacula is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webacula.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

/*
 * Data storage format in the session / data storage format in the session
 *
 * *** common parameters
 *
 * $this->restoreNamespace->isSessionExist = true|false;
 * $this->restoreNamespace->typeRestore = "restore | restore_recent";
 *    restore        - Restore All (or selected) files for JobId
 *    restore_recent - Restore All (or selected) files from Recent (date before) backup
 *
 * $this->restoreNamespace->JobHash = $jobidhash;
 * $this->restoreNamespace->CurDir = $curdir; // for draw file tree
 * $this->restoreNamespace->ClientNameFrom = client_name_from;
 * $this->restoreNamespace->ClientNameTo   = client_name_to;
 *
 * *** restore by JobId parameters
 *
 * $jobidhash = md5($jobid);
 * $this->restoreNamespace->JobId = $jobid;
 *
 * *** restore recent backup by Client, FileSet, Date before, parameters
 *
 * $jobidhash = md5(jobids...);
 * $this->restoreNamespace->aJobId = array(0 => jobid, 0 => jobid...);
 * $this->restoreNamespace->FileSet
 * $this->restoreNamespace->DateBefore
 * $this->restoreNamespace->ClientIdFrom
 */


require_once 'Zend/Controller/Action.php';

class RestorejobController extends MyClass_ControllerAclAction
{
    // for pager
    const ROW_LIMIT_FILES = 500;
    public $db_adapter;

    // To store data for Restore
    protected $restoreNamespace;
    const RESTORE_NAME_SPACE = 'RestoreSessionNamespace';
    protected $ttl_restore_session = 3900; // time to live session (65 min)

    /*
     * for Restore Form options
     */
    protected $type_restore;
    protected $jobid;
    protected $fileid;  // restore single file
    protected $client_name;
    protected $client_name_to; // restoreclient
    protected $storage;
    protected $pool;
    protected $fileset;
    protected $restore_job_select; // if have multiple Restore Job resources
    // advanced options
    protected $where;
    protected $strip_prefix;
    protected $add_prefix;
    protected $add_suffix;
    protected $regexwhere;



    function init()
    {
        parent::init();
        $this->translate  = Zend_Registry::get('translate');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->_helper->viewRenderer->setNoRender(); // disable autorendering
        // set ttl_restore_session for tpmTable
        $this->setTtlRestoreSession();
        $this->restoreNamespace = new Zend_Session_Namespace(self::RESTORE_NAME_SPACE);
        // load model
        Zend_Loader::loadClass('WbTmpTable');
    }

    /*
     * get ttl_restore_session from ini file and set appropriate value
     */
    function setTtlRestoreSession() {
        // set ttl_restore_session
        if ( empty($this->view->config->general->ttl_restore_session) ) {
            $this->ttl_restore_session = 3900;
        } else {
            $this->ttl_restore_session = intval($this->view->config->general->ttl_restore_session);
        }
    }


    function mySessionStart() {
        $this->setTtlRestoreSession();
        // session begin
        $this->restoreNamespace = new Zend_Session_Namespace(self::RESTORE_NAME_SPACE);
        $this->restoreNamespace->isSessionExist = true;
        $this->restoreNamespace->setExpirationSeconds($this->ttl_restore_session);
    }


    function mySessionStop() {
        // close session / Delete session data
        $this->restoreNamespace->unsetAll();
    }


    /**
     * see also getCmdRestore()
     * Get param from FormRestoreOptions
     */
    function getParamFromForm() {
        $this->type_restore = addslashes( $this->_request->getParam('type_restore', null));
        $this->jobid = addslashes( $this->_request->getParam('jobid', null));
        $this->fileid = addslashes( $this->_request->getParam('fileid', null)); // restore single file
        $this->client_name    = addslashes( $this->_request->getParam('client_name', null));
        $this->client_name_to = addslashes( $this->_request->getParam('client_name_to', null)); // restoreclient
        $this->storage = addslashes( $this->_request->getParam('storage', null));
        $this->pool    = addslashes( $this->_request->getParam('pool', null));
        $this->fileset = addslashes( $this->_request->getParam('fileset', null));

        // if have multiple Restore Job resources
        /* The defined Restore Job resources are:
             1: restore.files
             2: restore.files.2
          Select Restore Job (1-2): */
        $this->restore_job_select = $this->_request->getParam('restore_job_select', null);
        // advanced options
        $this->where   = addslashes( $this->_request->getParam('where', null));
        $this->strip_prefix = addslashes( $this->_request->getParam('strip_prefix', null));
        $this->add_prefix   = addslashes( $this->_request->getParam('add_prefix', null));
        $this->add_suffix   = addslashes( $this->_request->getParam('add_suffix', null));
        $this->regexwhere   = addslashes( $this->_request->getParam('regexwhere', null));
    }


    /**
     * see also getParamFromForm()
     */
    function getCmdRestore()   {
        $cmd = '';
        if ( !empty($this->jobid) )  $cmd .= ' jobid=' . $this->jobid;
        if ( !empty($this->client_name) )  $cmd .= ' client="' . $this->client_name . '"';
        if ( !empty($this->client_name_to) )  $cmd .= ' restoreclient="' . $this->client_name_to . '"';
        if ( !empty($this->storage) ) $cmd .= ' storage="' . $this->storage . '"';
        if ( !empty($this->pool) ) $cmd .= ' pool="' . $this->pool . '"';
        if ( !empty($this->fileset) ) $cmd .= ' fileset="' . $this->fileset . '"';
        // advanced options
        if ( !empty($this->where) )        $cmd .= ' where="' . $this->where . '"';
        if ( !empty($this->strip_prefix) ) $cmd .= ' strip_prefix="' . $this->strip_prefix . '"';
        if ( !empty($this->add_prefix) )   $cmd .= ' add_prefix="' . $this->add_prefix . '"';
        if ( !empty($this->add_suffix) )   $cmd .= ' add_suffix="' . $this->add_suffix . '"';
        if ( !empty($this->regexwhere) )   $cmd .= ' regexwhere="' . $this->regexwhere . '"';
        return $cmd;
    }



    function routeDrawTreeToRestore()   {
        if ($this->restoreNamespace->typeRestore == 'restore_recent')
            $this->view->action_form = '/restorejob/list-recent-restore';
        else
            $this->view->action_form = '/restorejob/list-restore';
    }


    function cloneBaculaTables($jobidhash)
    {
        /* Retrieve jobid data from session */
        $jobid = $this->restoreNamespace->JobId;
        $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
        $tmp_tables->cloneBaculaToTmp($jobid);
    }


    /**
     * Clone Bacula tables : File, Filename, Path to webacula DB
     * for Restore Recent Backup
     *
     * @return TRUE if ok
     */
    function cloneRecentBaculaTables($jobidhash)
    {
        /* Retrieve data about all jobids from the session */
        $sjobids = implode(",", $this->restoreNamespace->aJobId);
        $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
        $tmp_tables->cloneRecentBaculaToTmp($sjobids);
    }

    /**
     * Delete temporary table after starting the job to restore
     * (Delete temporary tables after running a restore job)
     */
    public function deleteTmpTables()
    {
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        $tmp_tables->deleteAllTmpTables();
    }






    /*****************************************************************************************************
     * Actions
     *****************************************************************************************************/


    public function mainFormAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        $this->view->unit_test = $this->_request->getParam('test', null); // for tests
        // get data for form
        Zend_Loader::loadClass('Client');
        $clients = new Client();
        $order  = array('Name');
        $this->view->clients = $clients->fetchAll(null, $order); // do Bacula ACLs
        Zend_Loader::loadClass('FileSet');
        $filesets = new FileSet();
        $order    = array('Fileset');
        $this->view->filesets = $filesets->fetchAll(null, $order); // do Bacula ACLs

        $this->view->title = $this->view->translate->_("Restore Job");
        $this->view->jobid = intval( $this->_request->getParam('jobid', null) );
        /*
         * Redirects from other forms (RestoreAll, etc.)
         */
        // Issuing a message that such a joid does not exist
        $this->view->msgNoJobId1 = $this->_request->getParam('msgNoJobId1', null);
        $this->view->msgNoJobId2 = $this->_request->getParam('msgNoJobId2', null);
        //What is the bookmark? Active, def = 0 (first child)
        $this->view->accordion_active = $this->_request->getParam('accordion_active', 0);
        // do view
        $this->render();
    }


    
    /**
     * Manager of action depending on the user's choice
     * Action Manager based on user choice
     *
     */
    public function restoreChoiceAction()
    {
        // user made a choice in the form of "Restore Job" restorejob/main-form
        // A choice is made in the form "Restore Job" restorejob/main-form
        $choice  = addslashes( $this->_request->getParam('choice', '') );
        $jobid   = intval( $this->_request->getParam('jobid', null) );
        // store the data in the session / Remember the data in the session
        $this->mySessionStart();
        $this->restoreNamespace->typeRestore = 'restore';
        $this->restoreNamespace->JobId = $jobid;
        $this->restoreNamespace->JobHash = md5($jobid);
        switch ( $choice )  {
        case 'restore_all': // Restore All
            $this->_forward('restore-all', null, null, null);
            break;
        case 'restore_select': // Select Files to Restore
            $this->_forward('select-files', null, null, null);
            break;
        }
    }

    /**
     * Manager of action depending on the user's choice
     * Action Manager based on user choice
     *
     */
    function restoreRecentChoiceAction()
    {
        // в форме "Restore Job" The choice is made
        $choice_recent    = addslashes( $this->_request->getParam('choice_recent', '') );
        // store the data in the session / Remember the data in the session
        $this->mySessionStart();
        $this->restoreNamespace->typeRestore = 'restore_recent';
        $this->restoreNamespace->ClientNameFrom = addslashes( $this->_request->getParam('client_name_from', null) );
        $this->restoreNamespace->FileSet        = addslashes( $this->_request->getParam('fileset', null) );
        $this->restoreNamespace->DateBefore     = addslashes( trim(
            trim( $this->_request->getParam('date_before', null) ) . ' ' . trim( $this->_request->getParam('time_before', null) )
            ) );
        switch ( $choice_recent ) {
            case 'restore_recent_all': // Restore All
                $this->_forward('restore-recent-all', null, null, null);
            break;
            case 'restore_recent_select': // Select Files to Restore
                $this->_forward('select-backups-before-date', null, null, null);
            break;
        }
    }

    /**
     * 12: Select full restore to a specified JobId + Bacula ACLs
     * Manager of action depending on the user's choice
     * Action Manager based on user choice
     *
     */
    public function restoreFullJobidChoiceAction()
    {
        // user made a choice in the form of "Restore Job" restorejob/main-form
        // A choice is made in the form "Restore Job" restorejob/main-form
        $choice  = addslashes( $this->_request->getParam('choice_full_jobid', '') );
        $jobid   = intval( $this->_request->getParam('jobid', null) );
        // get job record
        Zend_Loader::loadClass('Job');
        $job = new Job();
        // Does such jobid exist
        if ( !$job->isJobIdExists($jobid) ) {  // do Bacula ACLs
            $this->_forward('main-form', 'restorejob', null,
                    array(
                        'jobid' => $jobid,
                        'msgNoJobId2' => sprintf($this->view->translate->_("JobId %u does not exist."), $jobid),
                        'accordion_active' => 1
                    )); // action, controller, null, parameters
            return;
        }
            
        $ajob = $job->getByJobId($jobid); // see also cats/sql_get.c : db_accurate_get_jobids()
        $job_row = $ajob[0];
        $this->mySessionStart();
        // store the data in the session / Remember the data in the session
        $this->restoreNamespace->typeRestore = 'restore_recent';
        $this->restoreNamespace->ClientNameFrom = $job_row['clientname'];
        $this->restoreNamespace->FileSet        = $job_row['fileset'];
        $this->restoreNamespace->DateBefore     = $job_row['starttimeraw'];
        switch ( $choice )  {
            case 'restore_select_full_jobid':
                $this->_forward('select-backups-before-date', null, null, null);
                break;
        }       
    }


    /**
     * Restore All + Bacula ACLs
     *
     * see
     * The Restore Command: http://www.bacula.org/rel-manual/Restore_Command.html
     * Running the Console from a Shell Script: http://www.bacula.org/rel-manual/Bacula_Console.html#SECTION002180000000000000000
     * Bacula Console: http://www.bacula.org/rel-manual/Bacula_Console.html
     *
     */
    function restoreAllAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }

        Zend_Loader::loadClass('Job');
        $job = new Job();
        Zend_Loader::loadClass('Client');
        $client = new Client();
        $jobid  = intval( $this->_request->getParam('jobid', 0) );
        /* Remember the "left" jobid in the session so that there are no errors when the restore completes */
        $jobidhash = md5('fake_jobid');
        $this->restoreNamespace->aJobId = array(0 => $jobidhash, 1 => $jobid);

        $this->view->title = $this->view->translate->_("Restore All files for JobId");
        $this->view->jobid = $jobid;

        // Does such jobid exist
        if ( !$job->isJobIdExists($jobid) ) {  // do Bacula ACLs
            $this->_forward('main-form', 'restorejob', null,
                    array(
                        'jobid' => $jobid,
                        'msgNoJobId1' => sprintf($this->view->translate->_("JobId %u does not exist."), $jobid)
                    )); // action, controller, null, parameters
            return;
        }
        /*
         * Form Restore options
         */
        Zend_Loader::loadClass('FormRestoreOptions');
        $form = new formRestoreOptions();
        // validator "Where"
        Zend_Loader::loadClass('MyClass_Validate_BaculaAclWhere');
        $validator_where = new MyClass_Validate_BaculaAclWhere();
        /*
         * run restore
         */
        if ( $this->_request->isPost() && $this->_request->getParam('from_form') )
        {
            // Get values from Form Restore Options
            $this->getParamFromForm();
            // Override some variables
            $this->client_name    = $client->getClientName($jobid);
//            $this->client_name_to = $this->restoreNamespace->ClientNameTo;
            $this->client_name_to = $this->_request->getParam('client_name_to');
            $this->type_restore   = $this->restoreNamespace->typeRestore;
            // validator "Where"
            if ( $validator_where->isValid( $this->where ) ) {
                // check access to bconsole
                Zend_Loader::loadClass('Director');
                $director = new Director();
                if ( !$director->isFoundBconsole() )	{
                    $this->view->result_error = 'NOFOUND_BCONSOLE';
                    $this->render();
                    return;
                }
                $cmd_mount = '';
                $cmd_sleep = '';
                if ( (!empty($this->storage)) )    {
                    $cmd_mount = 'mount "' . $this->storage . '"';
                    $cmd_sleep = '@sleep 10';
                }
                //******************************* run job ***************************************
                // create command / Form the command line
                // restore client=Rufus select current all done yes
                $cmd  = 'restore '. $this->getCmdRestore() .' all done yes';

                $comment = __METHOD__;
                $astatusdir = $director->execDirector(
" <<EOF
@#
@# $comment
@#
$cmd_mount
$cmd_sleep
$cmd
$this->restore_job_select
@quit
EOF");

                $this->view->command_output = $astatusdir['command_output'];
                // check return status of the executed command
                if ( $astatusdir['return_var'] != 0 )
                    $this->view->result_error = $astatusdir['result_error'];
                $this->renderScript('restorejob/run-restore.phtml');
                return;
            } else {
                // Form failed validation
                $messages = $validator_where->getMessages();
                $this->view->msgNoValid = $messages[0];
            }
        }
        /*
         * Form Restore options
         */
        $form->setDecorators(array(
        array('ViewScript', array(
            'viewScript' => 'decorators/formRestoreoptions.phtml',
            'form'=> $form
        ))  ));
        $form->init();
        $form->setAction( $this->view->baseUrl .'/restorejob/restore-all' )
             ->setActionCancel( $this->view->baseUrl .'/restorejob/cancel-restore' );
        // fill form
        $form->populate( array(
            'jobid'          => $this->view->jobid,
            'client_name'    => $this->client_name,
            'type_restore'   => $this->type_restore ));
        $this->view->form = $form;
        $this->render();
    }


    /**
     * Restore all files in the most recent backup for a client + Bacula ACLs
     */
    function restoreRecentAllAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        Zend_Loader::loadClass('Director');
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        /* Remember the "left" jobid in the session so that there are no errors when the restore completes */
        $this->restoreNamespace->JobHash = md5('fake_jobid');
        $this->view->title = $this->view->translate->_("Restore All files");
        
        //$this->client_name = addslashes($this->_request->getParam('client_name', $this->restoreNamespace->ClientNameFrom ));
        //$this->fileset     = addslashes($this->_request->getParam('fileset', $this->restoreNamespace->FileSet));
        //$this->restoreNamespace->ClientNameTo = $this->client_name_to;
        
        $this->view->client_from_restore = addslashes($this->_request->getParam('client_name', $this->restoreNamespace->ClientNameFrom ));
        $this->view->fileset_restore = addslashes($this->_request->getParam('fileset', $this->restoreNamespace->FileSet ));
        
        
        
        // bconsole available ?
        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }       
        /*
         * Form Restore options
         */
        Zend_Loader::loadClass('FormRestoreOptions');
        $form = new formRestoreOptions();
        // validator "Where"
        Zend_Loader::loadClass('MyClass_Validate_BaculaAclWhere');
        $validator_where = new MyClass_Validate_BaculaAclWhere();
        /*
         * run restore
         */
        if ( $this->_request->isPost() && $this->_request->getParam('from_form') )
        {
            // Get values from Form Restore Options
            $this->getParamFromForm();
            // Redefinition of some variables
            $this->client_name = addslashes($this->_request->getParam('client_name', $this->restoreNamespace->ClientNameFrom ));
            $this->fileset     = addslashes($this->_request->getParam('fileset', $this->restoreNamespace->FileSet));
            $this->restoreNamespace->ClientNameTo = $this->client_name_to;
            
            $this->view->client_from_restore = $this->restoreNamespace->ClientNameFrom;
            
            // validator "Where"
            if ( $validator_where->isValid( $this->where ) ) {
                if ( empty($this->restoreNamespace->DateBefore) )
                    $cmd_date_before = ' current ';
                else
                    $cmd_date_before = ' before="'. $this->restoreNamespace->DateBefore . '" ';
                //******************************* run job ***************************************
                // create command / Form the command line
                // restore client="local.fd" restoreclient="local.fd" fileset="test1"  where="/home/test/11111" current select all done yes
                // restore client="local.fd" fileset="test1" before="2009-05-11 11:36:56" select all done yes
                // restore client="local.fd" restoreclient="srv1.fd" fileset="test1" before="2009-05-11 11:36:56" select all done yes
                $cmd = 'restore '. $this->getCmdRestore() .' '. $cmd_date_before .' select all done yes';

                $comment = __METHOD__;
                $astatusdir = $director->execDirector(
" <<EOF
@#
@# $comment
@#
$cmd
$this->restore_job_select
@quit
EOF"
                );
                $this->view->command_output = $astatusdir['command_output'];
                // check return status of the executed command
                if ( $astatusdir['return_var'] != 0 )
                    $this->view->result_error = $astatusdir['result_error'];
                $this->renderScript('restorejob/run-restore.phtml');
                return;
            } else {
                // Form failed validation
                $messages = $validator_where->getMessages();
                $this->view->msgNoValid = $messages[0];
            }
        }
        /*
         * Form Restore options
         */
        $form->setDecorators(array(
            array('ViewScript', array(
                  'viewScript' => 'decorators/formRestoreoptions.phtml',
                  'form'=> $form
            ))
        ));
        $form->init();
        $form->setAction( $this->view->baseUrl .'/restorejob/restore-recent-all' );
        $form->setActionCancel( $this->view->baseUrl .'/restorejob/cancel-restore-recent' );
        // fill form
        $form->populate( array(
            'client_name'    => $this->restoreNamespace->ClientNameFrom,
            'client_name_to' => $this->restoreNamespace->ClientNameTo,
            'fileset'        => $this->restoreNamespace->FileSet,
            'type_restore'   => $this->restoreNamespace->typeRestore,
        ));
        $this->view->form = $form;
        $this->render();
    }



    function selectFilesAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        Zend_Loader::loadClass('Job');
        $job = new Job();

        // routing
        $this->routeDrawTreeToRestore();
        // The beginning of the directory tree?
        $beginr = intval( $this->_request->getParam('beginr', 0) );
        if ( $beginr == 1 ) {
            /* Beginning of the directory tree */
            // Does such jobid exist
            if ( !$job->isJobIdExists($this->restoreNamespace->JobId) )  {  // do Bacula ACLs
                $this->_forward('main-form', 'restorejob', null,
                    array(
                        'jobid' => $this->restoreNamespace->JobId,
                        'msgNoJobId1' => sprintf($this->view->translate->_("JobId %u does not exist."), $this->restoreNamespace->JobId)
                    )); // action, controller, null, parameters
                return;
            }

            Zend_Loader::loadClass('Client');
            $client = new Client();
            $this->restoreNamespace->ClientNameFrom = $client->getClientName($this->restoreNamespace->JobId);

            // Tmp tables exist ?
            $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
            if ( $tmp_tables->isAllTmpTablesExists() )	{
                // Tmp tables are outdated ?
                if ( $tmp_tables->isOldTmpTables() )    {
                    // Tmp-tables are outdated
                    $tmp_tables->dropOldTmpTables();  // delete all old tmp tables
                    // create tmp tables
                    $this->cloneBaculaTables($this->restoreNamespace->JobHash);
                    // Drawing a tree
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
                    $curdir  = addslashes( $this->_request->getParam('curdir', '') );
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
                }   else {
                    // Tmp tables are not outdated
                    // Issue a message: 1. re-create temporary tables 2. work with old ones 3. exit
                    $this->view->jobid = $this->restoreNamespace->JobId;
                    $this->view->title = $this->view->translate->_('Restore Job');
                    echo $this->renderScript('restorejob/msg01.phtml');
                    return;
                }
            } else {
                $this->cloneBaculaTables( $this->restoreNamespace->JobHash ); // create tmp tables
                $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
            }
        } else {
            // Continue to show the directory tree
            $curdir  = addslashes( $this->_request->getParam('curdir', '') );
            $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir));
        }
    }


    /**
     *  with Bacula ACLs
     */
    function selectBackupsBeforeDateAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Job');
        // Search ClientId
        $client = new Client();
        $this->restoreNamespace->ClientIdFrom = $client->getClientId($this->restoreNamespace->ClientNameFrom);

        if ( !empty($this->restoreNamespace->DateBefore) ) {
            $date_before = " AND Job.StartTime<='".$this->restoreNamespace->DateBefore."'";
        } else {
            $date_before = '';
        }

        $job = new Job();
        $ajobs = $job->getJobBeforeDate(
                $date_before,
                $this->restoreNamespace->ClientIdFrom,
                $this->restoreNamespace->FileSet);  // with Bacula ACLs
        if ( !$ajobs ) {
            // Message that is not found Full backup: No Full backup before 2009-05-20 15:19:49 found.
            $this->view->title = $this->view->translate->_("Error");
            $this->view->msg = sprintf($this->view->translate->_("No Full backup before %s found."), $this->restoreNamespace->DateBefore);
            echo $this->renderScript('msg-note.phtml');
            return;
        }

        /* Remember the jobids data in the session */
        $this->restoreNamespace->JobHash = md5($ajobs['hash']);
        $this->restoreNamespace->aJobId  = $ajobs['ajob_all'];

        $this->view->ajob_full = $ajobs['ajob_full'];
        $this->view->ajob_diff = $ajobs['ajob_diff'];
        $this->view->ajob_inc  = $ajobs['ajob_inc'];
        $this->view->ajob_all  = $ajobs['ajob_all'];
        $this->view->title = $this->view->translate->_("You have selected the following JobIds");
        $this->view->datetime_format = Zend_Registry::get('datetime_format');
        $this->view->beginrecent = 1;
        $this->render();
    }


    function selectRecentFilesAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        // routing
        $this->routeDrawTreeToRestore();
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        // The beginning of the directory tree?
        $beginrecent = intval( $this->_request->getParam('beginrecent', 0) );
        if ( $beginrecent == 1 ) {
            /* The beginning of the directory tree. */
            // Data in the session are already stored in the selectBackupsBeforeDateAction()
            // Tmp tables exist?
            $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
            if ( $tmp_tables->isAllTmpTablesExists() ) {
                // Tmp tables are outdated?
                if ( $tmp_tables->isOldTmpTables() )    {
                    // Tmp-tables are outdated
                    $tmp_tables->dropOldTmpTables();  // delete all old tmp tables
                    // create tmp tables
                    $this->cloneRecentBaculaTables($this->restoreNamespace->JobHash);
                    // Drawing a tree
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
                    $curdir  = addslashes( $this->_request->getParam('curdir', '') );
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
                } else {
                    // Tmp tables are not outdated
                    // Issue a message: 1. re-create temporary tables 2. work with old ones 3. exit
                    $this->view->jobid = '';
                    $this->view->title = $this->view->translate->_('Restore Job');
                    echo $this->renderScript('restorejob/msg01.phtml');
                    return;
                }
            } else {
                $this->cloneRecentBaculaTables($this->restoreNamespace->JobHash); // create tmp tables
                $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
            }
        } else {
            // Continue to show the directory tree
            $curdir  = addslashes( $this->_request->getParam('curdir', '') );
            $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
        }
    }


    /**
     * The main function of rendering of a directory tree
     * The main function for drawing a directory tree
     *
     * @param string jobidhash
     * @param string curdir     If this is the beginning of the drawing, то $curdir = ''
     *
     */
    function drawFileTreeAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        $curdir  = stripslashes( $this->_request->getParam('curdir', '') );
        // add slash
        if ( $curdir )
            if ( substr($curdir, -1) != '/' )
                $curdir .= '/';
        $this->view->title = $this->view->translate->_("Restore Job");

        $adir = array();
        if ( $this->restoreNamespace->JobHash )    {
            $this->routeDrawTreeToRestore();
            //************ get a list of all directories + LStat (Get a list of all directories + their attributes LStat) ******
            $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
            $db = $tmp_tables->getDb();
            // $this->_db->quote();
            $stmt = $db->query("
                SELECT p.Path, t.isMarked, f.FileId, f.PathId, f.LStat, f.MD5
                FROM Path AS p
                INNER JOIN File AS f
                    ON f.PathId = p.PathId
                INNER JOIN " . $tmp_tables->getTableNameFile() . " AS t
                    ON t.FileId = f.FileId
                ORDER BY p.Path
            ");
            // get a list of directories on the current (Get a list of directories relative to the current)
            while($line = $stmt->fetch())   {
                if ( empty($curdir) ) {
                    $pos = 0;
                    if ( $line['path'][0] == '/') $curdir = '/'; // unix path
                    //elseif ( $line['path'][1] === ':') $curdir = $line['path'][0] . ':/'; // windows path
                } else
                    $pos = strpos($line['path'], $curdir);
                // The current directory is found
                if ( $pos === 0 )   {
                    // Delete the current directory from the full path
                    $nextdir = preg_replace('/^' . addcslashes($curdir, '/') . '/', '', $line['path']);
                    // If there are more subdirectories
                    if ( !empty($nextdir) ) {
                        // Get the next level of the subdirectory
                        $atmp = explode("/", $nextdir, 3);
                        $dir = $atmp[0];
                        if ( !empty($dir) ) {
                            if ($line['md5'] === 0)  {
                                // This is definitely a catalog
                                if ( isset($atmp[2]) )    {
                                    $adir[$dir]['lstat'] = '';  // There is no LStat data, it's just part of the way
                                } else {
                                    $adir[$dir]['lstat'] = $line['lstat']; // Exact match, characters. There is data about LStat
                                }
                                $adir[$dir]['pathid']   = $line['pathid'];
                                $adir[$dir]['dir']      = $dir;
                                $adir[$dir]['ismarked'] = $line['ismarked'];
                            } else {
                                // Perhaps this is the catalog
                                if ( empty($adir[$dir]) ) {
                                    $adir[$dir]['lstat'] = '';  // The LStat data will be from a file that is not needed
                                    $adir[$dir]['pathid']   = $line['pathid'];
                                    $adir[$dir]['dir']      = $dir;
                                    $adir[$dir]['ismarked'] = $line['ismarked'];
                                }
                            }
                        }
                    }
                }
            }
            unset($stmt);
            unset($db);
            //****** Get the list of files in the current directory ******
            $afile = array();
            if ( $curdir )	{
                $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
                $db = $tmp_tables->getDb();
                $select = $db->select();
                switch ($this->db_adapter) {
                    case 'PDO_SQLITE':
                        $sql = 'SELECT DISTINCT f.FileId as fileid, f.LStat as lstat, f.PathId as pathid, t.isMarked as ismarked, n.Name as name, p.Path as path
                            FROM ' . $tmp_tables->getTableNameFile() .' AS t,
                            Filename AS n, Path AS p, File AS f
                            WHERE (t.FileId = f.FileId) AND
                            (f.FileNameId = n.FileNameId) AND (f.PathId = p.PathId) AND
                            (p.Path = '. $db->quote($curdir) .')'."AND (n.Name != '')".
                            ' ORDER BY Name ASC';
                        break;
                    default: // include mysql, postgresql
                        $sql = 'SELECT DISTINCT f.FileId, f.LStat, f.PathId, t.isMarked, n.Name, p.Path
                            FROM ' . $tmp_tables->getTableNameFile() .' AS t,
                            Filename AS n, Path AS p, File AS f
                            WHERE (t.FileId = f.FileId) AND
                            (f.FileNameId = n.FileNameId) AND (f.PathId = p.PathId) AND
                            (p.Path = '. $db->quote($curdir) .')'."AND (n.Name != '')".
                            ' ORDER BY Name ASC';
                        break;
                }
                $stmt = $db->query($sql);
                while($line = $stmt->fetch())   {
                    $file = $line['name'];
                    $afile[$file]['fileid']   = $line['fileid'];
                    $afile[$file]['pathid']   = $line['pathid'];
                    $afile[$file]['lstat']    = $line['lstat'];
                    $afile[$file]['ismarked'] = $line['ismarked'];
                }
                unset($stmt);
            }
            $this->view->adir   = $adir;
            $this->view->afile  = $afile;
            $this->view->curdir = $curdir;
            $this->view->jobidhash = $this->restoreNamespace->JobHash;
            // We obtain the summary statistics
            $atotal = $tmp_tables->getTotalSummaryMark();
            $this->view->total_size  = $atotal['total_size'];
            $this->view->total_files = $atotal['total_files'];
            $this->view->type_restore = $this->restoreNamespace->typeRestore;
            $this->render();
        }
        else {
            $this->view->result = null;
        }
    }




    /**
     * Mark file for restore
     * Mark recovery file
     * See also javascript in draw-file-tree.pthml
     *
     * @return json
     */
    function markFileAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getParam('data', '');
        if ( $encodedValue ) {
            // Getting the value
            $phpNative = Zend_Json::decode($encodedValue);
            $fileid = $phpNative['fileid'];
            $jobidhash = $phpNative['jobidhash'];
            // We make actions in a DB
            $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
            $tmp_tables->markFile($fileid);
            $filename = $tmp_tables->getFileName($fileid);
            // We obtain the summary statistics
            $atotal = $tmp_tables->getTotalSummaryMark();
            // Form an array to send back
            $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
            $aout['total_files'] = $atotal['total_files'];
            $aout['filename']    = $filename;
            $aout['allok']    	 = 1; // Actions are successful
            // Transformation for returning to the client
            $json = Zend_Json::encode($aout);
            // Return the data to javascript
            echo $json;
        } else {
            $aout['allok'] = 0;
            $json = Zend_Json::encode($aout);
            echo $json;
        }
    }


    /**
     * Remove the mark with a file for restore
     * Unmark a file to restore
     * See also javascript in draw-file-tree.pthml
     *
     * @return json
     */
    function unmarkFileAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
        $encodedValue = $this->_request->getParam('data', '');
        if ( $encodedValue ) {
            // Getting the value
            $phpNative = Zend_Json::decode($encodedValue);
            $fileid = $phpNative['fileid'];
            $jobidhash = $phpNative['jobidhash'];
            // We make actions in a DB
            $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
            $tmp_tables->unmarkFile($fileid);
            $filename = $tmp_tables->getFileName($fileid);
            // We obtain the summary statistics
            $atotal = $tmp_tables->getTotalSummaryMark();
            // Form an array to send back
            $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
            $aout['total_files'] = $atotal['total_files'];
            $aout['filename']    = $filename;
            $aout['allok']    	 = 1; // Actions are successful
            // Transformation for returning to the client
            $json = Zend_Json::encode($aout);
            // Return the data to javascript
            echo $json;
        }  else {
            $aout['allok']    = 0;
            $json = Zend_Json::encode($aout);
            echo $json;
        }
    }


    /**
     * Mark the directory + files in the directory + subdirectories + files in them for recovery.
     * See javascript in draw-file-tree.pthml
     */
    function markDirAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getParam('data', '');
        if ( $encodedValue ) {
            // Getting the value
            $phpNative = Zend_Json::decode($encodedValue);
            $path  = $phpNative['path'];
            $jobidhash = $phpNative['jobidhash'];
            // We make actions in a DB
            $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
            $res = $tmp_tables->markDir($path, 1); // isMarked = 1
            if ( $res )
                $aout['msg'] = sprintf($this->view->translate->_("%s<br/>(%s dirs and files affected)"), $res['path'], $res['files']);
            else
                $aout['msg'] =  $this->view->translate->_('internal program error !');
            // We obtain the summary statistics
            $atotal = $tmp_tables->getTotalSummaryMark();
            // Form an array to send back
            $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
            $aout['total_files'] = $atotal['total_files'];
            $aout['path']        = $path;
            $aout['allok']       = 1; // Actions are successful
            // Transformation for returning to the client
            $json = Zend_Json::encode($aout);
            // Return the data to javascript
            echo $json;
        } else {
            $aout['allok'] = 0;
            $json = Zend_Json::encode($aout);
            echo $json;
        }
    }


    /**
     * Remove the marker catalog files in the directory and subdirectories + + files in order to restore them.
     * Remove the markup of the directory and files in the directory + subdirectories + files in them for recovery.
     * See javascript in draw-file-tree.pthml
     */
    function unmarkDirAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getParam('data', '');
        if ( $encodedValue ) {
            // Getting the value
            $phpNative = Zend_Json::decode($encodedValue);
            $path  = $phpNative['path'];
            $jobidhash = $phpNative['jobidhash'];
            // We make actions in a DB
            $tmp_tables = new WbTmpTable($jobidhash, $this->ttl_restore_session);
            $res = $tmp_tables->markDir($path, 0); // isMarked = 0
            if ( $res )
                $aout['msg'] = sprintf($this->view->translate->_("%s<br/>(%s dirs and files affected)"), $res['path'], $res['files']);
           else
                $aout['msg'] =  $this->view->translate->_('internal program error !');
            // We obtain the summary statistics
            $atotal = $tmp_tables->getTotalSummaryMark();
            // Form an array to send back
            $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
            $aout['total_files'] = $atotal['total_files'];
            $aout['path']        = $path;
            $aout['allok']       = 1; // Actions are successful
            // Transformation for returning to the client
            $json = Zend_Json::encode($aout);
            // Return the data to javascript
            echo $json;
        } else {
            $aout['allok']    = 0;
            $json = Zend_Json::encode($aout);
            echo $json;
        }
    }





    function oldTmpTableAction ()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        $this->_helper->viewRenderer->setNoRender();
        // routing
        $this->routeDrawTreeToRestore();
        // In the form "Msg01" a choice is made what to do with old tmp-tables
        $choice  = addslashes( $this->_request->getParam('choice', '') );
        if ($this->restoreNamespace->typeRestore)	{
            switch ( $choice )
            {
                case 'recreate_tmp': // Choice: re-create temporary tables
                    $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
                    $tmp_tables->createTmpTable();
                    // Depending on type Restore
                    if ( $this->restoreNamespace->typeRestore == 'restore_recent' ) {
                        $this->cloneRecentBaculaTables($this->restoreNamespace->JobHash);
                    } else {
                        $this->cloneBaculaTables($this->restoreNamespace->JobHash);
                    }
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>''));
                    return;
                    break;
                case 'continue_tmp': // Work with the old
                    // update timestamp
                    $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
                    $tmp_tables->updateTimestamp();
                    // continue
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>''));
                    return;
                    break;
                case 'goto_homepage': // to Home Page

                    $this->_redirect('index');
                    return;
                    break;
            }
        }
    }



    /**
     * Restore action
     *
     * Shows:
     *  - form to specify the options for Job Restore
     *  - plain-list of files before starting the Restore Job
     *
     * We show:
     *  - Form for indicating options for recovery
     *  - Plain-list of files before running a restore job
     */
    function listRestoreAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        $page  = intval( $this->_request->getParam('page', 1) );
        $page  = ($page > 0) ? $page : 1;
        $this->view->title = $this->view->translate->_("List of Files to Restore for JobId")." ".$this->restoreNamespace->JobId;

        if ( !$this->restoreNamespace->JobHash )
            $this->view->result = null;
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        // get total info
        $atotal = $tmp_tables->getTotalSummaryMark();
        $this->view->total_size = $atotal['total_size'];

        // *** pager ***
  	    // calculate total rows and pages
        $this->view->total_rows = $atotal['total_files'];
        $this->view->total_pages = ceil( $this->view->total_rows / self::ROW_LIMIT_FILES );
        $this->view->current_page = $page;
        // *** end pager ***

        $offset = self::ROW_LIMIT_FILES * ($page - 1);
        $this->view->result = $tmp_tables->getListToRestore($offset);
        /*
         * Form Restore options
         */
        Zend_Loader::loadClass('FormRestoreOptions');
        $form = new formRestoreOptions();
        // validator "Where"
        Zend_Loader::loadClass('MyClass_Validate_BaculaAclWhere');
        $validator_where = new MyClass_Validate_BaculaAclWhere();
        if ( $this->_request->isPost() && $this->_request->getParam('from_form') ) {
            // Get values from Form Restore Options
            $this->getParamFromForm();
            if ( $validator_where->isValid( $this->where ) ) {
                $this->_forward( 'run-restore', 'restorejob', null, $this->_request->getParams() );
                return;
            } else {
                // Form failed validation
                $messages = $validator_where->getMessages();
                $this->view->msgNoValid = $messages[0];
            }
        }
        /*
         * Form Restore options
         */
        $form->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'decorators/formRestoreoptions.phtml',
                'form'=> $form
            ))
        ));
        $form->init();
        $form->setAction( $this->view->baseUrl .'/restorejob/list-restore' );
        $form->setActionCancel( $this->view->baseUrl .'/restorejob/cancel-restore' );
        // fill form
        $form->populate( array(
            'client_name'    => $this->restoreNamespace->ClientNameFrom,
            'client_name_to' => $this->restoreNamespace->ClientNameTo,
            'type_restore'   => $this->restoreNamespace->typeRestore
        ));
        $this->view->form = $form;

        $this->render();
    }



    function listRecentRestoreAction()
    {
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        $page  = intval( $this->_request->getParam('page', 1) );
        $page  = ($page > 0) ? $page : 1;
        $this->view->title = $this->view->translate->_("List of Files to Restore")." ".$this->restoreNamespace->JobId;

        if ( !$this->restoreNamespace->JobHash )	{
        	$this->view->result = null;
        }
		$tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        // get total info
        $atotal = $tmp_tables->getTotalSummaryMark();
        $this->view->total_size = $atotal['total_size'];

        // *** pager ***
        // calculate total rows and pages
        $this->view->total_rows = $atotal['total_files'];
        $this->view->total_pages = ceil( $this->view->total_rows / self::ROW_LIMIT_FILES );
        $this->view->current_page = $page;
        // *** end pager ***

        $offset = self::ROW_LIMIT_FILES * ($page - 1);
        $this->view->result = $tmp_tables->getListToRestore($offset);
        /*
         * Form Restore options
         */
        Zend_Loader::loadClass('FormRestoreOptions');
        $form = new formRestoreOptions();
        // validator "Where"
        Zend_Loader::loadClass('MyClass_Validate_BaculaAclWhere');
        $validator_where = new MyClass_Validate_BaculaAclWhere();
        if ( $this->_request->isPost() && $this->_request->getParam('from_form') ) {
            // Get values from Form Restore Options
            $this->getParamFromForm();
            if ( $validator_where->isValid( $this->where ) ) {
                $this->_forward( 'run-restore-recent', 'restorejob', null, $this->_request->getParams() );
                return;
            } else {
                // Form failed validation
                $messages = $validator_where->getMessages();
                $this->view->msgNoValid = $messages[0];
            }
        }
        /*
         * Form Restore options
         */
        $form->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'decorators/formRestoreoptions.phtml',
                'form'=> $form
            ))
        ));
        $form->init();
        $form->setAction( $this->view->baseUrl .'/restorejob/list-recent-restore' );
        $form->setActionCancel( $this->view->baseUrl .'/restorejob/cancel-restore-recent' );
        // fill form
        $form->populate( array(
            'client_name'    => $this->restoreNamespace->ClientNameFrom,
            'client_name_to' => $this->restoreNamespace->ClientNameTo,
            'type_restore'   => $this->restoreNamespace->typeRestore
        ));
        $this->view->form = $form;

        $this->render();
    }



    /**
     * Run Restore Job + Bacula ACLs
     * Run a restore job
     *
     * see
     * The Restore Command: http://www.bacula.org/rel-manual/Restore_Command.html
     * Running the Console from a Shell Script: http://www.bacula.org/rel-manual/Bacula_Console.html#SECTION002180000000000000000
     * Bacula Console: http://www.bacula.org/rel-manual/Bacula_Console.html
     */
    function runRestoreAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        Zend_Loader::loadClass('Job');
        $job = new Job();
        if ( !$job->isJobIdExists($this->restoreNamespace->JobId) )   // with Bacula ACLs
                return;

        // Get values from Form Restore Options
        $this->getParamFromForm();
        // Override some variables
        $this->jobid = $this->restoreNamespace->JobId;

        $this->view->title = $this->view->translate->_("Restore JobId");
        $this->view->jobid = $this->restoreNamespace->JobId;
        $this->view->jobidhash = $this->restoreNamespace->JobHash;

        $cmd_mount = '';
        $cmd_sleep = '';
        if ( (!empty($storage)) )    {
            $cmd_mount = 'mount "' . $storage . '"';
            $cmd_sleep = '@sleep 7';
        }
        // check access to bconsole
        Zend_Loader::loadClass('Director');
        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }

        /* create table for restore (Create a table for recovery)
         * see also
         * http://www.bacula.org/5.0.x-manuals/en/main/main/Restore_Command.html
         * 7: Enter a list of files to restore
         * If you prefix the filename with a question mark (?), then the filename will be interpreted as an SQL table name,
         * and Bacula will include the rows of that table in the list to be restored.
         * The table must contain the JobId in the first column and the FileIndex in the second column.
         */
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        $tmp_tables->prepareTmpTableForRestore();
        //******************************* run job ***************************************
        // perform the command line  (Form the command line)
        // restore jobid=9713 file=<"/tmp/webacula_restore_9713.tmp" client="local.fd" yes
        // restore storage=<storage-name> client=<backup-client-name> where=<path> pool=<pool-name>
        //      fileset=<fileset-name> restoreclient=<restore-client-name>  select current all done
        $cmd = 'restore '. $this->getCmdRestore() .
               ' file=?"'.$tmp_tables->getTableNameFile(). '"' .
               ' yes';
        $comment = __METHOD__;
        $astatusdir = $director->execDirector(
" <<EOF
@#
@# $comment
@#
$cmd_mount
$cmd_sleep
$cmd
$this->restore_job_select
quit
EOF"
        );
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] == 0 )	{
            $this->deleteTmpTables();
            $this->mySessionStop();
        } else {
            $this->view->result_error = $astatusdir['result_error'];
        }
        $this->render();
    }



    /**
     *  with Bacula ACLs
     */
    function runRestoreRecentAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        // session expired ?
        if ( !isset($this->restoreNamespace->isSessionExist) ) {
            echo $this->renderScript('restorejob/msg02session.phtml');
            return;
        }
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        $this->view->title = $this->view->translate->_("Restore the most recent backup (or before a specified time) for a client");
        Zend_Loader::loadClass('Director');
        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->renderScript('restorejob/run-restore.phtml');
            return;
        }
        // Get values from Form Restore Options
        $this->getParamFromForm();

        /* create table for restore (Create a table for recovery)
         * see also
         * http://www.bacula.org/5.0.x-manuals/en/main/main/Restore_Command.html
         * 7: Enter a list of files to restore
         * If you prefix the filename with a question mark (?), then the filename will be interpreted as an SQL table name,
         * and Bacula will include the rows of that table in the list to be restored.
         * The table must contain the JobId in the first column and the FileIndex in the second column.
         */
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        $tmp_tables->prepareTmpTableForRestore();
        $date_before = 'current';
        if ( !empty($this->restoreNamespace->DateBefore) )
            $date_before = 'before="'. $this->restoreNamespace->DateBefore . '"';
        //******************************* Start job ***************************************
        // Form the command line
        // restore client="local.fd" fileset="test1" before="2009-05-15 14:50:01" file=<"/etc/bacula/webacula_restore.tmp" done yes
        $cmd = 'restore ' . $this->getCmdRestore() .' '.	$date_before .
               ' file=?"'. $tmp_tables->getTableNameFile() . '" done yes';
        $comment = __METHOD__;
        $astatusdir = $director->execDirector(
" <<EOF
@#
@# $comment
@#
$cmd
$this->restore_job_select
@quit
EOF"
        );

        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] == 0 )	{
            $this->deleteTmpTables();
            $this->mySessionStop();
        } else {
            $this->view->result_error = $astatusdir['result_error'];
        }
        $this->renderScript('restorejob/run-restore.phtml');
    }


    /**
     * Cancel Restore
     * (Cancel restore)
     */
    function cancelRestoreAction()
    {
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        $tmp_tables->deleteAllTmpTables();
        $this->mySessionStop();
        // goto home (Redirect to home page)
        $this->_redirect('index');
    }


    /**
     * Cancel Restore Recent
     */
    function cancelRestoreRecentAction()
    {
        $tmp_tables = new WbTmpTable($this->restoreNamespace->JobHash, $this->ttl_restore_session);
        $tmp_tables->deleteAllTmpTables();
        $this->mySessionStop();
        // goto home (Redirect to home page)
        $this->_redirect('index');
    }



    /**
     * Restore single file + Bacula ACLs
     */
    function restoreSingleFileAction()
    {
        $this->view->title = $this->view->translate->_('Restore Single File');
        $this->fileid = $this->_request->getParam('fileid', null);
        // get data for form
        Zend_Loader::loadClass('Job');
        $job = new Job();
        $this->view->file = $job->getByFileId($this->fileid); // do Bacula ACLs
        if ( empty($this->view->file) ) {
            $this->render();
            return;
        }
        Zend_Loader::loadClass('Client');
        $clients = new Client();
        /*
         * Restore options form
         */
        Zend_Loader::loadClass('FormRestoreOptions');
        $form = new formRestoreOptions();
        $form->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'decorators/formRestoreoptions.phtml',
                'form'=> $form
            ))
        ));
        $form->init();
        $form->setAction( $this->view->baseUrl .'/restorejob/run-restore-single-file' );
        $form->setActionCancel( $this->view->baseUrl );
        // fill form
        $form->populate( array(
            'fileid'         => $this->fileid,
            'client_name'    => $clients->getClientName($this->view->file[0]['jobid'])
        ));
        $this->view->form = $form;
        $this->render();
    }



    /**
     * Run Restore single File + Bacula ACLs
     * http://www.bacula.org/rel-manual/Restore_Command.html
     */
    function runRestoreSingleFileAction()
    {
        // do Bacula ACLs
        $command = 'restore';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        $this->view->title = $this->view->translate->_('Restore Single File');
        // Get values from Form Restore Options
        $this->getParamFromForm();

        // get File data
        Zend_Loader::loadClass('Job');
        $job = new Job();
        $file = $job->getByFileId($this->fileid); // do Bacula ACL
        if ( !$file )
            return;
        // check access to bconsole
        Zend_Loader::loadClass('Director');
        $director = new Director();
        if ( !$director->isFoundBconsole() )    {
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->renderScript('restorejob/run-restore.phtml');
            return;
        }
        //******************************* Start job ***************************************
        // perform the command line  (Form the command line)
        // restore jobid=9713 file=<"/tmp/webacula_restore_9713.tmp" client="local.fd" yes
        // restore storage=<storage-name> client=<backup-client-name> where=<path> pool=<pool-name>
        //      fileset=<fileset-name> restoreclient=<restore-client-name>  select current all done
        $cmd = 'restore jobid=' . $file[0]['jobid'] .
               ' file="' . $file[0]['path'] . $file[0]['filename'] . '"' .
               $this->getCmdRestore() .
               ' yes';

        $comment = __METHOD__;
        $astatusdir = $director->execDirector(
" <<EOF
@#
@# $comment
@#
$cmd
quit
EOF");
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] == 0 )   {
            $this->deleteTmpTables();
            $this->mySessionStop();
        } else {
            $this->view->result_error = $astatusdir['result_error'];
        }
        //echo "<pre>3 command_output:<br>" . print_r($command_output) . "<br><br>return_var = " . $return_var . "</pre>"; exit;
        $this->renderScript('restorejob/run-restore.phtml');
    }




}

