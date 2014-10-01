#!/bin/bash
#
#############################################################
#
# Main script for unit tests (see README)
#
# Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
# @author Yuriy Timofeev <tim4dev@gmail.com>
# @package webacula
# @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License 
#
# Usage:
#
# ./runtest.sh --exclude-group job-nonreusable,restore,logbook
# ./runtest.sh --group test-test
# ./runtest.sh --filter testJobFindByVolumeName
# sudo LANG=C phpunit --group test-test --stop-on-failure AllTests.php
#
#############################################################


BASEDIR=`pwd`
SRC_DIR=".."
F_INDEX_PHP="${SRC_DIR}/html/index.php"
F_README="${SRC_DIR}/README"
F_SPEC="${SRC_DIR}/packaging/Fedora/webacula.spec"
LINE1="*********************************************************************************************"

VERSION=`grep -e "^.*define('WEBACULA_VERSION.*$" ${F_INDEX_PHP} | awk -F "'" '{print($4)}'`
VER_README=`grep -e "^Version:" ${F_README} | awk '{print($2)}'`
VER_SPEC=`grep -e "^Version:" ${F_SPEC} | awk '{print($2)}'`




my_on_exit() {
    cd $BASEDIR
    echo "$0 output:"
    echo "clean and exit"
    cp -f ../application/config.ini.original  ../application/config.ini
    cp -f ../html/.htaccess_original  ../html/.htaccess
    rm -f cookies.txt
    rm -f  ../data/cache/zend_cache*
    rm -f  ../data/tmp/webacula*
    rm -f  ../data/session/ses*
}




###########################################################
# Main program
###########################################################

if [ ${VERSION} == ${VER_SPEC} ] && [ ${VERSION} == ${VER_README} ]
   then
      echo -e "\nOK. Versions correct."
   else
		echo -e "\nVersions not match. Correct this (file/version) :\n"
		echo -e "$F_INDEX_PHP\t${VERSION}"
		echo -e "${F_SPEC}\t${VER_SPEC}"
		echo -e "${F_README}\t${VER_README}"
		echo -e "\n"
		exit 10
fi

diff -q ../application/config.ini  ../application/config.ini.original
if [ $? == 0 ]
   then
      echo "OK. config.ini"
   else
      echo -e "\nMake application/config.ini and application/config.ini.original to be identical\n\n"
      exit 11
fi

diff -q ../html/.htaccess  ../html/.htaccess_original
if [ $? == 0 ]
   then
      echo "OK. .htaccess"
   else
      echo -e "\nMake html/.htaccess and html/.htaccess_original to be identical\n\n"
      exit 11
fi

trap my_on_exit  0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15

sudo rm -f /tmp/webacula_restore_*

cd prepare_tests
sudo LANG=C ./clean_all.sh
sudo LANG=C ./prepare.sh
if test $? -ne 0; then
    exit
fi
cd ..


################################################################
# Main tests
################################################################
echo -e "\n\n\n\n\n${LINE1}"
echo "Main tests"
echo -e "${LINE1}\n"
# phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php
cp -f conf/config.ini.mysql  ../application/config.ini
sudo LANG=C phpunit --colors --stop-on-failure AllTests.php
ret=$?
if [ $ret -ne 0 ]
then
    exit $ret
fi

################################################################
# Prepare testing other DBMS
################################################################
echo -e "\n\n${LINE1}"
echo "Prepare testing other DBMS"
echo -e "${LINE1}\n"
cd prepare_tests
sudo ./sync_bacula_db_from_mysql2others.sh
cd ..

################################################################
# Test Postgresql
################################################################
echo -e "\n\n${LINE1}"
echo "Test Postgresql"
echo -e "${LINE1}\n"
cp -f conf/config.ini.pgsql  ../application/config.ini
sudo LANG=C phpunit --exclude-group job-nonreusable,use-bconsole,autochanger,restore-select-job-id --colors --stop-on-failure AllTests.php
ret=$?
if [ $ret -ne 0 ]
then
    exit $ret
fi

################################################################
# Test Sqlite
################################################################
echo -e "\n\n${LINE1}"
echo "Test Sqlite"
echo -e "${LINE1}\n"
cp -f conf/config.ini.sqlite  ../application/config.ini
sudo LANG=C phpunit --exclude-group job-nonreusable,use-bconsole,autochanger,restore-select-job-id --colors --stop-on-failure AllTests.php
ret=$?
if [ $ret -ne 0 ]
then
    exit $ret
fi

cp -f ../application/config.ini.original  ../application/config.ini

echo -e "\n\n"
sudo sh ./locale-test.sh

sudo service postgresql stop

