<?php
/**
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This file is part of Webacula.
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
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

/*
 * формат хранения данных в сессии
 *
 * *** common parameters
 *
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

class RestorejobController extends Zend_Controller_Action
{
    // for pager
    const ROW_LIMIT_FILES = 500;
    // for names of tmp tables (для формирования имен временных таблиц)
    const _PREFIX = '_'; // только в нижнем регистре

    public $db_adapter;

    // для хранения данных для Restore
    protected $restoreNamespace;
    const RESTORE_NAME_SPACE = 'RestoreSessionNamespace';
    protected $ttl_restore_session = 3900; // time to live session (65 min)
    protected $logger;


    function my_debug($msg)
    {
        echo "$msg<br>";
        echo '<br><br><br><hr><h1>debug_backtrace</h1><pre>';
        $backtrace = debug_backtrace();
        foreach ($backtrace as $line) {
            echo 'file : ', $line['file'], "<br>";
            echo 'line : ', $line['line'], "<br>";
            echo 'function : ', $line['function'], "<br>";
            echo 'class : ', $line['class'], "<br>";
            echo '---------<br>';
        }
        //var_dump(debug_backtrace());
        echo '<br><h3>--- end debug_backtrace</h3>';
        exit;
    }

    function init()
    {
        // for debug !!!
        /*Zend_Loader::loadClass('Zend_Log_Writer_Stream');
        Zend_Loader::loadClass('Zend_Log');
        $writer = new Zend_Log_Writer_Stream('/tmp/ajax.log');
        $this->logger = new Zend_Log($writer);
        $this->logger->log("debug on", Zend_Log::INFO);*/

        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->_helper->viewRenderer->setNoRender(); // disable autorendering
        $this->view->translate = Zend_Registry::get('translate');
        $this->view->language  = Zend_Registry::get('language');

        // получаем ttl_restore_session
        $config_ini = Zend_Registry::get('config');
        if ( empty($config_ini->ttl_restore_session) || intval($config_ini->ttl_restore_session) < 300) {
            $this->ttl_restore_session = 3900;
        } else {
            $this->ttl_restore_session = intval($config_ini->ttl_restore_session);
        }

        $this->view->baseUrl = $this->_request->getBaseUrl();
        // load model
        Zend_Loader::loadClass('WbTmpTable');
        $this->translate = Zend_Registry::get('translate');

        // session begin
        $this->restoreNamespace = new Zend_Session_Namespace(self::RESTORE_NAME_SPACE);
        $this->restoreNamespace->setExpirationSeconds($this->ttl_restore_session);
        Zend_Session::rememberMe($this->ttl_restore_session);
    }


    function mainFormAction()
    {
        $this->view->unit_test = $this->_request->getParam('test', null); // for tests
        // get data for form
        Zend_Loader::loadClass('Client');
        $clients = new Client();
        $this->view->clients = $clients->fetchAll();
        Zend_Loader::loadClass('FileSet');
        $filesets = new FileSet();
        $this->view->filesets = $filesets->fetchAll();

        $this->view->title = $this->view->translate->_("Restore Job");
        $this->view->jobid = intval( $this->_request->getParam('jobid', null) );
        $this->render();
    }

    function cloneBaculaTables($jobidhash)
    {
        /* извлекаем данные о jobid из сессии */
        $jobid = $this->restoreNamespace->JobId;
        $tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
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
        /* извлекаем данные обо всех jobids из сессии */
        $sjobids = implode(",", $this->restoreNamespace->aJobId);
        $tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
        $tmp_tables->cloneRecentBaculaToTmp($jobidhash, $sjobids);
    }


    /**
     * Manager of action depending on the user's choice
     * Диспетчер действий в зависимости от выбора пользователя
     *
     */
    function restoreChoiceAction()
    {
        // в форме "Restore Job" сделан выбор
        $choice  = addslashes( $this->_request->getParam('choice', '') );
        $jobid   = intval( $this->_request->getParam('jobid', null) );
        // запоминаем данные в сессии
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
     * Диспетчер действий в зависимости от выбора пользователя
     *
     */
    function restoreRecentChoiceAction()
    {
        // в форме "Restore Job" сделан выбор
        $choice_recent    = addslashes( $this->_request->getParam('choice_recent', '') );
        // запоминаем данные в сессии
        $this->restoreNamespace->typeRestore = 'restore_recent';
        $this->restoreNamespace->ClientNameFrom = addslashes( $this->_request->getParam('client_name_from', null) );
        $this->restoreNamespace->FileSet		= addslashes( $this->_request->getParam('fileset', null) );
        $this->restoreNamespace->DateBefore		= addslashes( trim(
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
     * Restore All
     *
     * see
     * The Restore Command: http://www.bacula.org/rel-manual/Restore_Command.html
     * Running the Console from a Shell Script: http://www.bacula.org/rel-manual/Bacula_Console.html#SECTION002180000000000000000
     * Bacula Console: http://www.bacula.org/rel-manual/Bacula_Console.html
     *
     */
    function restoreAllAction()
    {
        Zend_Loader::loadClass('Job');
        $job = new Job();
        Zend_Loader::loadClass('Client');
        $client = new Client();
        $jobid  = intval( $this->_request->getParam('jobid', 0) );
        /* запоминаем "левый" jobid в сессии, чтобы не было ошибок при завершении restore */
        $jobidhash = md5('fake_jobid');
        $this->restoreNamespace->aJobId = array(0 => $jobidhash, 1 => $jobid);

        $this->view->title = $this->view->translate->_("Restore All files for JobId");
        $this->view->jobid = $jobid;

        // начало отрисовки? т.е. форма выбора client, where, storage уже заполнена?
        $choice_form = intval( $this->_request->getParam('choice_form', 0) );

        // существует ли такое jobid
        if ( !$job->isJobIdExists($jobid) ) {
            // выдача сообщения, что такого joid не существует
            $this->view->title = $this->view->translate->_("Restore Job");
            $this->view->jobid = intval( $this->_request->getParam('jobid', null) );
            $this->view->msgNoJobId = sprintf($this->view->translate->_("JobId %u does not exist."), $jobid);

            // get data for form
            Zend_Loader::loadClass('Storage');
            Zend_Loader::loadClass('Pool');
            Zend_Loader::loadClass('FileSet');

            $this->view->clients = $client->fetchAll();

            $storages = new Storage();
            $this->view->storages = $storages->fetchAll();

            $pools = new Pool();
            $this->view->pools = $pools->fetchAll();

            $filesets = new FileSet();
            $this->view->filesets = $filesets->fetchAll();

            echo $this->renderScript('restorejob/main-form.phtml');
            return;
        }

        $client_name = $client->getClientName($jobid);
        $this->view->client_name = $client_name;

        // *************************** run restore ************************************************
        if ( $choice_form == 1 )  {
            // форма выбора client, where, storage уже заполнена
            // check access to bconsole
            Zend_Loader::loadClass('Director');
            $director = new Director();
            if ( !$director->isFoundBconsole() )	{
                $this->view->result_error = 'NOFOUND_BCONSOLE';
                $this->render();
                return;
            }

            $client_restore = addslashes( $this->_request->getParam('client', '') );
            $client_backup  = addslashes( $this->_request->getParam('client_name', '') );
            $where   = addslashes( $this->_request->getParam('where', null) );
            $storage = addslashes( $this->_request->getParam('storage', null) );
            $pool    = addslashes( $this->_request->getParam('pool', null) );
            $fileset = addslashes( $this->_request->getParam('fileset', null) );

            if ( (!empty($storage)) && ($storage != 'default') )    {
                $cmd_mount = 'mount "' . $storage . '"';
                $cmd_sleep = '@sleep 10';
            }   else {
                $cmd_mount = '';
                $cmd_sleep = '';
            }

           //******************************* запуск задания ***************************************
           // формируем командную строку
           // restore client=Rufus select current all done yes
           $cmd = 'restore jobid=' . $jobid . ' restoreclient="' . $client_restore . '"';
           if ( !empty($client_backup) )  $cmd .= ' client="' . $client_backup . '"';
           if ( !empty($where) )   $cmd .= ' where="' . $where . '"';
           if ( !empty($storage) ) $cmd .= ' storage="' . $storage . '"';
           if ( !empty($pool) ) $cmd .= ' pool="' . $pool . '"';
           if ( !empty($fileset) ) $cmd .= ' fileset="' . $fileset . '"';
           $cmd .= ' all done yes';

            $astatusdir = $director->execDirector(
" <<EOF
$cmd_mount
$cmd_sleep
$cmd
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
            $this->renderScript('restorejob/run-restore.phtml');

        } else {
            // get data for form
            Zend_Loader::loadClass('Storage');
            Zend_Loader::loadClass('Pool');
            Zend_Loader::loadClass('FileSet');

            $this->view->clients = $client->fetchAll();

            $storages = new Storage();
            $this->view->storages = $storages->fetchAll();

            $pools = new Pool();
            $this->view->pools = $pools->fetchAll();

            $filesets = new FileSet();
            $this->view->filesets = $filesets->fetchAll();

            $this->render();
        }
    }


    function restoreRecentAllAction()
    {
        Zend_Loader::loadClass('Director');
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        /* запоминаем "левый" jobid в сессии, чтобы не было ошибок при завершении restore */
        $this->restoreNamespace->JobHash = md5('fake_jobid');
        $this->view->title = $this->view->translate->_("Restore All files");

        // начало отрисовки? т.е. форма выбора client, where уже заполнена?
        $choice_form = intval( $this->_request->getParam('choice_form', 0) );

        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }

        // *************************** run restore ************************************************
        if ( $choice_form == 1 )  {
            // форма выбора client, where уже заполнена
            $this->restoreNamespace->ClientNameTo   = addslashes( $this->_request->getParam('client_to_restore', '') );
            $path_to_restore     = $this->_request->getParam('path_to_restore', '');

            if ( empty($this->restoreNamespace->DateBefore) ) {
                $cmd_date_before = ' current ';
            } else {
                $cmd_date_before = ' before="'. $this->restoreNamespace->DateBefore . '" ';
            }
            if ( empty($this->restoreNamespace->ClientNameTo) ) {
                $client_to_restore = '';
            } else {
                $client_to_restore = ' restoreclient="'. $this->restoreNamespace->ClientNameTo . '" ';
            }
            if ( empty($path_to_restore) ) {
                $path_to_restore = '';
            } else {
                $path_to_restore = ' where="'. $path_to_restore . '" ';
            }
            //******************************* запуск задания ***************************************
            // формируем командную строку
            // restore client="local.fd" restoreclient="local.fd" fileset="test1"  where="/home/test/11111" current select all done yes
            // restore client="local.fd" fileset="test1" before="2009-05-11 11:36:56" select all done yes
            // restore client="local.fd" restoreclient="srv1.fd" fileset="test1" before="2009-05-11 11:36:56" select all done yes
            $cmd = 'restore client="' . $this->restoreNamespace->ClientNameFrom . '" ' .
                $client_to_restore . $path_to_restore .
                ' fileset="' . $this->restoreNamespace->FileSet . '"' .	$cmd_date_before;
            $cmd .= ' select all done yes';

            $astatusdir = $director->execDirector(
" <<EOF
$cmd
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
            $this->renderScript('restorejob/run-restore.phtml');

        } else {
            // для отрисовки формы выбора client, where
            $this->view->client_from_restore = $this->restoreNamespace->ClientNameFrom;
            $this->view->fileset_restore	 = $this->restoreNamespace->FileSet;
            $this->view->date_before		 = $this->restoreNamespace->DateBefore;
            // get data for form
            Zend_Loader::loadClass('Client');
            $clients = new Client();
            $this->view->clients = $clients->fetchAll();
            $this->render();
        }
    }


    function selectFilesAction()
    {
        Zend_Loader::loadClass('Job');
        $job = new Job();
        Zend_Loader::loadClass('Client');
        $client = new Client();
        // начало отрисовки дерева каталогов ?
        $beginr = intval( $this->_request->getParam('beginr', 0) );
        if ( $beginr == 1 ) {
            /* Начало отрисовки дерева каталогов */
            // существует ли такое jobid
            if ( !$job->isJobIdExists($this->restoreNamespace->JobId) ) {
                // выдача сообщения, что такого jobid не существует
                $this->view->title = $this->view->translate->_("Restore Job");
                $this->view->jobid = $this->restoreNamespace->JobId;
                $this->view->msgNoJobId = sprintf($this->view->translate->_("JobId %u does not exist."),
                    $this->restoreNamespace->JobId);
                // get data for form
                Zend_Loader::loadClass('Storage');
                Zend_Loader::loadClass('Pool');
                Zend_Loader::loadClass('FileSet');

                $this->view->clients = $client->fetchAll();

                $storages = new Storage();
                $this->view->storages = $storages->fetchAll();

                $pools = new Pool();
                $this->view->pools = $pools->fetchAll();

                $filesets = new FileSet();
                $this->view->filesets = $filesets->fetchAll();

                echo $this->renderScript('restorejob/main-form.phtml');
                return;
            }
            $this->restoreNamespace->ClientNameFrom = $client->getClientName($this->restoreNamespace->JobId);
            // tmp таблицы существуют ?
            $tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
            if ( !$tmp_tables->isAllTmpTablesExists() )	{
                $this->cloneBaculaTables( $this->restoreNamespace->JobHash ); // create tmp tables
                $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
            } else {
                $tmp_tables->dropOldTmpTables();  // delete all old tmp tables
                // tmp таблицы устарели ?
                if ( $tmp_tables->isOldTmpTables() )	{
                    // tmp-таблицы устарели
                    // create tmp tables
                    $this->cloneBaculaTables($this->restoreNamespace->JobHash);
                    // рисуем дерево
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
                    $curdir  = addslashes( $this->_request->getParam('curdir', '') );
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
                }   else {
                    // tmp таблицы не устарели
                    // выдать сообщение: 1. пересоздать временные таблицы 2. работать со старыми 3. выход
                    $this->view->jobid = $this->restoreNamespace->JobId;
                    $this->view->title = $this->view->translate->_('Restore Job');
                    echo $this->renderScript('restorejob/msg01.phtml');
                    return;
                }
            }
        } else {
            // продолжаем показывать дерево каталогов
            $curdir  = addslashes( $this->_request->getParam('curdir', '') );
            $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir));
        }
    }


    function selectBackupsBeforeDateAction()
    {
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Job');
        // поиск ClientId
        $client = new Client();
        $this->restoreNamespace->ClientIdFrom = $client->getClientId($this->restoreNamespace->ClientNameFrom);

        if ( !empty($this->restoreNamespace->DateBefore) ) {
            $date_before = " AND Job.StartTime<'".$this->restoreNamespace->DateBefore."'";
        } else {
            $date_before = '';
        }

        $job = new Job();
        $ajobs = $job->getJobBeforeDate($date_before, $this->restoreNamespace->ClientIdFrom, $this->restoreNamespace->FileSet);
        if ( !$ajobs ) {
            // сообщение, что не найден Full backup: No Full backup before 2009-05-20 15:19:49 found.
            $this->view->msg = sprintf($this->view->translate->_("No Full backup before %s found."), $this->restoreNamespace->DateBefore);
            echo $this->renderScript('msg-note.phtml');
            return;
        }

        /* запоминаем данные о jobids в сессии */
        $this->restoreNamespace->JobHash = md5($ajobs['hash']);
        $this->restoreNamespace->aJobId  = $ajobs['ajob_all'];

        $this->view->ajob_full = $ajobs['ajob_full'];
        $this->view->ajob_diff = $ajobs['ajob_diff'];
        $this->view->ajob_inc  = $ajobs['ajob_inc'];
        $this->view->ajob_all  = $ajobs['ajob_all'];
        $this->view->title = $this->view->translate->_("You have selected the following JobIds");
        $this->view->beginrecent = 1;
        $this->render();
    }

    function selectRecentFilesAction()
    {
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        // начало отрисовки дерева каталогов ?
        $beginrecent = intval( $this->_request->getParam('beginrecent', 0) );
        if ( $beginrecent == 1 ) {
            /* начало отрисовки дерева каталогов. */
            // данные в сессии уже запомнены в selectBackupsBeforeDateAction()
            // tmp таблицы существуют ?
            $tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
            if ( !$tmp_tables->isAllTmpTablesExists() )	{
                $this->cloneRecentBaculaTables($this->restoreNamespace->JobHash); // create tmp tables
                $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
            } else {
                $tmp_tables->dropOldTmpTables();  // delete all old tmp tables
                // tmp таблицы устарели ?
                if ( $tmp_tables->isOldTmpTables() )	{
                    // tmp-таблицы устарели
                    // create tmp tables
                    $this->cloneRecentBaculaTables($this->restoreNamespace->JobHash);
                    // рисуем дерево
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>'') );
                    $curdir  = addslashes( $this->_request->getParam('curdir', '') );
                    $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
                } else {
                    // tmp таблицы не устарели
                    // выдать сообщение: 1. пересоздать временные таблицы 2. работать со старыми 3. выход
                    $this->view->jobid = '';
                    $this->view->title = $this->view->translate->_('Restore Job');
                    echo $this->renderScript('restorejob/msg01.phtml');
                    return;
                }
            }
        } else {
            // продолжаем показывать дерево каталогов
            $curdir  = addslashes( $this->_request->getParam('curdir', '') );
            $this->_forward('draw-file-tree', null, null, array('curdir'=>$curdir) );
        }
    }


    /**
     * The main function of rendering of a directory tree
     * Главная функция по отрисовке дерева каталогов
     *
     * @param string jobidhash
     * @param string curdir
     *
     */
    function drawFileTreeAction()
    {
        $curdir  = stripslashes( $this->_request->getParam('curdir', '') );
        $this->view->title = $this->view->translate->_("Restore Job");

        $adir = array();
        if ( $this->restoreNamespace->JobHash )	{

            //************ get a list of all directories + LStat (получаем список всех каталогов + их атрибуты LStat) ******
            $tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
            $db = $tmp_tables->getDb();
            $stmt = $db->query("
                SELECT p.Path, p.isMarked, f.FileId, f.PathId, f.LStat
                FROM " . $tmp_tables->getTableNamePath() . " AS p
                LEFT JOIN " . $tmp_tables->getTableNameFile() . " AS f
                ON f.PathId = p.PathId WHERE (f.MD5 = '0') ORDER BY p.PathId ASC
            ");
            $result = $stmt->fetchAll();

			// get a list of directories on the current (получаем список каталогов относительно текущего)
			foreach($result as $line)	{
			    if ( !empty($curdir) ) {
					$pos = strpos($line['path'], $curdir);
				} else {
					$pos = 0;
					if ( $line['path'][0] == '/') $curdir = '/'; // linux path
					//elseif ( $line['path'][1] === ':') $curdir = $line['path'][0] . ':/'; // windows path
				}
				// найден текущий каталог
				if ( $pos === 0 )	{
					// удаляем текущий каталог из полного пути
					$nextdir = ereg_replace('^' . $curdir, '', $line['path']);

					// если есть еще подкаталоги
					if ( !empty($nextdir) ) {
						// получаем следующий уровень подкаталога
						$atmp = explode("/", $nextdir, 3);
						$dir = $atmp[0];

						if ( !empty($dir) ) {
						  $adir[$dir]['lstat']    = $line['lstat'];
						  $adir[$dir]['pathid']   = $line['pathid'];
						  $adir[$dir]['dir']      = $dir;
						  $adir[$dir]['ismarked'] = $line['ismarked'];
						}
					}
				}
			}
			unset($stmt);
            //echo "<pre>"; print_r($curdir); echo "</pre><br>";  // for !!!debug!!!
			//echo "<pre>"; print_r($adir); echo "</pre><br>";  // for !!!debug!!!

			// теперь необходимо получить список каталогов, данные LStat о которых не хранятся в таблице File
			// так бывает если, например, в FileSet заданы конкретные имена файлов
			$stmt = $db->query("SELECT PathId, Path, isMarked
					FROM " . $tmp_tables->getTableNamePath() . " AS n
					WHERE n.PathId NOT IN
						(SELECT p.PathId FROM " . $tmp_tables->getTableNamePath() . " AS p
 						LEFT JOIN " . $tmp_tables->getTableNameFile() . " AS f ON f.PathId = p.PathId WHERE (f.MD5 = '0'))");
			$result = $stmt->fetchAll();

			// get a list of directories on the current (получаем список каталогов относительно текущего)
			foreach($result as $line)	{
			    if ( !empty($curdir) ) {
					$pos = strpos($line['path'], $curdir);
				} else {
					$pos = 0;
					if ( $line['path'][0] == '/') $curdir = '/'; // linux path
					//elseif ( $line['path'][1] === ':') $curdir = $line['path'][0] . ':/'; // windows path
				}
				// найден текущий каталог
				if ( $pos === 0 )	{
					// удаляем текущий каталог из полного пути
					$nextdir = ereg_replace('^' . $curdir, '', $line['path']);

					// если есть еще подкаталоги
					if ( !empty($nextdir) ) {
						// получаем следующий уровень подкаталога
						$atmp = explode("/", $nextdir, 3);
						$dir = $atmp[0];

						if ( !empty($dir) ) {
						  $adir[$dir]['lstat']    = '';
						  $adir[$dir]['pathid']   = $line['pathid'];
						  $adir[$dir]['dir']      = $dir;
						  $adir[$dir]['ismarked'] = $line['ismarked'];
						}
					}
				}
			}
			unset($stmt);
			//echo "<pre>"; print_r($curdir); echo "</pre><br>";  // for !!!debug!!!
			//echo "<pre>"; print_r($adir); echo "</pre><br>";  // for !!!debug!!!


			//****** получаем список файлов в текущем каталоге ******
			$afile = array();
			if ( $curdir )	{
				$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
				$db = $tmp_tables->getDb();
				// unused ? $db_adapter = Zend_Registry::get('DB_ADAPTER_WEBACULA');
				switch ($this->db_adapter) {
					case 'PDO_SQLITE':
						$stmt = $db->query("
							SELECT DISTINCT f.FileId as fileid, f.LStat as lstat, f.PathId as pathid, f.isMarked as ismarked, n.Name as name, p.Path as path
							FROM " . $tmp_tables->getTableNameFile() . " AS f,
							" . $tmp_tables->getTableNameFilename()  . " AS n,
							" . $tmp_tables->getTableNamePath() .      " AS p
							WHERE (f.FileNameId = n.FileNameId) AND (f.PathId = p.PathId) AND
							(p.Path = '" . addslashes($curdir) . "') ORDER BY Name ASC;");
						break;
					default: // include mysql, postgresql
						$stmt = $db->query("
							SELECT DISTINCT f.FileId, f.LStat, f.PathId, f.isMarked, n.Name, p.Path
							FROM " . $tmp_tables->getTableNameFile() . " AS f,
							" . $tmp_tables->getTableNameFilename()  . " AS n,
							" . $tmp_tables->getTableNamePath() .      " AS p
							WHERE (f.FileNameId = n.FileNameId) AND (f.PathId = p.PathId) AND
							(p.Path = '" . addslashes($curdir) . "') ORDER BY Name ASC;");
						break;
				}
				$result = $stmt->fetchAll();

				// получаем список файлов
				foreach($result as $line)	{
					$file = $line['name'];
					$afile[$file]['fileid']   = $line['fileid'];
					$afile[$file]['pathid']   = $line['pathid'];
					$afile[$file]['lstat']    = $line['lstat'];
					$afile[$file]['ismarked'] = $line['ismarked'];
				}
				unset($stmt);
			}
			//echo "<pre>"; print_r($adir); echo "</pre><br>";  // for !!!debug!!!
			//echo "<pre>"; print_r($afile); echo "</pre>"; exit; // for !!!debug!!!

			$this->view->adir   = $adir;
			$this->view->afile  = $afile;
			$this->view->curdir = $curdir;
			$this->view->jobidhash = $this->restoreNamespace->JobHash;

            // получаем суммарную статистику
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
     * Пометить файл для восстановления
     * See also javascript in draw-file-tree.pthml
     *
     * @return json
     */
    public function markFileAction()
    {
    	$this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getPost('data', '');
        if ( $encodedValue ) {
	        // 	Получение значения
    	    $phpNative = Zend_Json::decode($encodedValue);
        	$fileid = $phpNative['fileid'];
        	$jobidhash = $phpNative['jobidhash'];
			//$this->logger->log("markFileAction()  $fileid  $jobidhash", Zend_Log::INFO); // !!! debug

	        // производим действия в БД
			$tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
    		$tmp_tables->markFile($fileid);
    		$filename = $tmp_tables->getFileName($fileid);
	    	// получаем суммарную статистику
    	    $atotal = $tmp_tables->getTotalSummaryMark();
	        // формируем массив для отправки назад
    	    $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
        	$aout['total_files'] = $atotal['total_files'];
        	$aout['filename']    = $filename;
        	$aout['allok']    	 = 1; // действия успешны
	        // Преобразование для возвращения клиенту
    	    $json = Zend_Json::encode($aout);
    	    // возвращаем данные в javascript
        	echo $json;
        } else {
        	$aout['allok']    = 0;
        	$json = Zend_Json::encode($aout);
        	echo $json;
        }
    }


    /**
     * Remove the mark with a file for restore
     * Снять отметку с файла для восстановления
     * See also javascript in draw-file-tree.pthml
     *
     * @return json
     */
	public function unmarkFileAction()
    {
    	$this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getParam('data', '');
        if ( $encodedValue ) {
	        // Получение значения
    	    $phpNative = Zend_Json::decode($encodedValue);
        	$fileid = $phpNative['fileid'];
        	$jobidhash = $phpNative['jobidhash'];
        	//$this->logger->log("unmarkFileAction()  $fileid  $jobidhash", Zend_Log::INFO); // !!! debug
	        // производим действия в БД
			$tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
    		$tmp_tables->unmarkFile($fileid);
    		$filename = $tmp_tables->getFileName($fileid);
	    	// получаем суммарную статистику
    	    $atotal = $tmp_tables->getTotalSummaryMark();
        	// формируем массив для отправки назад
	        $aout['total_size']  = $this->view->convBytes($atotal['total_size']);
    	    $aout['total_files'] = $atotal['total_files'];
        	$aout['filename']    = $filename;
        	$aout['allok']    	 = 1; // действия успешны
	        // Преобразование для возвращения клиенту
    	    $json = Zend_Json::encode($aout);
	        // возвращаем данные в javascript
    	    echo $json;
        }  else {
        	$aout['allok']    = 0;
        	$json = Zend_Json::encode($aout);
        	echo $json;
        }
    }


    /**
     * Пометить каталог + файлы в каталоге + подкаталоги + файлы в них для восстановления.
     * См. javascript в draw-file-tree.pthml
     */
    public function markDirAction()
    {
    	$this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getPost('data', '');
        if ( $encodedValue ) {
	        // Получение значения
    	    $phpNative = Zend_Json::decode($encodedValue);
	        $path  = $phpNative['path'];
	        $jobidhash = $phpNative['jobidhash'];
			//$this->logger->log("markDirAction() input value:\n$path\n$jobidhash\n", Zend_Log::INFO); // !!! debug
	        // производим действия в БД
			$tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
	        $res = $tmp_tables->markDir($path, 1); // isMarked = 1
    	    if ( $res ) {
    		    // $aout['msg'] = sprintf($this->view->translate->_("%s<br>(%s dirs, %s files affected)"), $res['path'], $res['dirs'], $res['files']);
    		    $aout['msg'] = sprintf($this->view->translate->_("%s<br>(%s dirs and files affected)"), $res['path'], $res['files'] + $res['dirs']);
    		} else {
    	    	$aout['msg'] =  $this->view->translate->_('internal program error !');
    		}
	    	// получаем суммарную статистику
    	    $atotal = $tmp_tables->getTotalSummaryMark();
        	// формируем массив для отправки назад
        	$aout['total_size']  = $this->view->convBytes($atotal['total_size']);
        	$aout['total_files'] = $atotal['total_files'];
        	$aout['path']        = $path;
        	$aout['allok']    	 = 1; // действия успешны
			//$this->logger->log("markDirAction() return value :\n".$aout['total_size']."\n".$aout['total_files']."\n".$aout['path']."\n".$aout['allok']."\n".$aout['msg'], Zend_Log::INFO); // !!! debug
	        // Преобразование для возвращения клиенту
    	    $json = Zend_Json::encode($aout);
        	// возвращаем данные в javascript
        	echo $json;
        } else {
        	$aout['allok']    = 0;
        	$json = Zend_Json::encode($aout);
        	echo $json;
        }
    }


    /**
     * Remove the marker catalog files in the directory and subdirectories + + files in order to restore them.
     * Убрать пометку каталога и файлов в каталоге + подкаталоги + файлы в них для восстановления.
     * See javascript in draw-file-tree.pthml
     */
    public function unmarkDirAction()
    {
    	$this->_helper->layout->disableLayout(); // disable layouts
        $encodedValue = $this->_request->getPost('data', '');
        if ( $encodedValue ) {
	        // Получение значения
    	    $phpNative = Zend_Json::decode($encodedValue);
        	$path  = $phpNative['path'];
	        $jobidhash = $phpNative['jobidhash'];
    	    //$this->logger->log("unmarkDirAction()  $path  $jobidhash", Zend_Log::INFO); // !!! debug
	        // производим действия в БД
			$tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash);
	        $res = $tmp_tables->markDir($path, 0); // isMarked = 0
    	    if ( $res ) {
    		    $aout['msg'] = sprintf($this->view->translate->_("%s<br>(%s dirs, %s files affected)"), $res['path'], $res['dirs'], $res['files']);
	    	} else {
    		    $aout['msg'] =  $this->view->translate->_('internal program error !');
	    	}
	    	// получаем суммарную статистику
    	    $atotal = $tmp_tables->getTotalSummaryMark();
        	// формируем массив для отправки назад
        	$aout['total_size']  = $this->view->convBytes($atotal['total_size']);
        	$aout['total_files'] = $atotal['total_files'];
	        $aout['path']        = $path;
	        $aout['allok']       = 1; // действия успешны
    	    // Преобразование для возвращения клиенту
        	$json = Zend_Json::encode($aout);
	        // возвращаем данные в javascript
    	    echo $json;
        } else {
        	$aout['allok']    = 0;
        	$json = Zend_Json::encode($aout);
        	echo $json;
        }
    }





    function oldTmpTableAction ()
    {
    	$this->_helper->viewRenderer->setNoRender(); // disable autorendering
    	// в форме "Msg01" сделан выбор, что делать со старыми tmp-таблицами
		$choice  = addslashes( $this->_request->getParam('choice', '') );
		if ($this->restoreNamespace->typeRestore)	{
			switch ( $choice )
			{
				case 'recreate_tmp': // выбор: пересоздать временные таблицы
					$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
					$tmp_tables->createTmpTables();
					// перенаправление в зависимости от typeRestore
					if ( $this->restoreNamespace->typeRestore == 'restore_recent' ) {
						$this->cloneRecentBaculaTables($this->restoreNamespace->JobHash);
					} else {
						$this->cloneBaculaTables($this->restoreNamespace->JobHash);
					}
					$this->_forward('draw-file-tree', null, null, array('curdir'=>''));
					break;
				case 'continue_tmp': // работать со старыми
					// обновить timestamp
					$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
					$tmp_tables->updateTimestamp();
					// продолжить
					$this->_forward('draw-file-tree', null, null, array('curdir'=>''));
					break;
				case 'goto_homepage': // на главную страницу
					$this->_redirect('index');
					break;
			}
		}
    }



    /**
     * Показываем plain-список файлов перед запуском задания на восстановление
     *
     */
    function listRestoreAction()
    {
        $page  = intval( $this->_request->getParam('page', 1) );
        $page  = ($page > 0) ? $page : 1;
        $this->view->title = $this->view->translate->_("List of Files to Restore for JobId")." ".$this->restoreNamespace->JobId;

        if ( !$this->restoreNamespace->JobHash )	{
        	$this->view->result = null;
        }
		$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
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

        // get data for form
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Storage');
        Zend_Loader::loadClass('Pool');
        Zend_Loader::loadClass('FileSet');

        $clients = new Client();
  	    $this->view->clients = $clients->fetchAll();

  	    $storages = new Storage();
   	    $this->view->storages = $storages->fetchAll();

   	    $pools = new Pool();
   	    $this->view->pools = $pools->fetchAll();

   	    $filesets = new FileSet();
   	    $this->view->filesets = $filesets->fetchAll();

   	    $this->view->client_name_to = $this->restoreNamespace->ClientNameTo;
   	    $this->view->type_restore   = $this->restoreNamespace->typeRestore;

    	$this->render();
    }


    function listRecentRestoreAction()
    {
        $page  = intval( $this->_request->getParam('page', 1) );
        $page  = ($page > 0) ? $page : 1;
        $this->view->title = $this->view->translate->_("List of Files to Restore")." ".$this->restoreNamespace->JobId;

        if ( !$this->restoreNamespace->JobHash )	{
        	$this->view->result = null;
        }
		$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
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

        // get data for form
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Storage');
        Zend_Loader::loadClass('Pool');
        Zend_Loader::loadClass('FileSet');

        $clients = new Client();
  	    $this->view->clients = $clients->fetchAll();

   	    $this->view->client_name_to = $this->restoreNamespace->ClientNameTo;
   	    $this->view->type_restore   = $this->restoreNamespace->typeRestore;

    	$this->render();
    }


    /**
     * Run Restore Job
     * Запуск задания на восстановление
     *
     * see
     * The Restore Command: http://www.bacula.org/rel-manual/Restore_Command.html
     * Running the Console from a Shell Script: http://www.bacula.org/rel-manual/Bacula_Console.html#SECTION002180000000000000000
     * Bacula Console: http://www.bacula.org/rel-manual/Bacula_Console.html
     */
    function runRestoreAction()
    {
    	Zend_Loader::loadClass('Job');
		$job = new Job();
        $client_name_to = addslashes( $this->_request->getParam('client_name_to', null));
        $where   = addslashes( $this->_request->getParam('where', null));
        $storage = addslashes( $this->_request->getParam('storage', null));
        $pool    = addslashes( $this->_request->getParam('pool', null));
        $fileset = addslashes( $this->_request->getParam('fileset', null));

        $this->view->title = $this->view->translate->_("Restore JobId");
        $this->view->jobid = $this->restoreNamespace->JobId;
        $this->view->jobidhash = $this->restoreNamespace->JobHash;

        if ( (!empty($storage)) && ($storage != 'default') )    {
            $cmd_mount = 'mount "' . $storage . '"';
            $cmd_sleep = '@sleep 10';
        }   else {
            $cmd_mount = '';
            $cmd_sleep = '';
        }

        if ( !$job->isJobIdExists($this->restoreNamespace->JobId) ) return;
        // получаем каталог куда можно писать файл
        $config = Zend_Registry::get('config');
        $tmpdir = $config->tmpdir;

        // check access to bconsole
        Zend_Loader::loadClass('Director');
        $director = new Director();
		if ( !$director->isFoundBconsole() )	{
			$this->view->result_error = 'NOFOUND_BCONSOLE';
   		  	$this->render();
   		  	return;
   	    }

        // export to a text file (экспорт в текстовый файл)
		$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
        $ares = $tmp_tables->exportMarkFiles($tmpdir);
        // unused ? $list = $ares['name']; // имя файла со списком файлов для восстановления

        if ( $ares['result'] == TRUE )  {
            //******************************* запуск задания ***************************************
            // perform the command line  (формируем командную строку)
            // !!! ONLY IN THAT ORDER (ТОЛЬКО В УКАЗАННОМ ПОРЯДКЕ) !!!
            // restore jobid=9713 file=<"/tmp/webacula_restore_9713.tmp" client="local.fd" yes
            // restore storage=<storage-name> client=<backup-client-name> where=<path> pool=<pool-name>
            //      fileset=<fileset-name> restoreclient=<restore-client-name>  select current all done
            $cmd = 'restore jobid=' . $this->restoreNamespace->JobId .
				   ' file=<"/tmp/webacula_restore_' . $this->restoreNamespace->JobHash . '.tmp"' .
            	   ' restoreclient="' . $client_name_to . '" ';
            if ( !empty($this->restoreNamespace->ClientNameFrom) )   $cmd .= ' client="' . $this->restoreNamespace->ClientNameFrom . '"';
            if ( !empty($where) )           $cmd .= ' where="' . $where . '"';
            if ( !empty($storage) )         $cmd .= ' storage="' . $storage . '"';
            if ( !empty($pool) )            $cmd .= ' pool="' . $pool . '"';
            if ( !empty($fileset) )         $cmd .= ' fileset="' . $fileset . '"';

            $cmd .= ' yes';

            //echo $cmd; exit;// !!! debug
            $astatusdir = $director->execDirector(
" <<EOF
$cmd_mount
$cmd_sleep
$cmd
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
            //echo "<pre>3 command_output:<br>" . print_r($command_output) . "<br><br>return_var = " . $return_var . "</pre>"; exit;

        }   else {
            // выдать сообщение
            $this->view->result_error = 'ERROR_EXPORT';
        }
        $this->render();
    }


    function runRestoreRecentAction()
    {
        // http://www.bacula.org/en/rel-manual/Restore_Command.html#SECTION002240000000000000000
        $this->view->title = $this->view->translate->_("Restore the most recent backup (or before a specified time) for a client");
        Zend_Loader::loadClass('Director');
        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }
        $this->restoreNamespace->ClientNameTo   = addslashes( $this->_request->getParam('client_name_to', '') );
        $path_to_restore = $this->_request->getParam('path_to_restore', '');

        // export to a text file (экспорт в текстовый файл)
        // получаем каталог куда можно писать файл
        $config = Zend_Registry::get('config');
        $tmpdir = $config->tmpdir;
        $tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
        $ares = $tmp_tables->exportMarkFiles($tmpdir);
        $list = $ares['name']; // имя файла со списком файлов для восстановления

        if ( empty($this->restoreNamespace->DateBefore) ) {
            $cmd_date_before = ' current ';
        } else {
            $cmd_date_before = ' before="'. $this->restoreNamespace->DateBefore . '" ';
        }
        if ( empty($this->restoreNamespace->ClientNameTo) ) {
            $client_to_restore = '';
        } else {
            $client_to_restore = ' restoreclient="'. $this->restoreNamespace->ClientNameTo . '" ';
        }
        if ( empty($path_to_restore) ) {
            $path_to_restore = '';
        } else {
            $path_to_restore = ' where="'. $path_to_restore . '" ';
       	}
        //******************************* запуск задания ***************************************
        // формируем командную строку
        // restore client="local.fd" fileset="test1" before="2009-05-15 14:50:01" file=<"/etc/bacula/webacula_restore.tmp" done yes
        $cmd = 'restore client="' . $this->restoreNamespace->ClientNameFrom . '" ' .
            $client_to_restore . $path_to_restore .
            ' fileset="' . $this->restoreNamespace->FileSet . '" ' .	$cmd_date_before .
            ' file=<"' . $list . '" ';
        $cmd .= ' done yes';
        //var_dump($cmd); exit; // !!!debug!!!
        $astatusdir = $director->execDirector(
" <<EOF
$cmd
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
        $this->renderScript('restorejob/run-restore.phtml');
    }


    /**
     * Delete temporary table after starting the job to restore
     * (Удалить временные таблицы после запуска задания на восстановление)
     *
     */
    function deleteTmpTablesAction()
    {
		$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
        $tmp_tables->deleteAllTmpTables();
        // удаляем данные сессии
        $this->restoreNamespace->unsetAll();
        Zend_Session::forgetMe();
        // переадресуем на страницу, где показываются запущенные задания
        $this->_redirect('job/running');
    }


    /**
     * Cancel Restore
     * (Отменить восстановление)
     */
    function cancelRestoreAction()
    {
		$tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
        $tmp_tables->deleteAllTmpTables();
        // удаляем данные сессии
        $this->restoreNamespace->unsetAll();
        Zend_Session::forgetMe();
        // goto home (переадресуем на главную страницу)
        $this->_redirect('index');
    }


    /**
     * Cancel Restore Recent
     */
    function cancelRestoreRecentAction()
    {
        $tmp_tables = new WbTmpTable(self::_PREFIX, $this->restoreNamespace->JobHash);
        $tmp_tables->deleteAllTmpTables();
        // удаляем данные сессии
        $this->restoreNamespace->unsetAll();
        Zend_Session::forgetMe();
        // goto home (переадресуем на главную страницу)
        $this->_redirect('index');
    }



}
