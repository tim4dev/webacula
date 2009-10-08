#!/usr/bin/php
<?php
/**
 *
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
$AEXT1   = array('pdo', 'gd', 'xml', 'dom');
$AEXT2   = array('pdo_mysql', 'pdo_pgsql');

error_reporting(E_ALL|E_STRICT);

function getMySQLversion() {
   $output = shell_exec('mysql -V');
   preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
   return $version[0];
}



echo "\nCheck System Requirements...\n";

$mysql_ver = getMySQLversion();
echo 'Current MySQL version = ', $mysql_ver;
if ( version_compare($mysql_ver, $MYSQLV) === -1 ) {
	echo "\tERROR! Upgrade your MySQL version to $MYSQLV or later\n";
} else {
	echo "\tOK\n";
}

$php_ver = phpversion();
echo 'Current PHP   version = ', $php_ver;
if ( version_compare($php_ver, $PHPV) === -1 ) {
	echo "\tERROR! Upgrade your PHP version to $PHPV or later\n";
} else {
	echo "\tOK\n";
}

foreach ($AEXT1 as $ext)	{
	if ( extension_loaded($ext) ) {
		echo "php $ext installed.\tOK\n";
	} else {
		echo "ERROR! php $ext extension not installed.\n";
	}
}

foreach ($AEXT2 as $ext)	{
	if ( extension_loaded($ext) ) {
		echo "php $ext installed.\tOK\n";
	} else {
		echo "Warning. php $ext extension not installed.\n";
	}
}

if ( extension_loaded('dom') ) {
	$temp = new DOMDocument('1.0', 'iso-8859-1');
	if ( !$temp ) {
		echo "Warning. php-xml, php-dom extension not installed. RSS feed not work\n";
	} else {
		echo "php-dom, php-xml installed.\tOK\n";
	}
} else {
	echo "Warning. php-xml, php-dom extension not installed. RSS feed not work\n";
}

?>

