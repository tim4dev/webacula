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
 * Class for handle temporary tables for DB webacula
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
    const _PREFIX = '_'; // только в нижнем регистре

    public $db_adapter;

    protected $jobidhash;

    protected $_name    = 'wbtmptablelist'; // список всех временных таблиц, имя только в нижнем регистре
    protected $_primary = 'tmpid';
    protected $ttl_restore_session = 3600; // time to live temp tables (1 hour)

    // names of tmp tables (имена временных таблиц)
    protected $tmp_file;
    protected $tmp_filename;
    protected $tmp_path;
    protected $num_tmp_tables = 3; // count of tmp tables (кол-во временных таблиц)

	protected $logger; // for debug




    /**
     * @param string $prefix для формирования имен tmp таблиц
     * @param string $jobidHash хэш-индекс для массива jobid
     */
    public function __construct($prefix, $jobidhash, $ttl_restore_session)
    {
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER_WEBACULA');
        $this->jobidhash = $jobidhash;
        $this->ttl_restore_session = $ttl_restore_session;
        // формируем имена временных таблиц
        $this->tmp_file     = $prefix . 'file_'     . $this->jobidhash;
        $this->tmp_filename = $prefix . 'filename_' . $this->jobidhash;
        $this->tmp_path     = $prefix . 'path_'     . $this->jobidhash;

        $config['db']      = Zend_Registry::get('db_webacula'); // database
        $config['name']    = $this->_name; 		// name table
        $config['primary'] = $this->_primary;   // primary key
        $config['sequence']= true;

        parent::__construct($config);

        // setup no default adapter
        $this->_db = Zend_Db_Table::getAdapter('db_webacula');
        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $this->_db->query('SET NAMES utf8');
                $this->_db->query('SET CHARACTER SET utf8');
                break;
            case 'PDO_PGSQL':
                $this->_db->query("SET NAMES 'UTF8'");
                break;
        }

        // for debug !!!
        /*Zend_Loader::loadClass('Zend_Log_Writer_Stream');
        Zend_Loader::loadClass('Zend_Log');
        $writer = new Zend_Log_Writer_Stream('/tmp/ajax.log');
        $this->logger = new Zend_Log($writer);
        $this->logger->log("debug on", Zend_Log::INFO);*/
    }

    protected function _setupTableName()
    {
        $this->_name = 'wbtmptablelist';
        parent::_setupTableName();
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

    function getTableNameFilename()
    {
    	return $this->tmp_filename;
    }

    function getTableNamePath()
    {
    	return $this->tmp_path;
    }

    /**
     * Залочить временные таблицы по записи
     */
    function lockTmpTablesW()
    {
        switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                $this->_db->query("LOCK TABLES " . $this->_db->quoteIdentifier($this->tmp_file) . ", " .
                    $this->_db->quoteIdentifier($this->tmp_filename) . ", " .
                    $this->_db->quoteIdentifier($this->tmp_path) . " WRITE;");
                break;
            case 'PDO_PGSQL':
                // TODO
                break;
        }
    }

    /**
     * Разлочить временные таблицы
     */
    function unlockTmpTables()
    {
    	switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
       			$this->_db->query("UNLOCK TABLES;");
       			break;
			case 'PDO_PGSQL':
				break;
    	}
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
		//$this->logger->log("markDir() input value:\n$path\n$label\n", Zend_Log::DEBUG);// !!! debug
        // проверка $label
        if ( !is_numeric($label) ) {
            return null;
        }

    	// сначала пометка файлов ! порядок не менять !
    	$query = "UPDATE " . $this->_db->quoteIdentifier($this->tmp_file) . " SET isMarked = $label
    			  WHERE PathId IN (
    			  	SELECT PathId FROM " . $this->_db->quoteIdentifier($this->tmp_path) .
    			  "	WHERE Path LIKE " . $this->_db->quote($path . "%") . ")";

    	//$this->logger->log($query, Zend_Log::DEBUG);// !!! debug
    	$res = $this->_db->query($query);
    	unset($query);
    	unset($res);

    	// пометка каталогов
    	$query = "UPDATE " . $this->_db->quoteIdentifier($this->tmp_path) . " SET isMarked = $label " .
    	         " WHERE Path LIKE " . $this->_db->quote($path . '%');
    	$res = $this->_db->query($query);

        //$this->logger->log($query, Zend_Log::DEBUG);// !!! debug
    	unset($query);
    	unset($res);

    	// подсчет статистики
    	// кол-во файлов
    	$query = "SELECT count(FileId) as countf FROM " . $this->_db->quoteIdentifier($this->tmp_file) .
    			  " WHERE PathId IN (
    			  	SELECT PathId FROM " . $this->_db->quoteIdentifier($this->tmp_path) .
    			  " WHERE Path LIKE " . $this->_db->quote($path . "%") . ")";
    	$stmt   = $this->_db->query($query);
    	$countf = $stmt->fetchAll();
    	$affected_files = $countf[0]['countf'];
    	unset($query);
    	unset($stmt);

        // кол-во каталогов
        $query = "SELECT count(PathId) as countd FROM " . $this->_db->quoteIdentifier($this->tmp_path) .
    	 	  	 " WHERE Path LIKE " . $this->_db->quote($path . "%");
    	$stmt   = $this->_db->query($query);
    	$countd = $stmt->fetchAll();
    	$affected_dirs = $countd[0]['countd'];
    	unset($query);
    	unset($stmt);

		//$this->logger->log("markDir() output values:\n$path\n$affected_files\n$affected_dirs\n", Zend_Log::DEBUG);// !!! debug
        return array('path' => $path, 'files' => $affected_files, 'dirs' => $affected_dirs);
    }



    /**
     * Получить имя файла
     */
    function getFileName($fileid)
    {
    	$stmt = $this->_db->query('SELECT f.FileId, n.Name FROM ' . $this->_db->quoteIdentifier($this->tmp_file) . ' AS f, ' .
  			$this->_db->quoteIdentifier($this->tmp_filename) .
			' AS n WHERE (f.FilenameId = n.FilenameId) AND (f.FileId = ' . $fileid . ') LIMIT 1');
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
    	$select->from(array('p' => $this->tmp_path),     'Path');
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
    function insertRowFile($FileId, $PathId, $FilenameId, $LStat, $MD5, $isMarked, $FileSize)
    {
    	try {
    		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
        		// INSERT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT DELAYED INTO " . $this->_db->quoteIdentifier($this->tmp_file) .
           			" (FileId, PathId, FilenameId, LStat, MD5, isMarked, FileSize) " .
					" VALUES ($FileId, $PathId, $FilenameId, " . $this->_db->quote($LStat) . ", " .
					$this->_db->quote($MD5) . ", $isMarked, $FileSize)");
			break;
			case 'PDO_PGSQL':
				/* exception handling example 38-1 at the bottom :
				 * file:///usr/share/doc/postgresql-8.3.7/html/plpgsql-control-structures.html
				 * see also file:///usr/share/doc/postgresql-8.3.7/html/rules-update.html
				 */
				$sql = 'SELECT my_clone_file(' . $this->_db->quote($this->tmp_file) . ' , ' .
					" $FileId, $PathId, $FilenameId, " . $this->_db->quote($LStat) . ' , ' .
					$this->_db->quote($MD5) . ", $isMarked, $FileSize)";
            	$this->_db->query($sql);
            	break;
            case 'PDO_SQLITE':
            	// http://www.sqlite.org/lang_conflict.html
        		// INSERT ON CONFLICT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT OR IGNORE INTO " . $this->_db->quoteIdentifier($this->tmp_file) .
           			" (FileId, PathId, FilenameId, LStat, MD5, isMarked, FileSize) " .
					" VALUES ($FileId, $PathId, $FilenameId, " . $this->_db->quote($LStat) . ", " .
					$this->_db->quote($MD5) . ", $isMarked, $FileSize)");
				break;
        	}
        	return TRUE; // all ok
        } catch (Zend_Exception $e) {
    		echo "<br><br><br>WbTmpTable.php -> insertRowFile()<br>Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "<br>";
    		return FALSE;
		}
    }

    /**
     * Fast INSERT INTO tmp_filename_ VALUES ()
     */
    function insertRowFilename($FilenameId, $Name)
    {
    	try {
    		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
        		// INSERT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT IGNORE INTO " . $this->_db->quoteIdentifier($this->tmp_filename) .
            		" (FilenameId, Name) VALUES ($FilenameId, " . $this->_db->quote($Name) . ")");
            	break;
			case 'PDO_PGSQL':
				/* exception handling example 38-1 at the bottom :
				 * file:///usr/share/doc/postgresql-8.3.7/html/plpgsql-control-structures.html
				 */
				$sql = 'SELECT my_clone_filename(' . $this->_db->quote($this->tmp_filename) . ", " .
            		$FilenameId . " , " . $this->_db->quote($Name) . ')';
				$this->_db->query($sql);
            	break;
            case 'PDO_SQLITE':
            	// http://www.sqlite.org/lang_conflict.html
        		// INSERT ON CONFLICT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT OR IGNORE INTO " . $this->_db->quoteIdentifier($this->tmp_filename) .
            		" (FilenameId, Name) VALUES ($FilenameId, " . $this->_db->quote($Name) . ")");
        		break;
           	}
        	return TRUE; // all ok
        } catch (Zend_Exception $e) {
    		echo "<br><br><br>WbTmpTable.php -> insertRowFilename()<br>Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "<br>";
    		return FALSE;
		}
    }

    /**
     * Fast INSERT INTO tmp_path_<> VALUES ()
     */
    function insertRowPath($PathId, $Path)
    {
    	try {
    		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
	        	// INSERT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT IGNORE INTO " . $this->_db->quoteIdentifier($this->tmp_path) . " (PathId, Path) VALUES ($PathId, " .
            		$this->_db->quote($Path) . ")");
            	break;
			case 'PDO_PGSQL':
				/* exception handling example 38-1 at the bottom :
				 * file:///usr/share/doc/postgresql-8.3.7/html/plpgsql-control-structures.html
				 */
				$sql = 'SELECT my_clone_path(' . $this->_db->quote($this->tmp_path) . ', ' . $PathId . ' , ' . $this->_db->quote($Path) . ')';
				$this->_db->query($sql);
				break;
			case 'PDO_SQLITE':
            	// http://www.sqlite.org/lang_conflict.html
        		// INSERT ON CONFLICT IGNORE - workaround of duplicate key
        		$this->_db->query("INSERT OR IGNORE INTO " . $this->_db->quoteIdentifier($this->tmp_path) . " (PathId, Path) VALUES ($PathId, " .
            		$this->_db->quote($Path) . ")");
        		break;
        	}
        	return TRUE; // all ok
        } catch (Zend_Exception $e) {
    		echo "<br><br><br>WbTmpTable.php -> insertRowPath()<br>Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "<br>";
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
    	if ($this->isTmpTableExists($this->tmp_file) &&
    		$this->isTmpTableExists($this->tmp_filename) &&
    		$this->isTmpTableExists($this->tmp_path))
    	{
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

    	$this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
    	   " SET tmpCreate=NOW() WHERE tmpName=" . $this->_db->quote($this->tmp_filename));

    	$this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
    	   " SET tmpCreate=NOW() WHERE tmpName=" . $this->_db->quote($this->tmp_path));
    }


    /**
     * Успешно ли скопированы данные из БД bacula
     * Проверка поля wbTmpTable.tmpIsCloneOk
     *
     */
    function isCloneOk()    {
        $select = new Zend_Db_Select($this->_db);
    	$select->from($this->_name, array('isCloneOk' => new Zend_Db_Expr(" COUNT(tmpIsCloneOk)") ));
	    $select->where('tmpName IN (' . $this->_db->quote($this->tmp_file) . ' , ' . $this->_db->quote($this->tmp_filename) . ' , ' .
	    	$this->_db->quote($this->tmp_path) . ')' );
	    $select->where("tmpIsCloneOk > 0");
		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $isCloneOk = $this->_db->fetchOne($select);
        if ( $isCloneOk < $this->num_tmp_tables ) {
            // копирование неудачно
            return FALSE;
        } else {
            //копирование успешно
            return TRUE;
        }
    }


    /**
     * Установка признака успешного клонирования таблиц bacula
     *
     */
    function setCloneOk()
    {
   		$this->_db->query("UPDATE " . $this->_db->quoteIdentifier($this->_name) .
			' SET tmpIsCloneOk=1 WHERE tmpName IN (' . $this->_db->quote($this->tmp_file) . ' , ' .
    	 	$this->_db->quote($this->tmp_filename) . ' , ' . $this->_db->quote($this->tmp_path) . ')' );
    }


    /**
     * Создание временных таблиц : File, Filename, Path
     *
     * @return TRUE if all ok
     */
    function createTmpTables()
    {
    	// удаляем старые таблицы с такими же именами
    	$this->dropTmpTable($this->tmp_file);
    	$this->dropTmpTable($this->tmp_filename);
    	$this->dropTmpTable($this->tmp_path);

    	// сначала создаем записи о новых таблицах !!! порядок не менять
    	switch ($this->db_adapter) {
       		case 'PDO_SQLITE':
       			$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    	   			" (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_file) . ", " .
    	   		$this->_db->quote($this->jobidhash) . ', ' . " datetime('now') )" );
		    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    			   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_filename) . ', '.
    	   		$this->_db->quote($this->jobidhash) . ', ' . " datetime('now') )" );
		    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    			   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_path) . ', ' .
    	   		$this->_db->quote( $this->jobidhash) . ', ' . " datetime('now') )" );
			break;
			default: // mysql, postgresql
				$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    	   			" (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_file) . ", " .
    	   		$this->_db->quote($this->jobidhash) . ', ' . ' NOW() )' );
		    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    			   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_filename) . ', '.
    	   		$this->_db->quote($this->jobidhash) . ', ' . ' NOW() )' );
		    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    			   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_path) . ', ' .
    	   		$this->_db->quote( $this->jobidhash) . ', ' . ' NOW() )' );
			break;
    	}

    	// создаем таблицы !!! порядок не менять
    	// see also cats/make_mysql_tables.in

    	try {
    		/*
    		 * File
    		 */
    		// добавлены дополнительные поля : isMarked, FileSize
			switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
        		$res_file = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
   					FileId INTEGER UNSIGNED NOT NULL,
   					PathId INTEGER UNSIGNED NOT NULL,
   					FilenameId INTEGER UNSIGNED NOT NULL,
   					LStat TINYBLOB NOT NULL,
   					MD5 TINYBLOB,

   					isMarked INTEGER  UNSIGNED DEFAULT 0,
   					FileSize INTEGER  UNSIGNED DEFAULT 0,

   					PRIMARY KEY(FileId),
   					INDEX (PathId, FilenameId)
				) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM");
            	break;
        	case 'PDO_PGSQL':
        	    $res_file = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
   					FileId INTEGER NOT NULL,
   					PathId INTEGER NOT NULL,
   					FilenameId INTEGER NOT NULL,
   					LStat TEXT NOT NULL,
   					MD5 TEXT,

   					isMarked SMALLINT  DEFAULT 0,
   					FileSize INTEGER  DEFAULT 0,

   					PRIMARY KEY(FileId)
				)");
				$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_file . '_pfidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_file) .	"  (PathId, FilenameId)");
            	break;
            case 'PDO_SQLITE':
        		$res_file = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_file) . " (
   					FileId INTEGER,
   					PathId INTEGER UNSIGNED NOT NULL,
   					FilenameId INTEGER UNSIGNED NOT NULL,
   					LStat VARCHAR(255) NOT NULL,
   					MD5 VARCHAR(255),

   					isMarked INTEGER  UNSIGNED DEFAULT 0,
   					FileSize INTEGER  UNSIGNED DEFAULT 0,

   					PRIMARY KEY(FileId)
				)");
				$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_file . '_pfidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_file) .	"  (PathId, FilenameId)");
            	break;
        	}

    		/*
    		 * Filename
    		 */
    		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
    			$res_filename = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_filename) . " (
    				FilenameId INTEGER UNSIGNED NOT NULL,
  					Name BLOB NOT NULL,
  					PRIMARY KEY(FilenameId),
  					INDEX (Name(255))
    			)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM");
    			break;
        	case 'PDO_PGSQL':
        		$res_filename = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_filename) . " (
    				FilenameId INTEGER NOT NULL,
  					Name TEXT NOT NULL,
  					PRIMARY KEY(FilenameId)
    			)");
    			$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_filename . '_nameidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_filename) . "  (Name)");
        		break;
        	case 'PDO_SQLITE':
    			$res_filename = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_filename) . " (
    				FilenameId INTEGER,
  					Name TEXT DEFAULT '',
  					PRIMARY KEY(FilenameId)
    			)");
    			$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_filename . '_nameidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_filename) . "  (Name)");
    			break;
        	}

    		/*
    		 * Path
    		 */
    		// добавлены дополнительные поля : isMarked
    		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
    			$res_path = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_path) . " (
    				PathId INTEGER UNSIGNED NOT NULL,
   					Path BLOB NOT NULL,

   					isMarked INTEGER  UNSIGNED DEFAULT 0,

   					PRIMARY KEY(PathId),
   					INDEX (Path(255))
    			)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM");
    			break;
        	case 'PDO_PGSQL':
        		$res_path = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_path) . " (
    				PathId INTEGER NOT NULL,
   					Path TEXT NOT NULL,

   					isMarked SMALLINT DEFAULT 0,

   					PRIMARY KEY(PathId)
    			)");
    			$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_path . '_pathidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_path) . "  (Path)");
        		break;
        	case 'PDO_SQLITE':
    			$res_path = $this->_db->query("
    			CREATE TABLE " . $this->_db->quoteIdentifier($this->tmp_path) . " (
    				PathId INTEGER,
   					Path TEXT DEFAULT '',

   					isMarked INTEGER  UNSIGNED DEFAULT 0,

   					PRIMARY KEY(PathId)
    			)");
    			$res = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_path . '_pathidx1') . " ON " .
					$this->_db->quoteIdentifier($this->tmp_path) . "  (Path)");
    			break;
        	}

    		return TRUE; // all ok

    	} catch (Zend_Exception $e) {
    		echo "<br><br><br>WbTmpTable.php -> createTmpTables()<br>Caught exception: " . get_class($e) . "<br>"; echo "Message: " . $e->getMessage() . "<br>";
    		// удаляем таблицы
    		$this->dropTmpTable($this->tmp_file);
    		$this->dropTmpTable($this->tmp_filename);
    		$this->dropTmpTable($this->tmp_path);
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
        $this->dropTmpTable($this->tmp_filename);
        $this->dropTmpTable($this->tmp_path);
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

		if ($total_rows < 3)	{
			return FALSE;
		} else {
			return TRUE;
		}
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
        if ( !$total_files ) {
            $total_files = 0;
        }

        $select->reset();
        unset($select);

        // расчет суммарного размера
        $select = new Zend_Db_Select($this->_db);
    	$select->from($this->tmp_file, array('total_size' => new Zend_Db_Expr(" SUM(FileSize)") ));
	    $select->where("isMarked = 1");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $total_size = $this->_db->fetchOne($select);
        if ( !$total_size ) {
            $total_size = 0;
        }

        return array('total_files' => $total_files, 'total_size' => $total_size);
    }


    /*
     * Возвращает имя файла, куда будут выгружены записи для восстановления
     */
    public function getFilenameToExportMarkFiles() {
    	return "webacula_restore_" . $this->jobidhash . ".tmp";
    }

    /**
     * Экспорт помеченных записей в текстовый файл (для восстановления)
     */
    function exportMarkFiles($dir)
    {
        $name = $dir . "/webacula_restore_" . $this->jobidhash . ".tmp";
        $ares = array('result' => FALSE, 'name' => $name);

        $file = fopen($name, 'w');
        if( !$file ) {
            $ares['msg'] = "Unable to write file $name";
            return $ares;
        }

		switch ($this->db_adapter) {
        	case 'PDO_MYSQL':
				$sql = "SELECT `f`.`FileId`, `n`.`Name`, `p`.`Path`
            		FROM " . $this->_db->quoteIdentifier($this->tmp_file) . " AS `f`
                	INNER JOIN " . $this->_db->quoteIdentifier($this->tmp_filename) . " AS `n`
                	INNER JOIN " . $this->_db->quoteIdentifier($this->tmp_path) . " AS `p`
            		WHERE
	           		(f.FilenameId = n.FilenameId) AND (f.PathId = p.PathId) AND (f.isMarked = 1)
            		ORDER BY `Path` ASC";
				break;
        	case 'PDO_PGSQL':
				$sql = "SELECT f.FileId, n.Name, p.Path
            		FROM " . $this->_db->quoteIdentifier($this->tmp_file) . " AS f,
                	" . $this->_db->quoteIdentifier($this->tmp_filename) . " AS n,
                	" . $this->_db->quoteIdentifier($this->tmp_path) . " AS p
            		WHERE
	           		(f.FilenameId = n.FilenameId) AND (f.PathId = p.PathId) AND (f.isMarked = 1)
            		ORDER BY Path ASC";
				break;
			case 'PDO_SQLITE':
				$sql = "SELECT f.FileId, n.Name, p.Path
            		FROM " . $this->_db->quoteIdentifier($this->tmp_file) . " AS f
                	INNER JOIN " . $this->_db->quoteIdentifier($this->tmp_filename) . " AS n
                	INNER JOIN " . $this->_db->quoteIdentifier($this->tmp_path) . " AS p
            		WHERE
	           		(f.FilenameId = n.FilenameId) AND (f.PathId = p.PathId) AND (f.isMarked = 1)
            		ORDER BY Path ASC";
				break;
        }
        $stmt = $this->_db->query($sql);

		$i = 0;
		while ($line = $stmt->fetch())
        {
            fwrite($file, $line['path'] . $line['name'] . "\n");
            $i++;
        }
        fclose($file);

        $ares['result'] = TRUE;
        $ares['msg'] = 'Export file is completed successfully';
        $ares['count'] = $i;

        return $ares;
    }



	/**
	 * Clone Bacula tables : File, Filename, Path to webacula DB
	 *
	 * @return TRUE if ok
	 */
	function cloneBaculaToTmp($jobid)
	{
		$bacula = Zend_Registry::get('db_bacula');
		// create temporary tables: File, Filename, Path. создаем временные таблицы File, Filename, Path
		if ( !$this->createTmpTables() ) {
			// view exception from WbTmpTable.php->createTmpTables()
			return FALSE;
		}

		$decode = new MyClass_HomebrewBase64;

		//********************** clone File + Path **********************

        // in order to reduce the number insert's for to copy a table Path
		// для минимизации insert'ов при копировании таблицы Path
        $old_pathid = 0;
        $apath = array();

        $stmt = $bacula->query(
            "SELECT
	           f.FileId, f.PathId, f.FilenameId, f.LStat, f.MD5,
	           n.FilenameId, n.Name,
	           p.PathId, p.Path
            FROM File AS f
                INNER JOIN Filename AS n ON n.FilenameId = f.FilenameId
                INNER JOIN Path AS p     ON p.PathId = f.PathId
            WHERE
	           f.JobId = $jobid
            ORDER BY
	           p.PathId ASC");

        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total
            // размер файла пишем в отдельное поле, чтобы потом легче было подсчитать общий объем
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
			$file_size = $decode->homebrewBase64($st_size);
			// Sorting through filed PathId should take INSERTs quickly
			// за счет сортировки по PathId вставка должна проходить быстро
			if ( !$this->insertRowFile($line['fileid'], $line['pathid'], $line['filenameid'], $line['lstat'], $line['md5'], 0, $file_size) ) {
				// show exception from WbTmpTable.php->insertRowFile()
				return FALSE;
			}

            if ( empty($line['name']) )	{
                // if it is a directory and there are data LStat, - immediately write
                // если это каталог и есть данные LStat,- сразу пишем
				if ( !$this->insertRowPath($line['pathid'], $line['path']) ) {
					// show exception from WbTmpTable.php->insertRowPath()
					return FALSE;
				}
				$old_pathid = $line['pathid'];
			} else {
                if ( $old_pathid != $line['pathid'] )	{
				    $key = $line['path'];
					$apath[$key]['pathid']    = $line['pathid'];
					$old_pathid = $line['pathid'];
				}
			}
        }

        // write on the path without information LStat
        // пишем пути без информации об LStat
		foreach($apath as $key=>$val)	{
            if ( !$this->insertRowPath($val['pathid'], $key) ) {
				// show exception from WbTmpTable.php->insertRowPath()
				return FALSE;
			}
		}
        unset($stmt);


        //**************************** clone Filename (fastest) ****************************
		/**
		 * Все имена полей, приведенные в списке предложения SELECT, должны присутствовать и во фразе GROUP BY -
		 * за исключением случаев, когда имя столбца используется в итоговой функции.
		 * Обратное правило не является справедливым - во фразе GROUP BY могут быть имена столбцов, отсутствующие в
		 * списке предложения SELECT.
		 * Если совместно с GROUP BY используется предложение WHERE, то оно обрабатывается первым,
		 * а группированию подвергаются только те строки, которые удовлетворяют условию поиска.
 		*/
        $stmt = $bacula->query(
            "SELECT
	           f.FileId, f.FilenameId,
	           n.FilenameId, n.Name
            FROM File AS f
                INNER JOIN Filename AS n ON n.FilenameId = f.FilenameId
            WHERE
	           f.JobId = $jobid");

        while ($line = $stmt->fetch()) {
            if ( !$this->insertRowFilename($line['filenameid'], $line['name']) ) {
				// show exception from WbTmpTable.php->insertRowFilename()
				return FALSE;
			}
        }
        unset($stmt);

        // end transaction
        // после успешного клонирования устанавливаем признак
        $this->setCloneOk();
        return TRUE;
	}


	/**
	 * Clone Bacula tables : File, Filename, Path to webacula DB
	 * for Restore Recent Backup
	 *
	 * @return TRUE if ok
	 */
	function cloneRecentBaculaToTmp($jobidhash, $sjobids)
	{
		$bacula = Zend_Registry::get('db_bacula');
		// create temporary tables: File, Filename, Path
		// создаем временные таблицы File, Filename, Path
		$tmp_tables = new WbTmpTable(self::_PREFIX, $jobidhash, $this->ttl_restore_session);
		$this->createTmpTables();

		$decode = new MyClass_HomebrewBase64;

		//********************** clone File, Filename, Path **********************
        // in order to reduce the number insert's for to copy a table Path
		// для минимизации insert'ов при копировании таблицы Path
        $old_pathid = 0;
        $apath = array();
		// dird/ua_restore.c :: build_directory_tree
        $sql = "SELECT Path.Path, File.FileId, File.PathId, File.FilenameId, File.LStat, File.MD5, Filename.Name" .
        		" FROM (" .
        			" SELECT max(FileId) as FileId, PathId, FilenameId" .
        			" FROM (" .
        				" SELECT FileId, PathId, FilenameId" .
        				" FROM File" .
        				" WHERE JobId IN ( $sjobids )" .
        				" ORDER BY JobId DESC" .
        			" ) AS F" .
        			" GROUP BY PathId, FilenameId )" .
        		" AS Temp" .
        		" JOIN Filename ON (Filename.FilenameId = Temp.FilenameId)" .
        		" JOIN Path ON (Path.PathId = Temp.PathId)" .
        		" JOIN File ON (File.FileId = Temp.FileId)" .
        		" WHERE File.FileIndex > 0" .
        		" ORDER BY JobId, FileIndex ASC";

		$stmt = $bacula->query($sql);

        while ($line = $stmt->fetch()) {
            // file size writing in a separate filed to then it was easier to calculate the total size
            // размер файла пишем в отдельное поле, чтобы потом легче было подсчитать общий объем
            // LStat example: MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E
            list($st_dev, $st_ino, $st_mode, $st_nlink, $st_uid, $st_gid, $st_rdev, $st_size, $st_blksize,
                $st_blocks, $st_atime, $st_mtime, $st_ctime) = preg_split("/[\s]+/", $line['lstat']);
			$file_size = $decode->homebrewBase64($st_size);
			// Sorting through filed PathId should take INSERTs quickly
			// за счет сортировки по PathId вставка должна проходить быстро
			$this->insertRowFile($line['fileid'], $line['pathid'], $line['filenameid'], $line['lstat'], $line['md5'], 0, $file_size);
			$this->insertRowFilename($line['filenameid'], $line['name']);

            if ( empty($line['name']) )	{
                // if it is a directory and there are data LStat, - immediately write
                // если это каталог и есть данные LStat,- сразу пишем
				$this->insertRowPath($line['pathid'], $line['path']);
				$old_pathid = $line['pathid'];
			} else {
                if ( $old_pathid != $line['pathid'] )	{
				    $key = $line['path'];
					$apath[$key]['pathid']    = $line['pathid'];
					$old_pathid = $line['pathid'];
				}
			}
        }
        // write on the path without information LStat
        // пишем пути без информации об LStat
		foreach($apath as $key=>$val)	{
            $this->insertRowPath($val['pathid'], $key);
		}
        unset($stmt);
        // end transaction
        // после успешного клонирования устанавливаем признак
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
				$sql = 'SELECT DISTINCT f.FileId as fileid, f.LStat as lstat, f.MD5 as md5, p.Path as path, n.Name as name
					FROM ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . " AS f, " .
               		$this->_db->quoteIdentifier($this->getTableNamePath()) . ' AS p, ' .
			   		$this->_db->quoteIdentifier($this->getTableNameFilename()) . ' AS n
			   		WHERE (f.isMarked = 1) AND (f.PathId = p.PathId) AND (f.FileNameId = n.FileNameId)
  			   		ORDER BY Path ASC, Name ASC
  			   		LIMIT ' . self::ROW_LIMIT_FILES . ' OFFSET ' . $offset;
  			   	break;
        	default: // mysql, postgresql
        		$sql = 'SELECT DISTINCT f.FileId, f.LStat, f.MD5, p.Path, n.Name
					FROM ' . $this->_db->quoteIdentifier($this->getTableNameFile()) . " AS f, " .
               		$this->_db->quoteIdentifier($this->getTableNamePath()) . ' AS p, ' .
			   		$this->_db->quoteIdentifier($this->getTableNameFilename()) . ' AS n
			   		WHERE (f.isMarked = 1) AND (f.PathId = p.PathId) AND (f.FileNameId = n.FileNameId)
  			   		ORDER BY Path ASC, Name ASC
  			   		LIMIT ' . self::ROW_LIMIT_FILES . ' OFFSET ' . $offset;
        	break;
        }
 		//$this->logger->log("listRestoreAction : " . $sql, Zend_Log::INFO); // for !!!debug!!!
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

	public function getCountFileName() {
		// подсчет кол-ва
    	$query = "SELECT count(*) as num FROM " . $this->_db->quoteIdentifier($this->tmp_filename);
    	$stmt   = $this->_db->query($query);
    	$countf = $stmt->fetchAll();
    	return $countf[0]['num'];
	}

	public function getCountPath() {
		// подсчет кол-ва
    	$query = "SELECT count(*) as num FROM " . $this->_db->quoteIdentifier($this->tmp_path);
    	$stmt   = $this->_db->query($query);
    	$countf = $stmt->fetchAll();
    	return $countf[0]['num'];
	}

	function my_debug($msg)
	{
		echo "$msg<br>";
		echo '<hr>model/WbTmpTable()</hr><hr>';
		echo '<br><h1>debug_backtrace</h1>';
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

}
