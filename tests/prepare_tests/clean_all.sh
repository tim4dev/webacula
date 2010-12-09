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

/usr/bin/psql -l
if test $? -ne 0; then
    echo "Can't connect to postgresql."
    /sbin/service postgresql start
    sleep 7
fi

/usr/bin/mysqlshow mysql
if test $? -ne 0; then
	echo "Can't connect to mysqld."
	/sbin/service mysqld start
   sleep 7
fi

cd /etc/bacula
./bacula stop
sleep 3

echo -e "\n\n"

rm -r -f /tmp/webacula/sqlite/*
rm -r -f /tmp/webacula/*
rmdir /tmp/webacula

/usr/bin/mysql -f <<END-OF-DATA
	DROP DATABASE IF EXISTS bacula;
END-OF-DATA

if test $? -eq 0 ; then
	echo "Drop MySQL databases succeeded."
else
	echo "Drop MySQL databases failed."
fi


if /usr/bin/dropdb bacula
then
   echo "Drop PGSQL bacula database succeeded."
else
   echo "Drop PGSQL bacula database failed."
fi

rm -f  ../../data/cache/zend_cache*
rm -f  ../../data/tmp/webacula*
rm -f  ../../data/session/ses*

echo -e "\nCLEAN ALL -- OK."

exit 0