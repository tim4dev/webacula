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
	
	// для хранения данных для Restore
	protected $restoreNamespace; 
	const RESTORE_NAME_SPACE = 'RestoreSessionNamespace';
	
	protected $logger; // for debug
	
	
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

	/**
	 * @param string $prefix для формирования имен tmp таблиц
	 * @param string $jobidHash хэш-индекс для массива jobid
	 */
	public function __construct($prefix, $jobidhash)
    {
    	$this->db_adapter = Zend_Registry::get('DB_ADAPTER_WEBACULA');   	    	
    	$this->restoreNamespace = new Zend_Session_Namespace(self::RESTORE_NAME_SPACE);
    	$this->jobidhash = $jobidhash;
    	// формируем имена временных таблиц
   		$this->tmp_file     = $prefix . 'file_'     . $this->jobidhash;
   		$this->tmp_filename = $prefix . 'filename_' . $this->jobidhash;
   		$this->tmp_path     = $prefix . 'path_'     . $this->jobidhash;
    	
		$config['db']      = Zend_Registry::get('db_webacula'); // database
		$config['name']    = $this->_name; 		// name table
		$config['primary'] = $this->_primary;   // primary key
		$config['sequence']= true;
		
        // получаем ttl_restore_session
        $config_ini = Zend_Registry::get('config');       
        if ( empty($config_ini->ttl_restore_session) || intval($config_ini->ttl_restore_session) < 300) {
        	$this->ttl_restore_session = 3900;
        } else {
        	$this->ttl_restore_session = intval($config_ini->ttl_restore_session);
        }
        
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
    	/* сессия на восстановление существует ? */
    	if ( !isset($this->restoreNamespace->typeRestore ) ) {    				
    		// если хэш не существует, то сессия протухла или ошибка в программе
    		// удаляем старые tmp-таблицы   		    		   		
    		$this->dropOldTmpTables();
    		// бросаем исключение
    		throw new Zend_Exception("Session of Restore backup is expired. See also <b>ttl_restore_session</b> in your config.ini.".
    								" This is not a bug in the program."); 
    	}
    	// for debug !!!
        /*Zend_Loader::loadClass('Zend_Log_Writer_Stream');
		Zend_Loader::loadClass('Zend_Log');
        $writer = new Zend_Log_Writer_Stream('/tmp/ajax.log');
		$this->logger = new Zend_Log($writer);
		$this->logger->log("debug on", Zend_Log::INFO);*/
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
    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    	   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_file) . ", " .
    	   $this->_db->quote($this->jobidhash) . ', ' . ' NOW() )' );

    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    	   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_filename) . ', '.
    	   $this->_db->quote($this->jobidhash) . ', ' . ' NOW() )' );

    	$this->_db->query("INSERT INTO " . $this->_db->quoteIdentifier($this->_name) .
    	   " (tmpName, tmpJobIdHash, tmpCreate) VALUES (" . $this->_db->quote($this->tmp_path) . ', ' .
    	   $this->_db->quote( $this->jobidhash) . ', ' . ' NOW() )' );

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
				$res_file = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_file . '_pfidx1') . " ON " . 
					$this->_db->quoteIdentifier($this->tmp_file) .
					"  (PathId, FilenameId)");
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
    			$res_file = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_filename . '_nameidx1') . " ON " . 
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
    			$res_file = $this->_db->query("CREATE INDEX " . $this->_db->quoteIdentifier($this->tmp_path . '_pathidx1') . " ON " . 
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


    /**
     * Экспорт помеченных записей в текстовый файл
     *
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


}
