#!/bin/bash

###########################################################
# Main program
###########################################################

if [ "$UID" -ne 0 ]
then
        echo -e "\nYou must be root to run this test.\nExit.\n"
        exit
fi

echo -e "\n\n*** WARNING!!! All Bacula, Webacula databases and files will be erased!!!\n"
echo -e "*** Press Enter to continue ...\n\n"
read

/usr/bin/mysqlshow -u root mysql > /dev/null
if test $? -ne 0; then
	echo "Can't connect to mysqld."
	/sbin/service mysqld start
   sleep 7
else
    echo "Connect MySql OK"
fi

/usr/bin/psql -l > /dev/null
if test $? -ne 0; then
    echo "Can't connect to postgresql."
    /sbin/service postgresql start
    sleep 7
else
    echo "Connect PostgreSql OK"
fi


cd /opt/bacula/etc
./bacula stop
sleep 3

echo -e "\n\n"

rm -r -f /tmp/webacula/sqlite/*
rm -r -f /tmp/webacula/*
rmdir /tmp/webacula

/usr/bin/mysql -u root -f <<END-OF-DATA
	DROP DATABASE IF EXISTS bacula;
END-OF-DATA

if test $? -eq 0 ; then
	echo "Drop MySQL databases succeeded."
else
    echo -en '\E[37;41m Drop MySQL databases failed.'
    tput sgr0
    echo -e "\n"
fi


if /usr/bin/dropdb bacula
then
   echo "Drop PGSQL bacula database succeeded."
else
   echo -en '\E[37;41m Drop PGSQL bacula database failed.'
   tput sgr0
   echo -e "\n"
fi

rm -f  ../../data/cache/zend_cache*
rm -f  ../../data/tmp/webacula*
rm -f  ../../data/session/ses*

echo -e "\nCLEAN ALL -- OK."

exit 0
