#!/usr/bin/php
<?php

require '../library/MyClass/PasswordHash.php';

function my_usage()
{
   GLOBAL $argv;
   echo "\nDB password hashing\n\n";
   echo "Usage:\n\t", $argv[0], " <password>\n\n";
}


#  проверка командной строки
if ($argc != 2)  {
   my_usage();
   exit(1);
}

/*
 * Main program
 */

$pass = $argv[1];

$hasher = new MyClass_PasswordHash();

$hash = $hasher->HashPassword($pass);
if (strlen($hash) < 20)
    exit('Failed to hash new password');
unset($hasher);

echo $hash. "\n";

?>
