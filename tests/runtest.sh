#!/bin/sh
#############################################################
# Usage:
#
# ./runtest.sh --exclude-group nonreusable,restore,logbook
# ./runtest.sh --group test-test
# ./runtest.sh --filter testJobFindByVolumeName
# phpunit --group test1 --stop-on-failure AllTests.php
#
#############################################################

SRC_DIR=".."
F_INDEX_PHP="${SRC_DIR}/html/index.php"
F_README="${SRC_DIR}/README"

VERSION=`grep -e "^.*define('WEBACULA_VERSION.*$" ${F_INDEX_PHP} | awk -F "'" '{print($4)}'`
VER_README=`grep -e "^Version:" ${F_README} | awk '{print($2)}'`

if [ ${VERSION} == ${VER_README} ]
   then
      echo "OK. Versions correct."
   else
      echo -e "\nVersions not match. Correct this (file/version) :\n"
      echo -e "$F_INDEX_PHP\t${VERSION}"
      echo -e "${F_README}\t${VER_README}"
      echo -e "\n"
      exit 10
fi


cd prepare_tests
sudo ./clean_all.sh
sudo ./prepare.sh
cd ..

echo -e "\n\n*******************************************************************************"
echo "Main tests"
echo "*******************************************************************************"
phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php

echo -e "\n\n*******************************************************************************"
echo "Prepare testing other DBMS"
echo "*******************************************************************************"
cd prepare_tests
sudo ./sync_bacula_db_from_mysql2others.sh
cd ..

echo -e "\n\n********** Test Postgresql **********\n"
cp -f conf/config.ini.pgsql  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php

echo -e "\n\n********** Test Sqlite **********\n"
cp -f conf/config.ini.sqlite  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php

cp -f ../application/config.ini.original  ../application/config.ini

