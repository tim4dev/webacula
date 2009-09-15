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
    public $db_adapter;

	public function __construct($config = array())
	{
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
	 * Get data about last terminated Jobs (executed in last 24 hours)
	 * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
	 *
	 */
    function GetLastJobs()
    {
    	$db = Zend_Registry::get('db_bacula');
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);

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
    	$db = Zend_Registry::get('db_bacula');
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);

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
    	$db = Zend_Registry::get('db_bacula');
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);

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
    	$config = Zend_Registry::get('config');

    	// check access to bconsole
    	if ( !file_exists($config->bacula->bconsole))	{
    		$aresult[] = 'ERROR: bconsole not found.';
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
    	exec($bconsolecmd . ' <<EOF
run
.
@quit
EOF', $command_output, $return_var);

    	//echo "<pre>"; print_r($command_output); echo "</pre>"; // !!! DEBUG !!!

    	// check return status of the executed command
    	if ( $return_var != 0 )	{
			$aresult[] = 'ERROR';
			$aresult[] = 'Command: <br>' . $bconsolecmd . '<br> output:<b>';
			foreach ($command_output as $line) {
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
    	foreach ($command_output as $line) {
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


}
