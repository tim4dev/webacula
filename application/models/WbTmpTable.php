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
    // for names of tmp tables (для формирования имен временных таблиц)
    const _PREFIX = 'webacula_tmp_'; // только в нижнем регистре

    public $db_adapter;

    protected $jobidhash;

    protected $_name    = 'webacula_tmp_tablelist'; // list of temporary tables.
                                                   //список всех временных таблиц, имя только в нижнем регистре
    protected $_primary = 'tmpid';
    protected $ttl_restore_session = 3600; // time to live temp tables (1 hour)

    // names of tmp tables (имена временных таблиц)
    protected $tmp_file;
    protected $num_tmp_tables = 1; // count of tmp tables (кол-во временных таблиц)



    /**
     * @param string $prefix для формирования имен tmp таблиц
     * @param string $jobidHash хэш-индекс для массива jobid
     */
    public function __construct($jobidhash, $ttl_restore_session)
    {
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->jobidhash = $jobidhash;
        $this->ttl_restore_session = $ttl_restore_session;
        // формируем имена временных таблиц
        $this->tmp_file    = self::_PREFIX . 'file_'     . $this->jobidhash;
        $config['db']      = Zend_Registry::get('db_bacula'); // database
        $config['name']    = $this->_name;      // name table
        $config['primary'] = $this->_primary;   // primary key
        $config['sequence']= true;

        parent::__construct($config);

        // setup DB adapter
        $this->_db = Zend_Db_Table::getAdapter('db_bacula');
        // существует ли таблица ?
        try {
            $this->_db->query('SELECT tmpId FROM '. $this->_name .' LIMIT 1');
        } catch (Zend_Exception $e) {
            // создаем таблицу
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
     * Пометить файл для восстановления
     */
    function markFile($fileid)
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->tmp_file) . " SET isMarked=1 WHERE FileId=$fileid");
    }


    /**
     * Удалить пометку файла для восстановления
     */
    function unmarkFile($fileid)
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->tmp_file) . " SET isMarked=0 WHERE FileId=$fileid");
    }

    /**
     * Пометить/снять пометку
     * каталог + файлы в каталоге + подкаталоги + файлы в них для восстановления.
     *
     * @param string $pathid
     * @param integer $label 0 или 1
     *
     * @return array (path , dirs + files affected)
     */
    function markDir($path, $label)
    {
        // проверка $label
        if ( !is_numeric($label) )
            return null;
        // помечаем файлы для восстановления
        $query = 'UPDATE '. $this->_db->quoteIdentifier($this->tmp_file) .' SET isMarked = '.$label.
            ' WHERE FileId IN (
            SELECT f.FileId FROM File AS f
            INNER JOIN Path AS p
                ON f.PathId = p.PathId
            WHERE p.Path LIKE ' . $this->_db->quote($path.'%') . ')';
        $res = $this->_db->query($query);
        unset($query);
        unset($res);
        // подсчет статистики
        // кол-во файлов
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
     * Получить имя файла
     */
    function getFileName($fileid)
    {
        $stmt = $this->_db->query('SELECT n.Name AS name
            FROM Filename AS n, File AS f
            WHERE (f.FilenameId = n.FilenameId) AND (f.FileId = ' . $fileid . ') LIMIT 1');
        $res  = $stmt->fetchAll();
        return $res[0]['name'];
    }

    /**
     * Получить имя каталога
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
    function insertRowFile($jobid, $FileId, $FileIndex, $FileSize, $isMarked=0)
    {
        try {
            $this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->tmp_file) .
                " (FileId, FileIndex, isMarked, FileSize, JobId) " .
                " VALUES ($FileId, $FileIndex, $isMarked, $FileSize, $jobid)");
            return TRUE; // all ok
        } catch (Zend_Exception $e) {
            echo '<br><br>', __METHOD__,'<br>Caught exception: ', get_class($e), '<br>', 'Message: ', $e->getMessage(), '<br>';
            return FALSE;
        }
    }



    /**
     * Return TRUE if there table exists
     * Возвращает TRUE если таблица существует
     *
     * @param string $name
     */
    function isTmpTableExists($name)
    {
    	try {
   			$sql = "SELECT 1 FROM $name WHERE TRUE";
			$this->_db->query($sql);
 			return TRUE; // таблица существует
		} catch (Zend_Exception $e) {
    		//echo "Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "\n"; // !!! debug !!!
    		return FALSE; // таблицы не существует
		}
    }


    /**
     * Return TRUE if all temporary tables exists
     * Возвращает TRUE если все временные таблицы существуют
     *
     */
    function isAllTmpTablesExists()
    {
        if ( $this->isTmpTableExists($this->tmp_file) )   {
            if ( !$this->isCloneOk() )   {
                // есть какие-то таблицы, но до конца не скопированные, поэтому их нужно удалить
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
     * Успешно ли скопированы данные из БД bacula
     * Проверка поля wbTmpTable.tmpIsCloneOk
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
            return FALSE; // копирование неудачно
        else
            return TRUE; //копирование успешно
    }


    /**
     * Установка признака успешного клонирования таблиц bacula
     *
     */
    function setCloneOk()
    {
        $this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
            ' SET tmpIsCloneOk=1 WHERE tmpName IN (' . $this->_db->quote($this->tmp_file) . ')' );
    }


    /**
     * Создание временной таблицы File
     *
     * @return TRUE if all ok
     */
    function createTmpTable()
    {
        // удаляем старые таблицы с такими же именами
        $this->dropTmpTable($this->tmp_file);
        // сначала создаем записи о новых таблицах !!! порядок не менять
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
        // создаем таблицы !!! порядок не менять // see also cats/make_mysql_tables.in
        try {
            /*
             * File
             */
            // добавлены дополнительные поля : isMarked, FileSize
            switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $res_file = $this->_db->query("
                CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
                    JobId  BIGINT UNSIGNED NOT NULL,
                    FileId BIGINT UNSIGNED NOT NULL,
                    FileIndex INTEGER UNSIGNED DEFAULT 0,
                    isMarked INTEGER  UNSIGNED DEFAULT 0,
                    FileSize BIGINT  UNSIGNED DEFAULT 0,
                    PRIMARY KEY(FileId)
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
            // удаляем таблицы
            $this->dropTmpTable($this->tmp_file);
            return FALSE; // error
        }
    }


    /**
     * Drop all tmp tables
     * Удалить ВСЕ временные таблицы
     *
     */
    function deleteAllTmpTables()
    {
        $this->dropTmpTable($this->tmp_file);
    }


    /**
     * Удалить временную таблицу
     *
     */
    function dropTmpTable($name)
    {
        // сначала удаляем саму временную таблицу
        $this->_db->query("DROP TABLE IF EXISTS " . $this->_db->quoteIdentifier($name));
        // удаляем записи о временной таблице
        $where = $this->getAdapter()->quoteInto('tmpName = ?', $name);
        $this->delete($where);
    }


    /**
     * Удалить все старые временные таблицы, если timestamp старше TTL
     *
     */
    function dropOldTmpTables()
    {
        // drop old temp tables
        // получаем список временных таблиц, подлежащих уничтожению
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
     * @return TRUE if the tables are outdated (если таблицы устарели)
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
     * Возвращает общее кол-во файлов и их суммарный размер для восстановления
     *
     */
    public function getTotalSummaryMark()
    {
        // подсчет кол-ва файлов и каталогов
        $select = new Zend_Db_Select($this->_db);
        $select->from($this->tmp_file, array('total_files' => new Zend_Db_Expr(" COUNT(FileId)") ));
        $select->where("isMarked = 1");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $total_files = $this->_db->fetchOne($select);
        if ( !$total_files )
            $total_files = 0;
        $select->reset();
        unset($select);

        // расчет суммарного размера
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
     * Возвращает имя файла, куда будут выгружены записи для восстановления
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
        // clone File
        $stmt = $bacula->query(
            "SELECT FileId, FileIndex, LStat
            FROM File
            WHERE JobId = $jobid");

        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total
            // размер файла пишем в отдельное поле, чтобы потом легче было подсчитать общий объем
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
            $file_size = $decode->homebrewBase64($st_size);
            if ( !$this->insertRowFile($jobid, $line['fileid'], $line['fileindex'], $file_size) )
                return FALSE; // show exception from WbTmpTable.php->insertRowFile()
        }
        // end transaction
        // после успешного клонирования устанавливаем признак
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
                    SELECT max(FileId) as FileId, PathId, FilenameId
                    FROM (
                        SELECT FileId, PathId, FilenameId
                        FROM File
                        WHERE JobId IN ( $sjobids )
                        ORDER BY JobId DESC
                    ) AS F
                    GROUP BY PathId, FilenameId )
                AS Temp
                JOIN Filename ON (Filename.FilenameId = Temp.FilenameId)
                JOIN Path ON (Path.PathId = Temp.PathId)
                JOIN File ON (File.FileId = Temp.FileId)
                WHERE File.FileIndex > 0
                ORDER BY JobId, FileIndex ASC";
        $stmt = $bacula->query($sql);
        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total size
            // размер файла пишем в отдельное поле, чтобы потом легче было подсчитать общий объем
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
            $file_size = $decode->homebrewBase64($st_size);
            $this->insertRowFile($line['jobid'], $line['fileid'], $line['fileindex'], $file_size);
        }
        // end transaction // после успешного клонирования устанавливаем признак
        $this->setCloneOk();
        return TRUE;
    }



	/**
     * Для показа plain-списка файлов перед запуском задания на восстановление
     *
     */
    function getListToRestore($offset)
    {
        switch ($this->db_adapter) {
        case 'PDO_SQLITE':
            // bug http://framework.zend.com/issues/browse/ZF-884
            $sql = 'SELECT f.FileId as fileid, f.LStat as lstat, f.MD5 as md5, p.Path as path, n.Name as name
                FROM  ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' AS t
                LEFT JOIN File AS f
                    ON f.FileId=t.FileId
                LEFT JOIN Path AS p
                    ON f.PathId=p.PathId
                LEFT JOIN Filename AS n
                    ON f.FilenameId=n.FilenameId
                WHERE (t.isMarked = 1)
                ORDER BY p.Path ASC, n.Name ASC
                LIMIT '. self::ROW_LIMIT_FILES .' OFFSET '. $offset;
            break;
        default: // mysql, postgresql
            $sql = 'SELECT f.FileId, f.LStat, f.MD5, p.Path, n.Name
                FROM  ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' AS t
                LEFT JOIN File AS f
                    ON f.FileId=t.FileId
                LEFT JOIN Path AS p
                    ON f.PathId=p.PathId
                LEFT JOIN Filename AS n
                    ON f.FilenameId=n.FilenameId
                WHERE (t.isMarked = 1)
                ORDER BY p.Path ASC, n.Name ASC
                LIMIT '. self::ROW_LIMIT_FILES .' OFFSET '. $offset;
            break;
        }
        $stmt = $this->_db->query($sql);
        return $stmt->fetchAll();
    }


    public function getCountFile() {
        // подсчет кол-ва файлов
        $query = "SELECT count(*) as num FROM " . $this->_db->quoteIdentifier($this->tmp_file);
        $stmt   = $this->_db->query($query);
        $countf = $stmt->fetchAll();
        return $countf[0]['num'];
    }

    /*
     * For Bacula should leave only files that are to be restored
     * Для Bacula надо оставить только записи о файлах, которые будут восстанавливаться
     */
    public function prepareTmpTableForRestore() {
        $sql = 'DELETE FROM ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . ' '.
               'WHERE isMarked != 1';
        $stmt = $this->_db->query($sql);
    }


}
