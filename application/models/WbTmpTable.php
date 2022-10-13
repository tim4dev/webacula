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
 * Class for handle temporary tables for webacula
 *
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class WbTmpTable extends Zend_Db_Table
{
    // for pager
    const ROW_LIMIT_FILES = 500;
    // for names of tmp tables
    const _PREFIX = 'webacula_tmp_'; // only lowercase

    public $db_adapter;

    protected $jobidhash;

    protected $_name    = 'webacula_tmp_tablelist'; // list of temporary tables.
                                                    //list all temporary tables, the name of only lowercase
    protected $_primary = 'tmpid';
    protected $ttl_restore_session = 3600; // time to live temp tables (1 hour)

    // names of tmp tables
    protected $tmp_file;
    protected $num_tmp_tables = 1; // count of tmp tables

    private $insertarray = array();
    private $flushnumber = 200;


    /**
     * @param string $prefix To generate tmp table names
     * @param string $jobidHash Hash-index for the jobid array
     */
    public function __construct($jobidhash, $ttl_restore_session)
    {
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->jobidhash = $jobidhash;
        $this->ttl_restore_session = $ttl_restore_session;
        // generate a temp table name
        $this->tmp_file    = self::_PREFIX . 'file_'     . $this->jobidhash;
        $config['db']      = Zend_Registry::get('db_bacula'); // database
        $config['name']    = $this->_name;      // name table
        $config['primary'] = $this->_primary;   // primary key
        $config['sequence']= true;

        parent::__construct($config);

        // setup DB adapter
        $this->_db = Zend_Db_Table::getAdapter('db_bacula');
        // if table exists
        try {
            $this->_db->query('SELECT tmpId FROM '. $this->_name .' LIMIT 1');
        } catch (Zend_Exception $e) {
            // create table
            switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $sql = 'CREATE TABLE '. $this->_name .' (
                        tmpId    INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                        tmpName  CHAR(64) UNIQUE NOT NULL,
                        tmpJobIdHash CHAR(64) NOT NULL,
                        tmpCreate   TIMESTAMP NOT NULL,
                        tmpIsCloneOk INTEGER DEFAULT 0,
                        PRIMARY KEY(tmpId)
                        )';
                break;
            case 'PDO_PGSQL':
                $sql = 'CREATE TABLE '. $this->_name .' (
                        tmpId    SERIAL NOT NULL,
                        tmpName  CHAR(64) UNIQUE NOT NULL,
                        tmpJobIdHash CHAR(64) NOT NULL,
                        tmpCreate   timestamp without time zone NOT NULL,
                        tmpIsCloneOk SMALLINT DEFAULT 0,
                        PRIMARY KEY(tmpId))';
                break;
            case 'PDO_SQLITE':
                $sql = 'CREATE TABLE '. $this->_name .' (
                       tmpId    INTEGER,
                       tmpName  CHAR(64) UNIQUE NOT NULL,
                       tmpJobIdHash CHAR(64) NOT NULL,
                       tmpCreate   TIMESTAMP NOT NULL,
                       tmpIsCloneOk INTEGER DEFAULT 0,
                       PRIMARY KEY(tmpId))';
                break;
            }
            $this->_db->query($sql);
        }
    }


    protected function _setupPrimaryKey()
    {
        $this->_primary = 'tmpId';
        parent::_setupPrimaryKey();
    }

    function getDb()
    {
        return $this->_db;
    }

    function getTableNameFile()
    {
        return $this->tmp_file;
    }


    /**
     * Mark file for recovery
     */
    function markFile($fileid)
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->tmp_file) . " SET isMarked=1 WHERE FileId=$fileid");
    }


    /**
     * Remove file for recovery
     */
    function unmarkFile($fileid)
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->tmp_file) . " SET isMarked=0 WHERE FileId=$fileid");
    }

    /**
     * Mark / unmark
     * Show catalog files and subdirectories to recover
     *
     * @param string $pathid
     * @param integer $label 0 or 1
     *
     * @return array (path , dirs + files affected)
     */
    function markDir($path, $label)
    {
        // verification $label
        if ( !is_numeric($label) )
            return null;
        // mark the files to recover
        $query = 'UPDATE '. $this->_db->quoteIdentifier($this->tmp_file) .' SET isMarked = '.$label.
            ' WHERE FileId IN (
            SELECT f.FileId FROM File AS f
            INNER JOIN Path AS p
                ON f.PathId = p.PathId
            WHERE p.Path LIKE ' . $this->_db->quote($path.'%') . ')';
        $res = $this->_db->query($query);
        unset($query);
        unset($res);
        // statistics counting
        // number of files
        $query = "SELECT count(FileId) as countf FROM " . $this->_db->quoteIdentifier($this->tmp_file) .
                " WHERE  isMarked=1";
        $stmt   = $this->_db->query($query);
        $countf = $stmt->fetchAll();
        if ( isset($countf[0]['countf']) )
            $affected_files = $countf[0]['countf'];
        unset($query);
        unset($stmt);
        return array('path' => $path, 'files' => $affected_files);
    }



    /**
     * Get file name
     */
    function getFileName($fileid)
    {
        $stmt = $this->_db->query('SELECT f.Filename AS name
            FROM File f
            WHERE (f.FileId = ' . $fileid . ') LIMIT 1');
        $res  = $stmt->fetchAll();
        return $res[0]['name'];
    }

    /**
     * Get folder name
     */
    function getDirName($pathid)
    {
        $select = new Zend_Db_Select($this->_db);
        $select = $this->_db->select();
        $select->from(array('p' => 'Path'), 'Path');
        $select->where("p.PathId = ?", $pathid);
        $select->limit(1);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        $res  = $stmt->fetchAll();
        return $res[0]['path'];
    }


    /**
     * Fast INSERT INTO tmp_file_ VALUES ()
     */
    function insertRowFile($jobid, $FileId, $FileIndex, $FileSize, $isMarked=0, $flush=false)
    {
        if(!$flush){
          $this->insertarray[] = "($FileId, $FileIndex, $isMarked, $FileSize, $jobid)";
        }
        if($flush || sizeof($this->insertarray) > $this->flushnumber){
          try {
              $q = "INSERT INTO " . $this->_db->quoteIdentifier($this->tmp_file) .
                  " (FileId, FileIndex, isMarked, FileSize, JobId) " .
                  " VALUES ";
              $q = $q . implode(',', $this->insertarray);
              $this->insertarray = array();

              $this->_db->query($q);
              return TRUE; // all ok
          } catch (Zend_Exception $e) {
              echo '<br><br>', __METHOD__,'<br>Caught exception: ', get_class($e), '<br>', 'Message: ', $e->getMessage(), '<br>';
              return FALSE;
          }
        } else {
          return TRUE;
        }
    }



    /**
     * Return TRUE if there table exists
     *
     * @param string $name
     */
    function isTmpTableExists($name)
    {
    	try {
   			$sql = "SELECT 1 FROM $name WHERE TRUE";
			$this->_db->query($sql);
 			return TRUE; // table exist
		} catch (Zend_Exception $e) {
    		//echo "Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "\n"; // !!! debug !!!
    		return FALSE; // table does not exist
		}
    }


    /**
     * Return TRUE if all temporary tables exists
     *
     */
    function isAllTmpTablesExists()
    {
        if ( $this->isTmpTableExists($this->tmp_file) )   {
            if ( !$this->isCloneOk() )   {
                // There are some tables, but not completely copied, so they need to be removed
                $this->deleteAllTmpTables();
                return FALSE;
            }   else {
                return TRUE;
            }
        } else {
            return FALSE;
        }
    }


    function updateTimestamp()
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
            " SET tmpCreate=NOW() WHERE tmpName=" . $this->_db->quote($this->tmp_file));
    }


    /**
     * Data from the database if successfully copied bacula
     * Check field wbTmpTable.tmpIsCloneOk
     *
     */
    function isCloneOk()    {
        $select = new Zend_Db_Select($this->_db);
        $select->from($this->_name, array('isCloneOk' => new Zend_Db_Expr(" COUNT(tmpIsCloneOk)") ));
        $select->where('tmpName IN ('. $this->_db->quote($this->tmp_file) .')' );
        $select->where("tmpIsCloneOk > 0");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $isCloneOk = $this->_db->fetchOne($select);
        if ( $isCloneOk < $this->num_tmp_tables )
            return FALSE; //failed
        else
            return TRUE; //successfully
    }


    /**
     * Setting the hash of a successful cloning bacula tables
     *
     */
    function setCloneOk()
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
            ' SET tmpIsCloneOk=1 WHERE tmpName IN (' . $this->_db->quote($this->tmp_file) . ')' );
    }


    /**
     * Create a temporary table
     *
     * @return TRUE if all ok
     */
    function createTmpTable()
    {
        // delete the old table with the same name
        $this->dropTmpTable($this->tmp_file);
        // first create a new record of the tables !!! order not to change
        switch ($this->db_adapter) {
            case 'PDO_SQLITE':
                $this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
                    " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_file) . ", " .
                    $this->_db->quote($this->jobidhash) . ', ' . " datetime('now') )" );
            break;
            default: // mysql, postgresql
                $this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
                    " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_file) . ", " .
                    $this->_db->quote($this->jobidhash) . ', ' . ' NOW() )' );
            break;
        }
        // Create a table !!! order not to change // see also cats/make_mysql_tables.in
        try {
            /*
             * File
             */
            // added additional fields : isMarked, FileSize
            switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $res_file = $this->_db->query("
                CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
                    JobId  BIGINT UNSIGNED NOT NULL,
                    FileId BIGINT UNSIGNED NOT NULL,
                    FileIndex INTEGER UNSIGNED DEFAULT 0,
                    isMarked INTEGER  UNSIGNED DEFAULT 0,
                    FileSize BIGINT  UNSIGNED DEFAULT 0,
                    PRIMARY KEY(FileId),  
                    KEY idx_fileindex (FileIndex)
                )");
                break;
            case 'PDO_PGSQL':
                $res_file = $this->_db->query("
                CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
                    JobId  BIGINT NOT NULL,
                    FileId BIGINT NOT NULL,
                    fileindex integer not null  default 0,
                    isMarked SMALLINT  DEFAULT 0,
                    FileSize BIGINT  DEFAULT 0,
                    PRIMARY KEY(FileId)
                )");
                break;
            case 'PDO_SQLITE':
                $res_file = $this->_db->query("
                CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
                    JobId  INTEGER,
                    FileId INTEGER,
                    FileIndex INTEGER UNSIGNED NOT NULL,
                    isMarked INTEGER  UNSIGNED DEFAULT 0,
                    FileSize INTEGER  UNSIGNED DEFAULT 0,
                    PRIMARY KEY(FileId)
                )");
                break;
            }
            return TRUE; // all ok
        } catch (Zend_Exception $e) {
            echo '<br><br><br>', __METHOD__, '<br>Caught exception: ', get_class($e),
                '<br>Message: ', $e->getMessage(), '<br>';
            // delete table
            $this->dropTmpTable($this->tmp_file);
            return FALSE; // error
        }
    }


    /**
     * Delete all tmp tables
     *
     */
    function deleteAllTmpTables()
    {
        $this->dropTmpTable($this->tmp_file);
    }


    /**
     * Drop all tmp tables
     *
     */
    function dropTmpTable($name)
    {
        // first remove itself a temporary table
        $this->_db->query("DROP TABLE IF EXISTS " . $this->_db->quoteIdentifier($name));
        // remove the temporary table entry
        $where = $this->getAdapter()->quoteInto('tmpName = ?', $name);
        $this->delete($where);
    }


    /**
     * Remove all the old temporary table if timestamp older TTL
     *
     */
    function dropOldTmpTables()
    {
        // drop old temp tables
        // get a list of temporary tables to be destroyed
        $select = new Zend_Db_Select($this->_db);
        $select = $this->_db->select();
        $select->from($this->_name, array('tmpName', 'tmpCreate'));
        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
            $select->where("(NOW() - tmpCreate) > ?", $this->ttl_restore_session);
            break;
        case 'PDO_PGSQL':
            $select->where("EXTRACT(SECOND FROM (NOW() - tmpCreate) ) > ?", $this->ttl_restore_session);
            break;
        case 'PDO_SQLITE':
            $select->where("(strftime('%H:%M:%S',strftime('%s','now') - strftime('%s',tmpCreate),'unixepoch')) > ?", $this->ttl_restore_session);
            break;
        }
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt   = $select->query();
        $tmp_table = $stmt->fetchAll();
        //echo "<pre>"; print_r($tmp_table); exit; // for !!!debug!!!
        $select->reset();
        unset($select);
        foreach($tmp_table as $line)	{
            $this->dropTmpTable($line['tmpname']);
        }
    }


    /**
     * @return TRUE if the tables are outdated (If the tables are outdated)
     *
     */
    function isOldTmpTables()
    {
        $select = new Zend_Db_Select($this->_db);
        $select = $this->_db->select();
        $select->from($this->_name, array('total_rows' => new Zend_Db_Expr(" COUNT(tmpId)") ));
        switch ($this->db_adapter) {
        case 'PDO_MYSQL':
            $select->where("(NOW() - tmpCreate) > ?", $this->ttl_restore_session);
            break;
        case 'PDO_PGSQL':
            $select->where("EXTRACT(SECOND FROM (NOW() - tmpCreate) ) > ?", $this->ttl_restore_session);
            break;
        case 'PDO_SQLITE':
            $select->where("(strftime('%H:%M:%S',strftime('%s','now') - strftime('%s',tmpCreate),'unixepoch')) > ?", $this->ttl_restore_session);
            break;
        }
        $select->where("tmpJobIdHash = ?", $this->jobidhash);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        // calculate total rows and pages. thanks to http://groups.google.com/group/ru-zend-framework?hl=ru
        $total_rows = $this->_db->fetchOne($select);
        return ($total_rows >= $this->num_tmp_tables);
    }


    /**
     * Returns the total number of files and their total size for recovery
     *
     */
    public function getTotalSummaryMark()
    {
        // counting of number of files and directories
        $select = new Zend_Db_Select($this->_db);
        $select->from($this->tmp_file, array('total_files' => new Zend_Db_Expr(" COUNT(FileId)") ));
        $select->where("isMarked = 1");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $total_files = $this->_db->fetchOne($select);
        if ( !$total_files )
            $total_files = 0;
        $select->reset();
        unset($select);

        // calculation of the total size
        $select = new Zend_Db_Select($this->_db);
    	$select->from($this->tmp_file, array('total_size' => new Zend_Db_Expr(" SUM(FileSize)") ));
	    $select->where("isMarked = 1");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $total_size = $this->_db->fetchOne($select);
        if ( !$total_size )
            $total_size = 0;

        return array('total_files' => $total_files, 'total_size' => $total_size);
    }


    /*
     * Returns the name of the file where the recording will be uploaded to restore
     */
    public function getFilenameToExportMarkFiles() {
        return "webacula_restore_" . $this->jobidhash . ".tmp";
    }


    /**
     * Clone Bacula table : File
     *
     * @return TRUE if ok
     */
    function cloneBaculaToTmp($jobid)
    {
        $bacula = Zend_Registry::get('db_bacula');
        // create temporary tables: File, Filename, Path. создаем временные таблицы File, Filename, Path
        if ( !$this->createTmpTable() )
            return FALSE; // view exception from WbTmpTable.php->createTmpTables()
        $decode = new MyClass_HomebrewBase64;
        // clone File (Don't get files that FileIndex = 0 that are deleted) jobs - accurate = yes
        $stmt = $bacula->query(
            "SELECT FileId, FileIndex, LStat
            FROM File
            WHERE JobId = $jobid and FileIndex <> 0");

        $bacula->beginTransaction();
        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
            $file_size = $decode->homebrewBase64($st_size);
            if ( !$this->insertRowFile($jobid, $line['fileid'], $line['fileindex'], $file_size) )
                return FALSE; // show exception from WbTmpTable.php->insertRowFile()
        }
        // end transaction
        $this->insertRowFile(null, null, null, null, null, true);
        $bacula->commit();
        // after the successful cloning feature set
        $this->setCloneOk();
        return TRUE;
    }


    /**
     * Clone Bacula tables : 'File' for Restore Recent Backup
     *
     * @return TRUE if ok
     */
    function cloneRecentBaculaToTmp($sjobids)
    {
        $bacula = Zend_Registry::get('db_bacula');
        $this->createTmpTable();
        $decode = new MyClass_HomebrewBase64;
        // clone File
        // dird/ua_restore.c :: build_directory_tree
        $sql = "SELECT File.JobId, File.FileId, File.FileIndex, File.LStat
                FROM (
                    SELECT max(FileId) as FileId, PathId
                    FROM (
                        SELECT FileId, PathId
                        FROM File
                        WHERE JobId IN ( $sjobids )
                        ORDER BY JobId DESC
                    ) AS F
                    GROUP BY PathId, FileId )
                AS Temp
                JOIN Path ON (Path.PathId = Temp.PathId)
                JOIN File ON (File.FileId = Temp.FileId)
                WHERE File.FileIndex > 0
                ORDER BY JobId, FileIndex ASC";
       // $sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $bacula->query($sql);
        $bacula->beginTransaction();
        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total size
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
            $file_size = $decode->homebrewBase64($st_size);
            $this->insertRowFile($line['jobid'], $line['fileid'], $line['fileindex'], $file_size);
        }
        $this->insertRowFile(null, null, null, null, null, true);
        // end transaction // After successful cloning, we set the sign
        $bacula->commit();
        $this->setCloneOk();
        return TRUE;
    }



     /**
     * To display the plain-file list before starting the restoration job
     *
     */
    function getListToRestore($offset)
    {
        switch ($this->db_adapter) {
        case 'PDO_SQLITE':
            // bug http://framework.zend.com/issues/browse/ZF-884
            $sql = 'SELECT f.FileId as fileid, f.LStat as lstat, f.MD5 as md5, p.Path as path, f.Filename as name
                FROM  ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' AS t
                LEFT JOIN File AS f
                    ON f.FileId=t.FileId
                LEFT JOIN Path AS p
                    ON f.PathId=p.PathId
                WHERE (t.isMarked = 1)
                ORDER BY p.Path ASC, f.Filename ASC
                LIMIT '. self::ROW_LIMIT_FILES .' OFFSET '. $offset;
            break;
        default: // mysql, postgresql
            $sql = 'SELECT f.FileId, f.LStat, f.MD5, p.Path, f.Filename as name
                FROM  ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' AS t
                LEFT JOIN File AS f
                    ON f.FileId=t.FileId
                LEFT JOIN Path AS p
                    ON f.PathId=p.PathId
                WHERE (t.isMarked = 1)
                ORDER BY p.Path ASC, f.Filename ASC
                LIMIT '. self::ROW_LIMIT_FILES .' OFFSET '. $offset;
            break;
        }
        $stmt = $this->_db->query($sql);
        return $stmt->fetchAll();
    }


    public function getCountFile() {
        // Count number of files
        $query = "SELECT count(*) as num FROM " . $this->_db->quoteIdentifier($this->tmp_file);
        $stmt   = $this->_db->query($query);
        $countf = $stmt->fetchAll();
        return $countf[0]['num'];
    }

    /*
     * For Bacula should leave only files that are to be restored
     */
    public function prepareTmpTableForRestore() {
        $sql = 'DELETE FROM ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' '.
               'WHERE isMarked != 1';
        $stmt = $this->_db->query($sql);
    }


}
