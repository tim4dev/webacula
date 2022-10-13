<?php
 /*
 * Script for copy all data from MySQL Bacula v.5.x into PostgreSQL Bacula v.5.x DB
 * begin: 2007.01.03
 *
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

$tables = array(
    'UnsavedFiles',
    'BaseFiles',
    'JobMedia',
    'File',
    'Job',
    'Media',
    'Client',
    'Pool',
    'FileSet',
    'Path',
    'Filename',
    'Counters',
    'Version',
    'CDImages',
    'Device',
    'Storage',
    'MediaType',
    'Log',
    'Location',
    'LocationLog',
    'PathHierarchy',
    'PathVisibility'
);



function my_gettime()
{
        $ptime = explode(' ', microtime());
        $rtime = $ptime[1] + $ptime[0];
        return $rtime;
}

function my_diff_time($start_time)
{
   $end_time = my_gettime();
   $diff_time = number_format($end_time - $start_time, 5);
   return $diff_time;
}

function my_MySQL_db_connect()
{
    // http://www.php.net/manual/ru/ref.mysql.php
    $dblocation = "localhost";
    $dbuser = "root";
    $dbpassword = "";
    $dbname = "bacula";

    $db = mysql_connect($dblocation, $dbuser, $dbpassword)
        or die("[MySQL]: Could not connect.\n" . mysql_error());
//    mysql_query("SET CHARACTER SET 'utf8';", $db);
//    mysql_query("SET NAMES utf8;", $db);
    mysql_select_db($dbname, $db)
        or die("[MySQL]: Could not select database.\n" . mysql_error());
    echo "Connect [MySQL] '$dbname' OK.\n";
    return $db;
}

function my_PGSQL_db_connect()
{
    // http://www.php.net/manual/ru/ref.pgsql.php
    $dblocation = "localhost";
    $dbuser = "root";
    $dbpassword = "";
    $dbname = "bacula";
    /*$db = pg_connect("host='$dblocation' dbname='$dbname' user='$dbuser' password='$dbpassword'")
        or die("[PGSQL]" . pg_last_error());*/
    $db = pg_connect("host='$dblocation' dbname='$dbname' user='$dbuser' password='$dbpassword'")
        or die("[PGSQL]" . pg_last_error());
    pg_set_client_encoding ($db, 'SQL_ASCII');
    echo "Connect [PGSQL] ", $dbname, ". Encoding ", pg_client_encoding(), ".  OK.\n";
    return $db;
}

function my_copy_table($table_name)
{
    $dbIN  = my_MySQL_db_connect();
    $dbOUT = my_PGSQL_db_connect();

    $sql   = "SELECT * FROM $table_name";
    $resIN = mysql_query($sql);

    if ( !$resIN ) {
        echo "Could not successfully run query ($sql) from DB: " , mysql_error(), "\n";
        return;
    }

    if ( mysql_num_rows($resIN) == 0) {
        echo "No rows found, exiting.\n";
        return;
    }

    $i = 0;
    while( $row = mysql_fetch_assoc($resIN) )
    {
        $columns = '';
        $values  = '';
        //print_r($row); exit; // debug
        foreach($row as $key => $value) {
          //echo "$key = $value\n"; // debug
          $columns = $columns . $key . ',';
	       if ($value == '0000-00-00 00:00:00')  {
	           $values  .= "NULL,";
	       } else {
            $values  .= "'" . pg_escape_string($dbOUT, $value) . "',";
          }
        }
        $columns = rtrim($columns, ',');
        $values  = rtrim($values, ',');
        unset($row);

        $query  = "INSERT INTO $table_name ($columns) VALUES ($values)";
        //echo $query, "\n";
        $resOUT = pg_query($dbOUT, $query);
        if ( !$resOUT ) die("\n\nSQL : $query\n\n");
        $i++;
    }
    // close MySQL
    if ($resIN) mysql_free_result($resIN);
    if ($dbIN) mysql_close($dbIN);
    // close PGSQL
    if ($resOUT) pg_free_result($resOUT);
    if ($dbOUT) pg_close($dbOUT);
    echo "Table : '$table_name' , insert ", $i, " row(s)\n";
}






// ************************** MAIN program

$start_time = my_gettime();

foreach ($tables as $table) {
    echo "\n*******", $table, "\n";
    my_copy_table($table);
}

echo "\n" , my_diff_time($start_time), "sec\n\n";

?>
