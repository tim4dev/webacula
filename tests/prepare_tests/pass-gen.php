<?php

$pwd  = $argv[1];
$salt = '+geTWx@L+iO|2gB7pkkikK;%za^ZWC8e';

echo "\n" . sha1($pwd . $salt) . "\n\n";

?>
