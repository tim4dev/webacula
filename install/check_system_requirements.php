#!/usr/bin/php
<?php
/**
 *
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
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 * @abstract Check System Requirements
 * 
 */
/*
	see also http://framework.zend.com/manual/ru/requirements.html
*/

$PHPV   = '5.2.4';
$MYSQLV = '5.0.0';
$PGSQLV = '8.0.0';
$SQLITEV= '3.0.0';
$AEXT1   = array('pdo', 'gd', 'xml', 'dom');
$AEXT2   = array('pdo_mysql', 'pdo_pgsql', 'pdo_sqlite');

error_reporting(E_ALL|E_STRICT);

function getMySQLversion() {
    $output = shell_exec('mysql -V');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
    if ( isset($version[0]) )
        return $version[0];
    else
        return 0; // MySql not installed
}

function getPgSQLversion() {
    $output = shell_exec('psql -V');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
    if ( isset($version[0]) )
        return $version[0];
    else
        return 0;  // Postgresql not installed
}

function getSqliteVersion() {
    $output = shell_exec('sqlite3 -version');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
    if ( isset($version[0]) )
        return $version[0];
    else
        return 0;  // Sqlite not installed
}


/*
 * main program
 */

echo "\nWebacula check System Requirements...\n\n";

$mysql_ver = getMySQLversion();
$pgsql_ver = getPgSQLversion();
$sqlite_ver = getSqliteVersion();

if ( ($mysql_ver == 0) && ($pgsql_ver == 0) && ($sqlite_ver == 0) )
    echo "ERROR! SQL server not found!\n";
else {
    if ($mysql_ver) {
        echo 'Current MySQL version = ', $mysql_ver;
        if ( version_compare($mysql_ver, $MYSQLV) === -1 ) {
            echo "\tWarning. Upgrade your MySQL version to $MYSQLV or later\n";
        } else {
            echo "\tOK\n";
        }
    }
    if ($pgsql_ver) {
        echo 'Current PostgreSQL version = ', $pgsql_ver;
        if ( version_compare($pgsql_ver, $PGSQLV) === -1 ) {
            echo "\tWarning. Upgrade your PostgreSQL version to $PGSQLV or later\n";
        } else {
            echo "\tOK\n";
        }
    }
    if ($sqlite_ver) {
        echo 'Current Sqlite version = ', $sqlite_ver;
        if ( version_compare($sqlite_ver, $SQLITEV) === -1 ) {
            echo "\tWarning. Upgrade your Sqlite version to $SQLITEV or later\n";
        } else {
            echo "\tOK\n";
        }
    }
}
echo "\n";

$php_ver = phpversion();
echo 'Current PHP version = ', $php_ver;
if ( version_compare($php_ver, $PHPV) === -1 )
	echo "\tERROR! Upgrade your PHP version to $PHPV or later\n";
else
	echo "\tOK\n";

echo "\n";

foreach ($AEXT1 as $ext)	{
	if ( extension_loaded($ext) )
		echo "php $ext installed.\tOK\n";
	else
		echo "ERROR! PHP extension $ext not installed.\n";
}

echo "\n";

foreach ($AEXT2 as $ext)	{
	if ( extension_loaded($ext) )
		echo "php $ext installed.\tOK\n";
	else
		echo "Warning. PHP extension $ext not installed.\n";
}

if ( extension_loaded('dom') ) {
	$temp = new DOMDocument('1.0', 'iso-8859-1');
	if ( !$temp )
		echo "Warning. php-xml, php-dom extension not installed. RSS feed not work\n";
	else
		echo "php-dom, php-xml installed.\tOK\n";
} else
	echo "Warning. php-xml, php-dom extension not installed. RSS feed not work\n";

echo "\n";

?>