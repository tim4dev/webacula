<?php
 /*
 * Script for copy all data from MySQL Bacula v.5.x DB into Sqlite Bacula v.5.x DB
 * begin: 2009.09.14
 *
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

$tables = array(
    'Job',
    'JobMedia',
    'File',
    'UnsavedFiles',
    'BaseFiles',
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

function my_Sqlite_db_connect()
{
    // http://by.php.net/manual/en/book.pdo.php
    $dbname = '/tmp/webacula/sqlite/bacula.db';
    $db = new PDO('sqlite:'.$dbname, '', '') or die("[Sqlite] error connect.");
    echo "Connect [Sqlite] '$dbname' OK.\n";
    return $db;
}

function my_copy_table($table_name)
{
    $dbIN  = my_MySQL_db_connect();

    $sql   = "SELECT * FROM $table_name";
    $resIN = mysql_query($sql);

    if ( !$resIN ) {
        echo "Could not successfully run query ($sql) from DB: " , mysql_error(), "\n";
        return;
    }

    if ( mysql_num_rows($resIN) == 0) {
        echo "No rows in $table_name found.\n";
        return;
    }

    $dbOUT = my_Sqlite_db_connect();
    $i = 0;
    echo "\n";
    while( $row = mysql_fetch_assoc($resIN) )
    {
        $columns = '';
        $values  = '';
        //print_r($row); exit; // debug
        foreach($row as $key => $value) {
          //echo "$key = $value\n"; // debug
          $columns = $columns . $key . ' ,';
          $values  .= $dbOUT->quote($value) . ' ,';
        }
        $columns = rtrim($columns, ',');
        $values  = rtrim($values, ',');
        unset($row);

        // http://by.php.net/manual/en/book.pdo.php
        $query  = "INSERT INTO $table_name ($columns) VALUES ($values)";
        //echo $query, "\n";
        $resOUT = $dbOUT->query($query);
        if (!$resOUT) {
            echo "\n\nERROR : array([0] SQLSTATE error code, [1] Driver-specific error code, [2] Driver-specific error message)\n";
            print_r($dbOUT->errorInfo());
            echo "\n\n";
            echo "SQL QUERY : $query \n\n";
            exit;
        }
        $resOUT->closeCursor();
        unset($query);
        unset($resOUT);
        $i++;
        //echo "."; // progress bar
    }
    // close MySQL
    if ($resIN) mysql_free_result($resIN);
    if ($dbIN) mysql_close($dbIN);
    // close Sqlite
    unset($queryOUT);
    unset($resOUT);
    unset($dbOUT);

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
