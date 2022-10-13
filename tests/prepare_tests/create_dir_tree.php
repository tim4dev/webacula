<?php
/*
 * Create directory tree for tests
 *
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

#  проверка командной строки
if ($argc != 3)
{
   echo "Usage : <dir> <max files>\n";
   echo  "Error: command line incorrect !\n";
   exit(1);
}

define("BASE_PATH",  $argv[1]);
define("MAX_FILES",  $argv[2]);
define("MAX_DIRS",    5);
define("MAX_LEVEL",   2);

define("PERMISSION_DIR",  0777);
define("PERMISSION_FILE", 0666);
define("SUFFIX_NAME_FILE", " Файл'.txt");
define("SUFFIX_NAME_DIR",  " Каталог'tmp");

define("FILES_PER_DIR",  round(MAX_FILES / MAX_DIRS) );
define("DIRS_PER_LEVEL", round(MAX_DIRS / MAX_LEVEL) );



$count_dir = 0;
$count_fil = 0;



/*****************************************************************************
    Functions
******************************************************************************/

function my_create_dir($path)
{
    global $count_dir;
    $path_full = BASE_PATH . $path;
    if ( !file_exists($path_full) ) {
        mkdir($path_full, PERMISSION_DIR, true);
        $count_dir++;
    }
}

function my_write_file($path)
{
    global $count_fil;
    $path_full = BASE_PATH . $path;
    for ($i = 0; $i < FILES_PER_DIR; $i++) {
        // Generation of unique names for files
        $namefile = $path_full . '/' . $count_fil . SUFFIX_NAME_FILE;
        if ( !file_exists("$namefile") ) {
            $f = fopen("$namefile", 'w');
            fwrite($f, 'test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1test1');
            fclose($f);
            $count_fil++;
        }
    }
}



/*****************************************************************************
    Main program
******************************************************************************/

echo "-------\n";
echo "base path = " . BASE_PATH . "\n";
echo "FILES_PER_DIR = " . FILES_PER_DIR . "\n";
echo "MAX_LEVEL = " . MAX_LEVEL . "\n";
echo "DIRS_PER_LEVEL = " . DIRS_PER_LEVEL . "\n";
echo "-------\n";

//echo "Press ENTER to create, CTRL+C to break";
//fgets(STDIN);

$count_dir = 0;
$count_fil = 0;

$path_i = '';
for ($i = 0; $i < MAX_LEVEL; $i++) {
    $path_i .= '/' . $i . SUFFIX_NAME_DIR;
    //echo "$path_i\n";

    $path_x = '';
    for ($x = 1; $x <= DIRS_PER_LEVEL; $x++) {
        $path_x = $path_i . '/' . $x . SUFFIX_NAME_DIR;
        //echo "$path_x\n";
        my_create_dir($path_x);
        my_write_file($path_x);
    }
}

echo "\ncount dirs = $count_dir\n";
echo "count files = $count_fil\n";

?>
