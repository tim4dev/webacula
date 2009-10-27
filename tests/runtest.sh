#!/bin/sh
#############################################################
#
# Main script for unit tests
#
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
F_SPEC="${SRC_DIR}/packaging/Fedora/webacula.spec"
LINE1="*********************************************************************************************"

VERSION=`grep -e "^.*define('WEBACULA_VERSION.*$" ${F_INDEX_PHP} | awk -F "'" '{print($4)}'`
VER_README=`grep -e "^Version:" ${F_README} | awk '{print($2)}'`
VER_SPEC=`grep -e "^Version:" ${F_SPEC} | awk '{print($2)}'`

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

echo ""
diff -q ../application/config.ini  ../application/config.ini.original
if [ $? == 0 ]
   then
      echo "OK. config.ini"
   else
      echo -e "\nMake cp ../application/config.ini ../application/config.ini.original\n\n"
      exit 11
fi


cd prepare_tests
sudo ./clean_all.sh
sudo ./prepare.sh
cd ..

# Main tests
echo -e "\n\n${LINE1}"
echo "Main tests"
echo -e "${LINE1}\n"
phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php
echo "ret=$?"
if test $? -ne 0; then
    exit $?
fi

# Prepare testing other DBMS
echo -e "\n\n${LINE1}"
echo "Prepare testing other DBMS"
echo -e "${LINE1}\n"
cd prepare_tests
sudo ./sync_bacula_db_from_mysql2others.sh
cd ..

# Test Postgresql
echo -e "\n\n${LINE1}"
echo "Test Postgresql"
echo -e "${LINE1}\n"
cp -f conf/config.ini.pgsql  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php
echo "ret=$?"
if test $? -ne 0; then
    exit $?
fi


# Test Sqlite
echo -e "\n\n${LINE1}"
echo "Test Sqlite"
echo -e "${LINE1}\n"
cp -f conf/config.ini.sqlite  ../application/config.ini
phpunit --exclude-group nonreusable --colors --stop-on-failure AllTests.php
echo "ret=$?"
if test $? -ne 0; then
    exit $?
fi

cp -f ../application/config.ini.original  ../application/config.ini



echo -e "\n\n"
sh ./locale-test.sh

