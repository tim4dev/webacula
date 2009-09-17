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

class Job extends Zend_Db_Table
{
	public $db;
    public $db_adapter;

	public function __construct($config = array())
	{
		$this->db         = Zend_Registry::get('db_bacula');
	    $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
	    parent::__construct($config);
	}

	protected function _setupTableName()
    {
        switch ($this->db_adapter) {
        case 'PDO_PGSQL':
            $this->_name = 'job';
            break;
		default: // including mysql, sqlite
			$this->_name = 'Job'; 
        }       
        parent::_setupTableName();
    }

    protected function _setupPrimaryKey()
    {
        $this->_primary = 'jobid';
        parent::_setupPrimaryKey();
    }


	/**
	 * If there JobId exist in the database Bacula ...
	 * Существует ли JobId в БД Bacula
	 *
	 * @return TRUE if exist
	 * @param integer $jobid
	 */
	function isJobIdExists($jobid)
	{
   		$select = new Zend_Db_Select($this->db);
    	$select->from('Job', 'JobId');
    	$select->where("JobId = ?", $jobid);
    	$select->limit(1);
    	$res = $this->db->fetchOne($select);	
		if ( $res )	{
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	 * Get data about last terminated Jobs (executed in last 24 hours)
	 * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
	 *
	 */
    function GetLastJobs()
    {
    	$select = new Zend_Db_Select($this->db);
    	$select->distinct();
    	$last1day = date('Y-m-d H:i:s', time() - 86400); // для совместимости со старыми версиями mysql: NOW() - INTERVAL 1 DAY

        switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'
                ));        	
                $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'=>'JobStatusLong'));
        	break;
            case 'PDO_PGSQL':
            // PostgreSQL
            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)'
                ));
                $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'=>'JobStatusLong'));
            break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				// bug http://framework.zend.com/issues/browse/ZF-884
				// http://sqlite.org/pragma.html
				//$res = $db->query('PRAGMA short_column_names=1'); // not affected
				//$res = $db->query('PRAGMA full_column_names=0'); // not affected
				$select->from(array('j' => 'Job'),
					array('jobid'=>'JobId', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
					'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
			break;
        }

        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
        $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
        $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');
        /*
         *  C 	Created but not yet running
			R 	Running
			B 	Blocked
			T 	Terminated normally
			E 	Terminated in Error
			e 	Non-fatal error
			f 	Fatal error
			D 	Verify Differences
			A 	Canceled by the user
			
			F 	Waiting on the File daemon
			S 	Waiting on the Storage daemon
			m 	Waiting for a new Volume to be mounted
			M 	Waiting for a Mount
			s 	Waiting for Storage resource
			j 	Waiting for Job resource
			c 	Waiting for Client resource
			d 	Wating for Maximum jobs
			t 	Waiting for Start Time
			p 	Waiting for higher priority job to finish
			W	?undocumented? see cats/sql_cmds.c -- List last 20 Jobs
         */
        $select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W')");
        $select->where("j.EndTime > ?", $last1day);
        $select->order(array("StartTime", "JobId"));

    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    	$result = $select->query();
		return $result;
    }


    /**
     * Get data about running Jobs
     * from DB Catalog
     */
    function GetRunningJobs()
    {
    	$select = new Zend_Db_Select($this->db);
    	$select->distinct();

        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
        	$last7day = date('Y-m-d H:i:s', time() - 604800);
            $select->from(array('j' => 'Job'),
    		  array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
    		  'StartTime' => "j.StartTime", 'EndTime'   => "j.EndTime",
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => 'TIMEDIFF(NOW(), StartTime)'
    		));
	    	$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
        	$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
	       	$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
    		$select->where("(j.EndTime = 0) OR (j.EndTime IS NULL) OR (j.JobStatus IN ('C','R','B','e','D','F','S','m','M','s','j','c','d','p'))");
	        $select->where("j.StartTime > ?", $last7day);
			break;
    	case 'PDO_PGSQL':
            // PostgreSQL
            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
            $select->from(array('j' => 'Job'),
    		  array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
    		  'StartTime' => "j.StartTime", 'EndTime'   => "j.EndTime",
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => '(NOW() - StartTime)'
    		));
	    	$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
        	$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
	       	$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
   			$select->where("(j.EndTime IS NULL) OR (j.JobStatus IN ('C','R','B','e','D','F','S','m','M','s','j','c','d','p'))");
    		$select->where("j.StartTime > ( NOW() - INTERVAL '7 days' )");
            break;
		case 'PDO_SQLITE':
			// SQLite3 Documentation
			// http://sqlite.org/lang_datefunc.html
			// bug http://framework.zend.com/issues/browse/ZF-884
			// http://sqlite.org/pragma.html
			//$res = $db->query('PRAGMA short_column_names=1'); // not affected
			//$res = $db->query('PRAGMA full_column_names=0'); // not affected
			$select->from(array('j' => 'Job'),
				array('jobid'=>'JobId', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
				'starttime' => "j.StartTime", 'endtime'   => "j.EndTime",
				'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
				'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
				'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
				'DurationTime' => "(strftime('%H:%M:%S',strftime('%s','now') - strftime('%s',StartTime),'unixepoch'))"
			));
			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
			$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
			$select->joinLeft(array('p' => 'Pool'), 'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
			$select->where("(datetime(j.EndTime) IS NULL) OR (j.JobStatus IN ('C','R','B','e','D','F','S','m','M','s','j','c','d','p'))");
			$select->where("j.StartTime > datetime('now','-7 days')");
			break;
        }

    	$select->order(array("StartTime", "JobId"));

    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    	$result = $select->query();
		return $result;
    }


    /**
	 * Running Jobs (from Director)
	 * Для дополнительной информации. Т.к. информация из БД Каталога не всегда корректна.
	 */
    function GetDirRunningJobs()
    {
    	$config = Zend_Registry::get('config');

    	// check access to bconsole
    	if ( !file_exists($config->bacula->bconsole))	{
    		$aresult[] = 'NOFOUND';
    		return $aresult;
    	}

    	$bconsolecmd = '';
        if ( isset($config->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        }

    	// run bconsole
    	$command_output = '';
    	$return_var = 0;
    	exec($bconsolecmd . ' <<EOF
status director
quit
EOF', $command_output, $return_var);

    	//echo "<pre>"; print_r($command_output); echo "</pre>"; // !!! DEBUG !!!

    	// check return status of the executed command
    	if ( $return_var != 0 )	{
			$aresult[] = 'ERROR';
			if ( isset($cmd)) {
			    $aresult[] = 'Command: <br>' . $cmd . '<br> output:<b>';
			} else {
			    $aresult[] = "Command: no output.<br />Check access to<br /><b>$bconsolecmd</b>";
			}
			foreach ($command_output as $line) {
				$aresult[] = $line;
			}
			$aresult[] = '</b>';
    		return $aresult;
    	}

    	// parsing output
    	$str_rj = 'Running Jobs';
    	$str_no = 'No Jobs running.'; // признак того, что нет запущенных заданий
    	$str_start = '====='; // признак начала списка
    	$str_end   = '====';  // признак конца списка
    	$i = 0;
		$fl_str_rj    = 0;
		$fl_str_start = 0;
    	$aresult = array();
    	foreach ($command_output as $line) {
			if ( strlen($line) == 0 )
				continue;

			if ( ($fl_str_rj == 0) && !(strpos($line, $str_rj) === FALSE) )  {
				$fl_str_rj = 1;
				continue;
			}

			if ( ($fl_str_rj == 1) && ($fl_str_start == 0) && !(strpos($line, $str_start) === FALSE) )  {
				$fl_str_start = 1;
				continue;
			}

			// нет запланированных заданий
			if ( strpos($line, $str_no) === TRUE )  {
				$aresult[] = 'NOJOBS';
				break;
			}

			// задания закончились
			if ( ($fl_str_rj == 1) && ($line === $str_end) )
				break;

			//echo '<pre>$fl_str_rj=', $fl_str_rj, ' $fl_str_start=', $fl_str_start, '<br>',$line; //!!! debug
			// парсим
			if ( ( $fl_str_rj == 1 ) && ( $fl_str_start == 1 ) ) {
			    // clean spaces
				$line = trim($line);
				$line = preg_replace("/[ \t\n\r\f\v]+/", ' ', $line);
            //echo "<pre>", $line; echo "</pre>"; // !!! debug
				list($id, $level, $name, $status) = explode(' ', $line, 4);

				$aresult[$i]['id']     = $id;
				$aresult[$i]['level']  = $level;
				$aresult[$i]['name']   = $name;
				$aresult[$i]['status'] = $status;

				$i++;
			}
		}
      //echo "<pre>"; print_r($aresult); exit; // !!! debug
		return $aresult;
    }




    /**
	 * Return % free space in VolumeName
	 *
	 * @param string $name
	 * @return -1 error, -100 new volume
	 */
	function getFreeVolumeCapacity($name)
	{
		$table = new Media();
		$where  = $table->getAdapter()->quoteInto('VolumeName = ?', trim($name));
		$row = $table->fetchRow($where);

    	if ( !isset($row) )
				return Zend_Registry::get('NEW_VOLUME');
			else	{
				if ( $row->maxvolbytes != 0 )
					return( floor(100 - ($row->volbytes * 100 / $row->maxvolbytes)) );
				else
				    // tape storage may be maxvolbytes == 0
					return Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
			}
	}


    /**
	 * Scheduled Jobs (at 24 hours forward)
	 *
	 */
    function GetNextJobs()
    {
    	$config = Zend_Registry::get('config');

    	// check access to bconsole
    	if ( !file_exists($config->bacula->bconsole))	{
    		$aresult[] = 'NOFOUND';
    		return $aresult;
    	}

    	$bconsolecmd = '';
        if ( isset($config->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        }

    	// run bconsole
    	$command_output = '';
    	$return_var = 0;
    	exec($bconsolecmd . ' <<EOF
status director
quit
EOF', $command_output, $return_var);

    	//echo "<pre>"; print_r($command_output); echo "</pre>"; // !!! DEBUG !!!

    	// check return status of the executed command
    	if ( $return_var != 0 )	{
			$aresult[] = 'ERROR';
			if ( isset($cmd)) {
			    $aresult[] = 'Command: <br>' . $cmd . '<br> output:<b>';
			} else {
			    $aresult[] = "Command: no output.<br />Check access to<br /><b>$bconsolecmd</b>";
			}
			foreach ($command_output as $line) {
				$aresult[] = $line;
			}
			$aresult[] = '</b>';
    		return $aresult;
    	}

    	// parsing output
    	$strs = 'Scheduled Jobs:';
    	$str_no = 'No Scheduled Jobs.'; // признак того, что нет запланированных заданий
    	$str_end = '===='; // признак конца списка
    	$i = $start = 0;
    	$omit_count = 2; // кол-во строк после $strs, которые нужно пропустить
    	$aresult = array();
    	foreach ($command_output as $line) {

			if ( strlen($line) == 0 )
				continue;

			if ( ($start == 0) && (!(strpos($line, $strs) === FALSE)) )  {
				$start = 1;
				continue;
			}

			// нет запланированных заданий
			if ( strpos($line, $str_no) === TRUE )  {
			    $aresult[] = 'NOJOBS';
				break;
			}

			// задания закончились
			if ( ($start == 1) && ($line === $str_end) )
				break;

			// парсим 7-мь полей
			// Level    Type   Pri  Scheduled(date) (time)   Name  Volume
			if ( ( $start == 1 ) && ( $omit_count <= 0 ) ) {
				$atmp = preg_split("/[\s]+/", $line);
				if ( count($atmp) == 7 ) {
					// вывод Director правильно пропарсился и в имени Job нет пробелов
					$aresult[$i]['parseok'] = true;
				} else {
					// неверно пропарсилось или в имени Job есть пробелы
					// see also bug#2797123 https://sourceforge.net/tracker/index.php?func=detail&aid=2797123&group_id=201199&atid=976599
					$aresult[$i]['parseok'] = false;
				}			
				list($level, $type, $pri, $sched_date, $sched_time, $name, $vol) = preg_split("/[\s]+/", $line, 7);												
			} elseif ( $start == 1 ) {
			    // пропуск строк
			    --$omit_count;
			    continue;
			}

			if ( $start == 0 )
				continue;

			$aresult[$i]['name']  = $name;
			$aresult[$i]['level'] = $level;
			$aresult[$i]['type']  = $type;
			$aresult[$i]['pri']   = $pri;
			$aresult[$i]['date']  = "$sched_date $sched_time";
			$aresult[$i]['vol']   = $vol;
			if ( $aresult[$i]['parseok'] ) {
				// если нет ошибок при парсинге
				$aresult[$i]['volfree'] = $this->getFreeVolumeCapacity($vol);
			} else {
				// если ошибки при парсинге, то имя тома (и свободное место в т.ч.) неизвестно
				$aresult[$i]['volfree'] = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
			}

			$i++;
		}
		return $aresult;
    }


    /**
     * Jobs with errors/problems (last 14 days)
     *
     */
    function GetProblemJobs()
    {
    	$select = new Zend_Db_Select($this->db);
    	$select->distinct();

        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
    	   $select->from(array('j' => 'Job'),
	           array('JobId', 'JobName' => 'Name', 'Level', 'ClientId', 'StartTime', 'EndTime',
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'
    	   ));
    	   $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'=>'JobStatusLong'));
    	   break;
    	case 'PDO_PGSQL':
            // PostgreSQL
            $select->from(array('j' => 'Job'),
	           array('JobId', 'JobName' => 'Name', 'Level', 'ClientId', 'StartTime', 'EndTime',
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => '(EndTime - StartTime)'
    	    	));
    	    $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'=>'JobStatusLong'));
            break;
        case 'PDO_SQLITE':
			// SQLite3 Documentation
			// http://sqlite.org/lang_datefunc.html
			// bug http://framework.zend.com/issues/browse/ZF-884
			// http://sqlite.org/pragma.html
			//$res = $db->query('PRAGMA short_column_names=1'); // not affected
			//$res = $db->query('PRAGMA full_column_names=0'); // not affected
			$select->from(array('j' => 'Job'),
				array('jobid' => 'JobId', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId', 
				'starttime'=>'StartTime', 'endtime'=>'EndTime',
				'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
				'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
				'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
				'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
            	));
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong'=>'JobStatusLong'));
			break;
        }
    	$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
        $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
        $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', array('fileset'=>'FileSet'));

    	$last7day = date('Y-m-d H:i:s', time() - 604800); // для совместимости со старыми версиями mysql: NOW() - INTERVAL 7 DAY
        $select->where("((j.JobErrors > 0) OR (j.JobStatus IN ('E','e', 'f')))");
        $select->where("j.EndTime > ?", $last7day);
    	$select->order(array("StartTime", "JobId"));

    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    	$result = $select->query();    	
		return $result;
    }


   /**
	 * Get Listing All Jobs
	 *
	 */
    function getListJobs()
    {
    	$director = new Director();
    	// check access to bconsole           
		if ( !$director->isFoundBconsole() )	{
			$aresult[] = 'ERROR: bconsole not found.';
    		return $aresult;
   	    }
		$astatusdir = $director->execDirector(
"<<EOF
run
.
@quit
EOF"
		); 
        // check return status of the executed command
        if ( $astatusdir['return_var'] != 0 )	{
			$aresult[] = 'ERROR';
			$aresult[] = 'bconsole output:<b>';
			foreach ($astatusdir['command_output'] as $line) {
				$aresult[] = $line;
			}
			$aresult[] = '</b>';
    		return $aresult;
		}

    	// parsing Director's output. Example :
    	/*
The defined Job resources are:
     1: restore.files
     2: job.name.test.1
     3: job name test 2
Select Job resource (1-3):
    	 */
    	$strs = 'The defined Job resources are:';
    	$str_end = 'Select Job resource'; // признак конца списка
    	$start = 0;
    	$aresult = array();
    	foreach ($astatusdir['command_output'] as $line) {
			if ( strlen($line) == 0 )
				continue;

			if ( ($start == 0) && (!(strpos($line, $strs) === FALSE)) )  {
				$start = 1;
				$aresult[]  = $line;
				continue;
			}

			// задания закончились
			if ( ($start == 1) && ( !(strpos($line, $str_end) === FALSE) ) )
				break;

			if ( $start == 1 ) 
				$aresult[]  = $line;
			else															
				continue;
		}
    	return $aresult;
    }


	function getSelectFilteredJob($date_begin, $time_begin, $date_end, $time_end,
								$client, $fileset, $jlevel, $jstatus, $jtype, $volname,
								$orderp)
	{
		if ( isset($date_begin, $time_begin, $date_end, $time_end, $client, $fileset) )	{
   			$select = new Zend_Db_Select($this->db);

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

			if ( $orderp ) {
   		    	$order = array($orderp . ' ASC', 'JobId');
   			} else {
   		    	$order = array('JobId');
   			}
   		
			$select->order($order);
			return $select;
   		}
	}


	function getByJobId($jobid)
	{
		if ( isset($jobid) )	{  			
   			$select = new Zend_Db_Select($this->db);
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
			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

   			$stmt = $select->query();
			return $stmt->fetchAll();
   		}
	}



	function getByVolumeName($volname)
	{
		if ( isset($volname) )	{
   			$select = new Zend_Db_Select($this->db);
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
  			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
   			$stmt = $select->query();
			return $stmt->fetchAll();
   		}
	}
	
	
	function getDetailByJobId($jobid)
	{
    	if ( isset($jobid) )	{   		
    		$select = new Zend_Db_Select($this->db);
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
                $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
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
    			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				// bug http://framework.zend.com/issues/browse/ZF-884
				// http://sqlite.org/pragma.html
				//$res = $db->query('PRAGMA short_column_names=1'); // not affected
				//$res = $db->query('PRAGMA full_column_names=0'); // not affected
				$select->from(array('j' => 'Job'),
					array('jobid'=>'JobId', 'job'=>'Job', 'name'=>'Name', 'level'=>'Level', 'clientid'=>'ClientId',
					'starttime'=>'StartTime', 'endtime'=>'EndTime', 'schedtime'=>'SchedTime',
					'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus', 'type'=>'Type',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
				break;
            }
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId',
                array('ClientName' => 'Name', 'ClientUName' => 'UName'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');
        	$select->where("j.JobId = ?", $jobid);
    		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$aresult['job'] = $stmt->fetchAll();

			$select->reset();
			unset($select);
			unset($stmt);

			// list volumes
			$select = new Zend_Db_Select($this->db);
			switch ($this->db_adapter) {
			case 'PDO_SQLITE':
				// bug http://framework.zend.com/issues/browse/ZF-884
				$select->distinct();
    			$select->from(array('j' => 'JobMedia'),	array('mediaid'=>'MediaId'));
	    		$select->joinInner(array('m' => 'Media'), 'j.MediaId = m.MediaId', array('volumename'=>'VolumeName'));
        		$select->where("j.JobId = ?", $jobid);
				break;
			default: // mysql, postgresql
				$select->distinct();
    			$select->from(array('j' => 'JobMedia'),	array('MediaId'));
	    		$select->joinInner(array('m' => 'Media'), 'j.MediaId = m.MediaId', array('VolumeName'));
        		$select->where("j.JobId = ?", $jobid);
			}
        	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

        	$stmt = $select->query();
			$aresult['volume'] = $stmt->fetchAll();

			$select->reset();
			unset($select);
			unset($stmt);
			return $aresult;
    	}		
	}



	function getLastJobRun($numjob)
	{
		$select = new Zend_Db_Select($this->db);
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
   		        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
				$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId',       array('PoolName' => 'Name'));
				$select->joinInner(array('s' => 'Status'), "j.JobStatus = s.JobStatus" , array('JobStatusLong'));
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
   		        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
				$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId',       array('PoolName' => 'Name'));
				$select->joinInner(array('s' => 'Status'), "j.JobStatus = s.JobStatus" , array('JobStatusLong'));
            break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				// bug http://framework.zend.com/issues/browse/ZF-884
				$select->from(array('j' => 'Job'),
					array('jobid'=>'JobId', 'type'=>'Type', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
					'sortStartTime' => 'StartTime',
					'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
				$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId',       array('PoolName' => 'Name'));
				$select->joinInner(array('s' => 'Status'), "j.JobStatus = s.JobStatus" , array('jobstatuslong'=>'JobStatusLong'));
			break;
        }
		$select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W')");
		$select->where("j.Type = 'B'");
   		$select->order(array("sortStartTime DESC"));
   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!DEBUG!!!

   		$stmt = $select->query();
		return $stmt->fetchAll();
	}


	function getByFileName($namefile, $client, $limit)
	{
		if ( isset($namefile, $client) )	{
   			$select = new Zend_Db_Select($this->db);
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
				// bug http://framework.zend.com/issues/browse/ZF-884
				$select->from(array('j' => 'Job'),
					array('jobid'=>'JobId', 'type'=>'Type', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
					'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles', 
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
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
			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
   		}
   		$stmt = $select->query();
		return $stmt->fetchAll();
	}
	
	
	
	function getJobBeforeDate($date_before, $client_id_from, $file_set)
	{
		/* Поиск JobId последнего Full бэкапа для заданных Client, Fileset, Date
		 * cats/sql_cmds.c :: uar_last_full 
		 */
		$ajob_all = array();
		
		$sql =  "SELECT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
					" Job.StartTime,Media.VolumeName,JobMedia.StartFile, Job.VolSessionId,Job.VolSessionTime" .
				" FROM Client,Job,JobMedia,Media,FileSet WHERE Client.ClientId=$client_id_from" .
					" AND Job.ClientId=$client_id_from" .
					" $date_before".
					" AND Level='F' AND JobStatus IN ('T','W') AND Type='B'" .
					" AND JobMedia.JobId=Job.JobId" .
					" AND Media.Enabled=1" .
					" AND JobMedia.MediaId=Media.MediaId" .
					" AND Job.FileSetId=FileSet.FileSetId" .
					" AND FileSet.FileSet='".$file_set."'" .
				" ORDER BY Job.JobTDate DESC" .
				" LIMIT 1";
		//var_dump($sql); exit; // for !!!debug!!!
		$stmt = $this->db->query($sql);
		$ajob_full = $stmt->fetchAll();
		unset($stmt);
		//var_dump($ajob_full); exit; // for !!!debug!!!
		
		if ( !$ajob_full ) {
			return; 
		}
		$ajob_all[] = $ajob_full[0]['jobid'];
		/* Поиск свежего Differential бэкапа, после Full бэкапа, если есть
		 * cats/sql_cmds.c :: uar_dif
		 */
		$sql = "SELECT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
					" Job.StartTime,Media.VolumeName,JobMedia.StartFile, Job.VolSessionId,Job.VolSessionTime" .
				" FROM Job,JobMedia,Media,FileSet" .
				" WHERE Job.JobTDate>'".$ajob_full[0]['jobtdate']."'" .
					" $date_before" .
					" AND Job.ClientId=$client_id_from" .
					" AND JobMedia.JobId=Job.JobId" .
					" AND Media.Enabled=1" .
					" AND JobMedia.MediaId=Media.MediaId" .
					" AND Job.Level='D' AND JobStatus IN ('T','W') AND Type='B'" .
					" AND Job.FileSetId=FileSet.FileSetId" .
					" AND FileSet.FileSet='".$file_set."'" .
					" ORDER BY Job.JobTDate DESC" .
					" LIMIT 1";
		//var_dump($sql); exit; // for !!!debug!!!
		$stmt = $this->db->query($sql);
		$ajob_diff = $stmt->fetchAll();
		unset($stmt);
		//var_dump($ajob_diff); exit; // for !!!debug!!!
		
		if ( $ajob_diff ) {
			$ajob_all[] .= $ajob_diff[0]['jobid'];
		} 
		/* Поиск свежих Incremental бэкапов, после Full или Differential бэкапов, если есть
		 * cats/sql_cmds.c :: uar_inc
		 */
		if ( !empty($ajob_diff[0]['jobtdate']) ) {
			$jobtdate = " AND Job.JobTDate>'" . $ajob_diff[0]['jobtdate'] . "'";
		} else {
			$jobtdate = " AND Job.JobTDate>'" . $ajob_full[0]['jobtdate'] . "'";
		}
		switch ($this->db_adapter) {
        	case 'PDO_SQLITE':
				// bug http://framework.zend.com/issues/browse/ZF-884
				$sql = "SELECT DISTINCT Job.JobId as jobid, Job.JobTDate as jobtdate, Job.ClientId as clientid, " .
					" Job.Level as level, Job.JobFiles as jobfiles, Job.JobBytes as jobbytes, " .
					" Job.StartTime as starttime, Media.VolumeName as volumename, JobMedia.StartFile as startfile, " .
					" Job.VolSessionId as volsessionid, Job.VolSessionTime as volsessiontime" .
				" FROM Job,JobMedia,Media,FileSet" .
				" WHERE Media.Enabled=1 " .
					" $jobtdate " .
					" $date_before" .
					" AND Job.ClientId=$client_id_from" .
					" AND JobMedia.JobId=Job.JobId" .
					" AND JobMedia.MediaId=Media.MediaId" .
					" AND Job.Level='I' AND JobStatus IN ('T','W') AND Type='B'" .
					" AND Job.FileSetId=FileSet.FileSetId" .
					" AND FileSet.FileSet='".$file_set."'";
				break;
			default: // mysql, postgresql
				$sql = "SELECT DISTINCT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
					" Job.StartTime,Media.VolumeName,JobMedia.StartFile, Job.VolSessionId,Job.VolSessionTime" .
				" FROM Job,JobMedia,Media,FileSet" .
				" WHERE Media.Enabled=1 " .
					" $jobtdate " .
					" $date_before" .
					" AND Job.ClientId=$client_id_from" .
					" AND JobMedia.JobId=Job.JobId" .
					" AND JobMedia.MediaId=Media.MediaId" .
					" AND Job.Level='I' AND JobStatus IN ('T','W') AND Type='B'" .
					" AND Job.FileSetId=FileSet.FileSetId" .
					" AND FileSet.FileSet='".$file_set."'";
				break;
		}			
		//echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
   		$stmt = $this->db->query($sql);
  		$ajob_inc = $stmt->fetchAll();
		unset($stmt);
		//var_dump($ajob_inc); exit; // for !!!debug!!!
		
		// формируем хэш из jobids
		if ( empty($ajob_diff) ) {
   			$hash = '' . $ajob_full[0]['jobid'];
		} else {
   			$hash = '' . $ajob_full[0]['jobid'] . $ajob_diff[0]['jobid'];
		}
   		foreach ($ajob_inc as $line) {
   			$hash = $hash . $line['jobid'];
   			$ajob_all[] = $line['jobid'];
		}
		return(array('ajob_full' => $ajob_full, 'ajob_diff' => $ajob_diff, 'ajob_inc' => $ajob_inc, 
			'ajob_all' => $ajob_all, 'hash' => $hash));	
	}
	
	

}
