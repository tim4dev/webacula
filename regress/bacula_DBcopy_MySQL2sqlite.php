<?php
 /*
 * Script for copy all data from MySQL Bacula v.3.0 DB into Sqlite Bacula v.3.0 DB
 * begin: 2009.09.14
 *
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
//    'Status',
    'Log',
    'Location',
    'LocationLog'
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
    mysql_query("SET CHARACTER SET 'utf8';", $db);
    mysql_query("SET NAMES utf8;", $db);
    mysql_select_db($dbname, $db)
        or die("[MySQL]: Could not select database.\n" . mysql_error());
    echo "Connect [MySQL] '$dbname' OK.\n";
    return $db;
}

function my_Sqlite_db_connect()
{
    // http://by.php.net/manual/en/book.sqlite3.php
    $dbname = '/tmp/webacula/sqlite/bacula.db';
    $db = new SQLite3(dbname)
      or die("[Sqlite] error connect.");
    echo "Connect [Sqlite] '$dbname' OK.\n";
    return $db;
}

function my_copy_table($table_name)
{
    $dbIN  = my_MySQL_db_connect();
    $dbOUT = my_Sqlite_db_connect();

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
//	       if ($value == '0000-00-00 00:00:00')  {
//	           $values  .= "NULL,";
//	       } else {
            $values  .= "'" .  addslashes($value) . "',";
//          }
        }  
        $columns = rtrim($columns, ',');
        $values  = rtrim($values, ',');
        unset($row);

        $query  = "INSERT INTO $table_name ($columns) VALUES ($values)";
        //echo $query, "\n";
        $resOUT = $dbOUT->query($query);
        if ( !$resOUT ) die("\n\nSQL : $query\n\n");
        //if ( !$resOUT ) echo 'Query failed: ' , pg_last_error();
        $i++;
    }

    // close MySQL
    if ($resIN) mysql_free_result($resIN);
    if ($dbIN) mysql_close($dbIN);
    // close Sqlite
    $dbOUT->close();

    echo "******* Table : '$table_name' , insert ", $i, " row(s)\n";
}






// ************************** MAIN program

$start_time = my_gettime();

foreach ($tables as $table) {
    echo "\n*******", $table, "\n";
    my_copy_table($table);
}

echo "\n" , my_diff_time($start_time), "sec\n\n";

?>
