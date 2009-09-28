#!/bin/sh
#############################################################
# Usage:
#
# ./runtest.sh --exclude-group nonreusable,restore,logbook
# ./runtest.sh --group test-test
# ./runtest.sh --filter testJobFindByVolumeName
#
#############################################################

cd prepare_tests
sudo ./clean_all.sh
sudo ./prepare.sh
cd ..

phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php

echo "Prepare testing other DBMS"
cd prepare_tests
sudo ./sync_bacula_db_from_mysql2others.sh
cd ..

echo "Test Postgresql"
cp -f conf/config.ini.pgsql  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php

echo "Test Sqlite"
cp -f conf/config.ini.sqlite  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php

cp -f ../application/config.ini.original  ../application/config.ini

