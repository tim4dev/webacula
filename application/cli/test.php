<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 * 
 * Test CLI
 * 
 */ 
$version = 'version';
echo "\n$version\n\n";

error_reporting(E_ALL|E_STRICT);
ini_set('display_errors',true);
set_include_path('.' . PATH_SEPARATOR . '../../library/' . PATH_SEPARATOR . '../models/' .
    PATH_SEPARATOR . '../forms/' .
    PATH_SEPARATOR . get_include_path() );

include "Zend/Loader.php";

Zend_Loader::loadClass('Zend_Config_Ini');
Zend_Loader::loadClass('Zend_Console_Getopt');
Zend_Loader::loadClass('Zend_Db');
Zend_Loader::loadClass('Zend_Db_Table');
Zend_Loader::loadClass('Zend_Date');
Zend_Loader::loadClass('Zend_Registry');

// load configuration
$config = new Zend_Config_Ini('../config.ini');

// setup database bacula
Zend_Registry::set('DB_ADAPTER', strtoupper($config->general->db->adapter) );
$params = $config->general->db->config->toArray();
// for cross database compatibility with PDO, MySQL, PostgreSQL
$params['options'] = array(Zend_Db::CASE_FOLDING => Zend_Db::CASE_LOWER, Zend_DB::AUTO_QUOTE_IDENTIFIERS => FALSE);
$db_bacula = Zend_Db::factory($config->general->db->adapter, $params);
Zend_Db_Table::setDefaultAdapter($db_bacula);
Zend_Registry::set('db_bacula', $db_bacula);


/*
 * main program
 */
$params = $config->general->db->config->toArray();
var_dump($params);

echo "\n", getcwd(), "\n";

?>