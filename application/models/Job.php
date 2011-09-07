<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuri Timofeev tim4dev@gmail.com
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
    const BEGIN_LIST = '/^======.*/'; // признак начала списка (не всегда есть)
    const END_LIST   = '/^====$/';   // признак конца списка (присутствует всегда)

    const RINNING_JOBS    = '/Running Jobs:/';        // начало списка запущенных заданий
    const NO_JOBS_RUNNING =  '/No Jobs running\./';   // нет запущенных заданий

    const SCHEDULED_JOBS    = '/Scheduled Jobs:/';     // начало списка запланированных заданий
    const NO_SCHEDULED_JOBS = '/No Scheduled Jobs\./';  // нет запланированных заданий

    const EMPTY_RESULT = 'EMPTY_RESULT';     // если ничего не найдено

    public $db;
    public $db_adapter;
    protected $bacula_acl; // bacula acl



    public function __construct($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->bacula_acl = new MyClass_BaculaAcl();
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
    	$select->from('Job', array('JobId', 'Name'));
    	$select->where("JobId = ?", $jobid);
    	$select->limit(1);
        $stmt = $select->query();
        // do Bacula ACLs
        $res = $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'name', 'job');
        return ( empty ($res) ) ? FALSE : TRUE;
	}



	/**
	 * Get data about last terminated Jobs (executed in last 24 hours)
	 * See also http://www.bacula.org/manuals/en/developers/developers/Database_Tables.html
	 *
	 */
    function getTerminatedJobs()
    {
        $select = new Zend_Db_Select($this->db);
        //$select->distinct();
        $last1day = date('Y-m-d H:i:s', time() - 86400); // для совместимости со старыми версиями mysql: NOW() - INTERVAL 1 DAY

        switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)',
                    'Reviewed'
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
                    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
                    'DurationTime' => '(EndTime - StartTime)',
                    'Reviewed'
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
                        'type' => 'Type',
			'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))",
			'reviewed'=>'Reviewed'
		));
		$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
		break;
        }

        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
        $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
        $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');
        /*
         * developers/Database_Tables.html
C   Created but not yet running
R   Running
B   Blocked
T   Terminated normally
W   Terminated normally with warnings
E   Terminated in Error
e   Non-fatal error
f   Fatal error
D   Verify Differences
A   Canceled by the user
I   Incomplete Job
F   Waiting on the File daemon
S   Waiting on the Storage daemon
m   Waiting for a new Volume to be mounted
M   Waiting for a Mount
s   Waiting for Storage resource
j   Waiting for Job resource
c   Waiting for Client resource
d   Wating for Maximum jobs
t   Waiting for Start Time
p   Waiting for higher priority job to finish
i   Doing batch insert file records
a   SD despooling attributes
l   Doing data despooling
L   Committing data (last despool)
         */
        $select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W', 'D')");
        $select->where("j.EndTime > ?", $last1day);
        $select->order(array("StartTime", "JobId"));
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
    }

    

    /**
     * Get data about running Jobs
     * from DB Catalog
     */
    function getRunningJobs()
    {
    	$select = new Zend_Db_Select($this->db);
    	$select->distinct();
        /*
         * developers/Database_Tables.html
C   Created but not yet running
R   Running
B   Blocked
T   Terminated normally
W   Terminated normally with warnings
E   Terminated in Error
e   Non-fatal error
f   Fatal error
D   Verify Differences
A   Canceled by the user
I   Incomplete Job
F   Waiting on the File daemon
S   Waiting on the Storage daemon
m   Waiting for a new Volume to be mounted
M   Waiting for a Mount
s   Waiting for Storage resource
j   Waiting for Job resource
c   Waiting for Client resource
d   Wating for Maximum jobs
t   Waiting for Start Time
p   Waiting for higher priority job to finish
i   Doing batch insert file records
a   SD despooling attributes
l   Doing data despooling
L   Committing data (last despool)
         */
        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
        	$last7day = date('Y-m-d H:i:s', time() - 604800);
            $select->from(array('j' => 'Job'),
    		  array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
    		  'StartTime' => "j.StartTime", 'EndTime'   => "j.EndTime",
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => 'TIMEDIFF(NOW(), StartTime)'
    		));
	    	$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
        	$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
	       	$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
    		$select->where("(j.EndTime = 0) OR (j.EndTime IS NULL) OR ".
                "(j.JobStatus IN ('C','R','B','e','F','S','m','M','s','j','c','d','t','p','i','a','l','L'))");
	        $select->where("j.StartTime > ?", $last7day);
			break;
    	case 'PDO_PGSQL':
            // PostgreSQL
            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
            $select->from(array('j' => 'Job'),
    		  array('JobId', 'JobName' => 'Name', 'Level', 'ClientId',
    		  'StartTime' => "j.StartTime", 'EndTime'   => "j.EndTime",
    		  'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
        	   'FileSetId', 'PurgedFiles', 'JobStatus',
        	   'DurationTime' => '(NOW() - StartTime)'
    		));
	    	$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
        	$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
	       	$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
   			$select->where("(j.EndTime IS NULL) OR ".
                "(j.JobStatus IN ('C','R','B','e','F','S','m','M','s','j','c','d','t','p','i','a','l','L'))");
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
				'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed' => 'Reviewed', 'poolid'=>'PoolId',
				'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
				'DurationTime' => "(strftime('%H:%M:%S',strftime('%s','now') - strftime('%s',StartTime),'unixepoch'))"
			));
			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
			$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
			$select->joinLeft(array('p' => 'Pool'), 'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
			$select->where("(datetime(j.EndTime) IS NULL) OR ".
                "(j.JobStatus IN ('C','R','B','e','F','S','m','M','s','j','c','d','t','p','i','a','l','L'))");
			$select->where("j.StartTime > datetime('now','-7 days')");
			break;
        }
    	$select->order(array("StartTime", "JobId"));
    	//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
		$stmt = $select->query();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
    }


    
    /**
	 * Running Jobs (from Director)
	 * Для дополнительной информации. Т.к. информация из БД Каталога не всегда корректна.
     *
     * === Замеченные баги ===
     * 1. Running Jobs - вместо (разделитель м/д полями - пробел) :
     *          24 Increme  job.name.test.4  2010-11-08_21.56.48_39 running
     *      Bacula может выдать (разделитель м/д полями - точка) :
     *          24 Increme  job.name.test.4.2010-11-08_21.56.48_39 running
	 */
    function getDirRunningJobs()
    {
    	$config = Zend_Registry::get('config');

    	// check access to bconsole
    	if ( !file_exists($config->general->bacula->bconsole))	{
    		$aresult[] = 'NOFOUND';
    		return $aresult;
    	}

    	$bconsolecmd = '';
        if ( isset($config->general->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->general->bacula->sudo . ' ' . $config->general->bacula->bconsole . ' ' .
                    $config->general->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->general->bacula->bconsole . ' ' . $config->general->bacula->bconsolecmd;
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
			if ( isset($command_output) ) {
			    $aresult[] = 'Command: <br>' . $bconsolecmd . '<br> output:<b>';
			} else {
			    $aresult[] = "Command: no output.<br />Check access to<br /><b>$bconsolecmd</b>";
			}
			foreach ($command_output as $line) {
				$aresult[] = $line;
			}
			$aresult[] = '</b>';
    		return $aresult;
    	}

    	// parsing Director output
        $begin1 = $begin2 = $begin3 = FALSE;
        $aresult = array();

        $i = 0;
        foreach ($command_output as $line) {
            $line = trim($line);
            // пустая строка
            if ( $line == '' )
                continue;
            // нет запланированных заданий - выход в любом случае
            if ( preg_match(self::NO_JOBS_RUNNING, $line) === 1 )  {
                $aresult = null;
                break;
            }
            // начало списка запланированных заданий
            if ( (!$begin1) && (!$begin2) && (!$begin3) && ( preg_match(self::RINNING_JOBS, $line) === 1) )  {
                $begin1 = TRUE;
                continue;
            }
            if ( $begin1 && (!$begin2) && (!$begin3) && ( preg_match('/^JobId /', $line) === 1) )  {
                $begin2 = TRUE;
                continue;
            }
            if ( $begin1 && $begin2 && (!$begin3) && ( preg_match(self::BEGIN_LIST, $line) === 1) )  {
                $begin3 = TRUE;
                continue;
            }
            // конец списка
            if ( $begin1 && $begin2 && $begin3 && ( preg_match(self::END_LIST, $line) == 1) )
                break;

            // парсим - разделитель - пробел :
            // 0=JobId  1=Level  2=Name  3=Status
            if ( $begin1 && $begin2 && $begin3 )  {
                // парсим
                $acols = preg_split("/[\s]+/", $line, 4, PREG_SPLIT_NO_EMPTY);
                $count = count($acols);
                if ( $count == 4 ) {
                    $aresult[$i]['id']     = $acols[0];
                    $aresult[$i]['level']  = $acols[1];
                    $aresult[$i]['name']   = $acols[2];
                    $aresult[$i]['status'] = $acols[3];
                } else {
                    // неверно пропарсилось (изменения в bacula ?)
                    $aresult = null;
                    break;
                }
                $i++;
            }
        }
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $aresult, 'name', 'job');
    }




    /**
	 * Return % free space in VolumeName
	 *
	 * @param string $name
	 * @return -1 error, -100 new volume
	 */
	function getFreeVolumeCapacity($name)
	{
		Zend_Loader::loadClass('Media');
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
    function getScheduledJobs()
    {
    	$config = Zend_Registry::get('config');
    	// check access to bconsole
    	if ( !file_exists($config->general->bacula->bconsole))	{
    		$aresult[] = 'NOFOUND';
    		return $aresult;
    	}

    	$bconsolecmd = '';
        if ( isset($config->general->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->general->bacula->sudo . ' ' . $config->general->bacula->bconsole .
                    ' ' . $config->general->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->general->bacula->bconsole . ' ' . $config->general->bacula->bconsolecmd;
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
			if ( isset($command_output) ) {
			    $aresult[] = 'Command: <br>' . $bconsolecmd . '<br> output:<b>';
			} else {
			    $aresult[] = "Command: no output.<br />Check access to<br /><b>$bconsolecmd</b>";
			}
			foreach ($command_output as $line) {
				$aresult[] = $line;
			}
			$aresult[] = '</b>';
    		return $aresult;
    	}

        // parse Director output
        $begin1 = $begin2 = $begin3 = FALSE;
        $aresult = array();

        $i = 0;
        foreach ($command_output as $line) {
            $line = trim($line);
            // пустая строка
            if ( $line == '' )
                continue;
            // нет запланированных заданий - выход в любом случае
            if ( preg_match(self::NO_SCHEDULED_JOBS, $line) === 1 )  {
                $aresult = null;
                break;
            }
            // начало списка запланированных заданий
            if ( (!$begin1) && (!$begin2) && (!$begin3) && ( preg_match(self::SCHEDULED_JOBS, $line) === 1) )  {
                $begin1 = TRUE;
                continue;
            }
            if ( $begin1 && (!$begin2) && (!$begin3) && ( preg_match('/^Level /', $line) === 1) )  {
                $begin2 = TRUE;
                continue;
            }
            if ( $begin1 && $begin2 && (!$begin3) && ( preg_match(self::BEGIN_LIST, $line) === 1) )  {
                $begin3 = TRUE;
                continue;
            }
            // конец списка
            if ( $begin1 && $begin2 && $begin3 && ( preg_match(self::END_LIST, $line) == 1) )
                break;

            // парсим - разделитель - пробел :
            // 0=Level    1=Type   2=Pri  3=Scheduled(date) 4=(time)   5=Name  6=Volume
            if ( $begin1 && $begin2 && $begin3 )  {
                // пробуем парсить
                $acols = preg_split("/[\s]+/", $line, -1, PREG_SPLIT_NO_EMPTY);
                $count = count($acols);
                $aresult[$i]['level'] = $acols[0];
                $aresult[$i]['type']  = $acols[1];
                $aresult[$i]['pri']   = $acols[2];
                $aresult[$i]['date']  = $acols[3] . ' ' . $acols[4];
                $aresult[$i]['vol']   = $acols[ $count-1 ];  // в имени Volume пробелов быть не может, поэтому последнее поле - точно Volume
                if ( $aresult[$i]['vol'] == '*unknown*')
                    $aresult[$i]['volfree'] = 'UNKNOWN_VOLUME_CAPACITY';
                else
                    $aresult[$i]['volfree'] = $this->getFreeVolumeCapacity($aresult[$i]['vol']);
                if ( $count == 7 ) {
                    // в имени Job нет пробелов
                    $aresult[$i]['name']  = $acols[5];
                } else {
                    // в имени Job есть пробелы (в имени Volume пробелов быть не может)
                    $aresult[$i]['name']  = $acols[5];
                    for ($j = 6; $j <= $count-2; $j++) {
                        $aresult[$i]['name']  .= ' ' . $acols[$j];
                    }
                }
                $i++;
            }
        }
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $aresult, 'name', 'job');
    }

    

    /**
     * Jobs with errors/problems (last NN days)
     *
     */
    function getProblemJobs($last_days)
    {
        $select = new Zend_Db_Select($this->db);
        $select->distinct();

        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
            $select->from(array('j' => 'Job'),
               array('JobId', 'JobName' => 'Name', 'Level', 'ClientId', 'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'
            ));
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'=>'JobStatusLong'));
            break;
        case 'PDO_PGSQL':
            // PostgreSQL
            $select->from(array('j' => 'Job'),
                array('JobId', 'JobName' => 'Name', 'Level', 'ClientId', 'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus', 'Type',
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
                    'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
                    'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
                    'type' => 'Type',
                    'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
            ));
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong'=>'JobStatusLong'));
            break;
        }
        $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
        $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
        $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', array('fileset'=>'FileSet'));

        $last7day = date('Y-m-d H:i:s', time() - $last_days * 86400); // для совместимости
        $select->where("((j.JobErrors > 0) OR (j.JobStatus IN ('E','e','f','I','D')))");
        $select->where("j.EndTime > ?", $last7day);
        $select->where("j.Reviewed = 0");
        $select->order(array("StartTime", "JobId"));

        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
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

    	/* Parsing Director's output.
         * Example :
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
				// parsing
				list($number, $name_job) = preg_split("/:+/", $line, 2);
				if ( !empty($name_job))
                    $aresult[]['jobname']  = trim($name_job);
				continue;
			}
			// задания закончились
			if ( ($start == 1) && ( !(strpos($line, $str_end) === FALSE) ) )
				break;

			if ( $start == 1 ) {
			    // parsing
                list($number, $name_job) = preg_split("/:+/", $line, 2);
                if ( !empty($name_job))
                    $aresult[]['jobname']  = trim($name_job);
			}
			else
				continue;
		}
        // do Bacula ACLs
        $res2dim = $this->bacula_acl->doBaculaAcl( $aresult, 'jobname', 'job');
        /*
         * convert two dimensional $res2dim to one dimension array $res1dim
         * для корректного отображения в форме нужен ординарный массив
         */
        $res1dim = array();
        foreach($res2dim as $res2) {
            $res1dim[] = $res2['jobname'];
        }
    	return $res1dim;
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
   				    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
       			    'FileSetId', 'PurgedFiles', 'JobStatus',
       			    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
   				    'StartTime', 'EndTime',
   				    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
       			    'FileSetId', 'PurgedFiles', 'JobStatus',
       			    'DurationTime' => '(EndTime - StartTime)'));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				$select->from(array('j' => 'Job'),
					array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
					'StartTime', 'EndTime',
					'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
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
   				$select->where("j.JobStatus  IN ('E', 'e', 'f', 'I', 'D')");
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
            //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // !!!debug
            $stmt = $select->query();
            // do Bacula ACLs
            return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
   		}
	}



    /**
     * Search Job by JobId
     * @param integer $jobid
     * @return array of Jobs
     */
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
                    'StartTimeRaw' => 'j.StartTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)'));
                break;
            case 'PDO_SQLITE':
                // SQLite3 Documentation
                // http://sqlite.org/lang_datefunc.html
                // workaround of bug http://framework.zend.com/issues/browse/ZF-884
                $select->from(array('j' => 'Job'),
                    array('jobid'=>'JobId', 'type'=>'Type', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
                    'starttime'=>'StartTime', 'endtime'=>'EndTime',
                    'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles',
                    'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
                    'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
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
            // do Bacula ACLs
            return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
        }
    }



    /**
     * Search Job by Job Name
     * @param string $jobname
     * @return array of Jobs
     */
    function getByJobName($jobname)
    {
        if ( isset($jobname) )	{
        $select = new Zend_Db_Select($this->db);
        $select->distinct();

        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
                    'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
                    'StartTimeRaw' => 'j.StartTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)'));
                break;
            case 'PDO_SQLITE':
                // SQLite3 Documentation
                // http://sqlite.org/lang_datefunc.html
                // workaround of bug http://framework.zend.com/issues/browse/ZF-884
                $select->from(array('j' => 'Job'),
                    array('jobid'=>'JobId', 'type'=>'Type', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
                    'starttime'=>'StartTime', 'endtime'=>'EndTime',
                    'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles',
                    'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
                    'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
                    'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))" ));
                break;
            }
            $select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong'));
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', array('FileSet'));
            $select->where("j.Name = '$jobname'");
            $select->order(array("StartTime", "JobId"));
            //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
            $stmt = $select->query();
            // do Bacula ACLs
            return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
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
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
           			'FileSetId', 'PurgedFiles', 'JobStatus',
           			'DurationTime' => 'TIMEDIFF(EndTime, StartTime)' ));
    			$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
                break;
            case 'PDO_PGSQL':
            // PostgreSQL
            // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
            	$select->from(array('j' => 'Job'),
					array('j.JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)' ));
				$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('JobStatusLong' => 'JobStatusLong'));
                break;
            case 'PDO_SQLITE':
				// workaround of bug http://framework.zend.com/issues/browse/ZF-884
				$select->from(array('j' => 'Job'),
					array('jobid'=>'JobId', 'job'=>'Job', 'JobName'=>'Name', 'level'=>'Level', 'clientid'=>'ClientId',
					'starttime'=>'StartTime', 'endtime'=>'EndTime', 'schedtime'=>'SchedTime',
					'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles',
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus', 'type'=>'Type',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
				break;
            }
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId');
            $select->joinLeft(array('o' => 'JobMedia'), 'j.JobId = o.JobId', array('JobId'));
            $select->joinLeft(array('m' => 'Media'), 'm.MediaId = o.MediaId', array('MediaId'));

			$select->where("m.VolumeName = '$volname'");
   			$select->order(array("StartTime", "j.JobId"));
  			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
   			$stmt = $select->query();
            // do Bacula ACLs
            return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
   		}
	}

    

	function getDetailByJobId($jobid)
	{
    	if ( $this->isJobIdExists($jobid) )	{
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
        		    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)', 'PriorJobId',
                    'Reviewed', 'Comment'
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
        		    'DurationTime' => '(EndTime - StartTime)', 'PriorJobId',
                    'Reviewed', 'Comment'
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
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))",
                    'priorjobid'=>'PriorJobId', 'reviewed'=>'Reviewed', 'comment'=>'Comment'
				));
				$select->joinLeft(array('s' => 'Status'), 'j.JobStatus = s.JobStatus', array('jobstatuslong' => 'JobStatusLong'));
				break;
            }
            $select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId',
                array('ClientName' => 'Name', 'ClientUName' => 'UName'));
            $select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId', array('PoolName' => 'Name'));
            $select->joinLeft(array('f' => 'FileSet'), 'j.FileSetId = f.FileSetId', 
                    array('FileSetName' => 'FileSet', 'FileSetCreateTime' => 'CreateTime'));
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
			        'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
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
			        'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
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
					'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
					'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
					'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"
				));
				$select->joinLeft(array('c' => 'Client'), 'j.ClientId = c.ClientId', array('ClientName' => 'Name'));
				$select->joinLeft(array('p' => 'Pool'),	'j.PoolId = p.PoolId',       array('PoolName' => 'Name'));
				$select->joinInner(array('s' => 'Status'), "j.JobStatus = s.JobStatus" , array('jobstatuslong'=>'JobStatusLong'));
			break;
        }
		$select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W', 'D')");
		$select->where("j.Type = 'B'");
   		$select->order(array("sortStartTime DESC"));
   		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // !!!debug
   		$stmt = $select->query();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
    }


    /**
     * Make WHERE
     *
     * @param $field
     * @param $mask
     * @param $type_search      [ordinary | like | regexp]
     * @return SQL WHERE statement
     */
    function myMakeWhere($field, $mask, $type_search)
    {
        $mask = $this->db->quoteInto("?", $mask);
        switch ($type_search) {
        case 'like':
            switch ($this->db_adapter) {
                case 'PDO_MYSQL':
                    return "($field LIKE   $mask)";
                    break;
                case 'PDO_PGSQL':
                    return "($field LIKE   $mask)";
                    break;
                case 'PDO_SQLITE':
                    return "($field LIKE   $mask)";
                    break;
            }
            break;
        case 'regexp':
            switch ($this->db_adapter) {
                case 'PDO_MYSQL':
                    return "($field REGEXP $mask)";
                    break;
                case 'PDO_PGSQL':
                    return "($field ~ $mask)";
                    break;
                case 'PDO_SQLITE':
                    return "($field LIKE  $mask)"; //regexp not implemented by default
                    break;
            }
            break;
        default: // ordinary
            return "($field = $mask)";
            break;
        }
    }



    /**
     * Find File(s) by Path/Name file
     *
     * @param $path     with trailing slash
     * @param $namefile
     * @param $client
     * @param $limit
     * @param $type_search      [ordinary | like | regexp]
     * @return rows
     */
    function getByFileName($path, $namefile, $client, $limit, $type_search)
    {
        if ( isset($namefile, $client) )  {
            $select = new Zend_Db_Select($this->db);
            $select->distinct();
            $select->limit($limit);

            switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $select->from(array('j' => 'Job'),
                array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime' => "DATE_FORMAT(j.StartTime, '%y-%b-%d %H:%i')",
                    'EndTime'   => "DATE_FORMAT(j.EndTime,   '%y-%b-%d %H:%i')",
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => 'TIMEDIFF(EndTime, StartTime)'));
                $select->joinLeft('File', 'j.JobId = File.JobId', array('File.JobId', 'File.FileId'));
                $select->joinLeft('Filename', 'File.FilenameId = Filename.FilenameId', array('FileName' => 'Filename.Name'));
                $select->joinLeft('Path', 'File.PathId = Path.PathId', array('Path' => 'Path.Path'));
                $select->joinLeft('Status', 'j.JobStatus = Status.JobStatus', array('JobStatusLong' => 'Status.JobStatusLong'));
                $select->joinLeft('Client', 'j.ClientId = Client.ClientId',   array('ClientName' => 'Client.Name'));
                $select->joinLeft('Pool',	 'j.PoolId = Pool.PoolId',          array('PoolName' => 'Pool.Name'));
                $select->joinLeft('FileSet', 'j.FileSetId = FileSet.FileSetId', array('FileSet' => 'FileSet.FileSet'));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-datetime.html
                $select->from(array('j' => 'Job'),
                    array('JobId', 'Type', 'JobName' => 'Name', 'Level', 'ClientId',
                    'StartTime', 'EndTime',
                    'VolSessionId', 'VolSessionTime', 'JobFiles', 'JobBytes', 'JobErrors', 'Reviewed', 'PoolId',
                    'FileSetId', 'PurgedFiles', 'JobStatus',
                    'DurationTime' => '(EndTime - StartTime)'));
                $select->joinLeft('File', 'j.JobId = File.JobId', array('File.JobId', 'File.FileId'));
                $select->joinLeft('Filename', 'File.FilenameId = Filename.FilenameId', array('FileName' => 'Filename.Name'));
                $select->joinLeft('Path', 'File.PathId = Path.PathId', array('Path' => 'Path.Path'));
                $select->joinLeft('Status', 'j.JobStatus = Status.JobStatus', array('JobStatusLong' => 'Status.JobStatusLong'));
                $select->joinLeft('Client', 'j.ClientId = Client.ClientId',   array('ClientName' => 'Client.Name'));
                $select->joinLeft('Pool',	 'j.PoolId = Pool.PoolId',          array('PoolName' => 'Pool.Name'));
                $select->joinLeft('FileSet', 'j.FileSetId = FileSet.FileSetId', array('FileSet' => 'FileSet.FileSet'));
                break;
            case 'PDO_SQLITE':
                // SQLite3 Documentation
                // http://sqlite.org/lang_datefunc.html
                // workaround of bug http://framework.zend.com/issues/browse/ZF-884
                $select->from(array('j' => 'Job'),
                array('jobid'=>'JobId', 'type'=>'Type', 'JobName' => 'Name', 'level'=>'Level', 'clientid'=>'ClientId',
                    'starttime'=>'StartTime', 'endtime'=>'EndTime',
                    'volsessionid'=>'VolSessionId', 'volsessiontime'=>'VolSessionTime', 'jobfiles'=>'JobFiles',
                    'jobbytes'=>'JobBytes', 'joberrors'=>'JobErrors', 'reviewed'=>'Reviewed', 'poolid'=>'PoolId',
                    'filesetid'=>'FileSetId', 'purgedfiles'=>'PurgedFiles', 'jobstatus'=>'JobStatus',
                    'DurationTime' => "(strftime('%H:%M:%S',strftime('%s',EndTime) - strftime('%s',StartTime),'unixepoch'))"));
                $select->joinLeft('File', 'j.JobId = File.JobId', array('File.JobId', 'File.FileId'));
                $select->joinLeft('Filename', 'File.FilenameId = Filename.FilenameId', array('FileName' => 'Filename.Name'));
                $select->joinLeft('Path', 'File.PathId = Path.PathId', array('path' => 'Path.Path'));
                $select->joinLeft('Status', 'j.JobStatus = Status.JobStatus', array('jobstatuslong' => 'Status.JobStatusLong'));
                $select->joinLeft('Client', 'j.ClientId = Client.ClientId',   array('clientname' => 'Client.Name'));
                $select->joinLeft('Pool',   'j.PoolId = Pool.PoolId',          array('poolname' => 'Pool.Name'));
                $select->joinLeft('FileSet', 'j.FileSetId = FileSet.FileSetId', array('fileset' => 'FileSet.FileSet'));
                break;
            }
            // terminated jobs
            $select->where("j.JobStatus IN ('T', 'E', 'e', 'f', 'A', 'W')");

            if ( !empty($path) )    {
                $select->where(
                    $this->myMakeWhere('Path.Path', $path, $type_search));
            }

            $select->where(
                $this->myMakeWhere('Filename.Name', $namefile, $type_search));

            if ( !empty($client) )    {
                $select->where($this->db->quoteInto("Client.Name = ?", $client));
            }
            $select->order(array("StartTime"));
            //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        }
        $stmt = $select->query();
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
    }



    /**
     * with Bacula ACLs
     */
    function getJobBeforeDate($date_before, $client_id_from, $file_set)
    {
        /* Поиск JobId последнего Full бэкапа для заданных Client, Fileset, Date
         * cats/sql_cmds.c :: uar_last_full
         */
        $ajob_all = array();

        // level 'B' - Base job, see track ticket #90
        $sql =  "SELECT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
            " Job.StartTime, Media.VolumeName, JobMedia.StartFile, Job.VolSessionId,Job.VolSessionTime,
              Job.Name as jobname " .
            " FROM Client,Job,JobMedia,Media,FileSet
              WHERE Client.ClientId=$client_id_from" .
            " AND Job.ClientId=$client_id_from" .
            " $date_before".
            " AND Level IN ('F','B') AND JobStatus IN ('T','W') AND Type='B'" .
            " AND JobMedia.JobId=Job.JobId" .
            " AND Media.Enabled=1" .
            " AND JobMedia.MediaId=Media.MediaId" .
            " AND Job.FileSetId=FileSet.FileSetId" .
            " AND FileSet.FileSet='".$file_set."'" .
            " ORDER BY Job.JobTDate DESC" .
            " LIMIT 1";
        $stmt = $this->db->query($sql);
        // do Bacula ACLs
        $ajob_full = $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
        unset($stmt);

        if ( empty($ajob_full) )
            return;

        $ajob_all[] = $ajob_full[0]['jobid'];
        /* Поиск свежего Differential бэкапа, после Full бэкапа, если есть
         * cats/sql_cmds.c :: uar_dif
         */
        $sql = "SELECT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
            " Job.StartTime, Media.VolumeName, JobMedia.StartFile, Job.VolSessionId,Job.VolSessionTime,
              Job.Name as jobname " .
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
        $stmt = $this->db->query($sql);
        $ajob_diff = $stmt->fetchAll();
        unset($stmt);

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
                " Job.VolSessionId as volsessionid, Job.VolSessionTime as volsessiontime, Job.Name as jobname " .
                " FROM Job,JobMedia,Media,FileSet" .
                " WHERE Media.Enabled=1 " .
                " $jobtdate " .
                " $date_before" .
                " AND Job.ClientId=$client_id_from" .
                " AND JobMedia.JobId=Job.JobId" .
                " AND JobMedia.MediaId=Media.MediaId" .
                " AND Job.Level='I' AND JobStatus IN ('T','W') AND Type='B'" .
                " AND Job.FileSetId=FileSet.FileSetId" .
                " AND FileSet.FileSet='".$file_set."'".
                " ORDER BY Job.StartTime ASC";
            break;
        default: // mysql, postgresql
            $sql = "SELECT DISTINCT Job.JobId,Job.JobTDate,Job.ClientId, Job.Level,Job.JobFiles,Job.JobBytes," .
                " Job.StartTime, Media.VolumeName ,JobMedia.StartFile, Job.VolSessionId, Job.VolSessionTime,
                  Job.Name as jobname " .
                " FROM Job,JobMedia,Media,FileSet" .
                " WHERE Media.Enabled=1 " .
                " $jobtdate " .
                " $date_before" .
                " AND Job.ClientId=$client_id_from" .
                " AND JobMedia.JobId=Job.JobId" .
                " AND JobMedia.MediaId=Media.MediaId" .
                " AND Job.Level='I' AND JobStatus IN ('T','W') AND Type='B'" .
                " AND Job.FileSetId=FileSet.FileSetId" .
                " AND FileSet.FileSet='".$file_set."'".
                " ORDER BY Job.StartTime ASC";
                break;
        }
        $stmt = $this->db->query($sql);
        $ajob_inc = $stmt->fetchAll();
        unset($stmt);

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



    /**
     * Get records by FileId
     * @param $fileid
     * @return recordset
     */
    function getByFileId($fileid)
    {
        if ( empty($fileid) )
            return;
        $select = new Zend_Db_Select($this->db);
        $select->from(array('f' => 'File'), array('FileId', 'LStat'));
        $select->joinLeft(array('j' => 'Job'),  'j.JobId  = f.JobId',  array('JobId', 'jobname' => 'Name') );
        $select->joinLeft(array('p' => 'Path'), 'p.PathId = f.PathId', array('PathId', 'Path') );
        $select->joinLeft(array('n' => 'Filename'), 'n.FilenameId = f.FilenameId', array('Filename' => 'Name') );
        $select->where("f.FileId = ?", $fileid);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        // do Bacula ACLs
        //return $stmt->fetchAll();
        return $this->bacula_acl->doBaculaAcl( $stmt->fetchAll(), 'jobname', 'job');
    }



}
