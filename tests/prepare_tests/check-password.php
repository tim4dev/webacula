#!/usr/bin/php
<?php

require '../../library/MyClass/PasswordHash.php';

function my_usage()
{
   GLOBAL $argv;
   echo "\nPassword check\n\n";
   echo "Usage:\n\t", $argv[0], " <password> <hash>\n\n";
}


#  проверка командной строки
if ($argc != 3)  {
   my_usage();
   exit(1);
}

/*
 * Main program
 */

$pass = $argv[1];
$hash = $argv[2];

echo "read:\npassword = $pass\nhash = $hash\n\n";

$hasher = new MyClass_PasswordHash();

$rc = $hasher->CheckPassword($pass, $hash);
if ( $rc ) {
    echo "OK\n";
} else {
    echo "ERROR\n";
}
unset($hasher);

?>
