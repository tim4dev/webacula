#!/bin/sh
#
# Script to create webacula database(s)
# $Id: webacula_postgresql_create_database.sh 402 2009-08-14 22:29:40Z tim4dev $
#

db_name="webacula"

# If necessary change db_user and db_password.
# See also application/config.ini

db_user="wbuser"
db_password="wbpass"

psql -f - -d template1 $* <<END-OF-DATA
CREATE DATABASE ${db_name} ENCODING 'UTF8';
ALTER DATABASE ${db_name} SET datestyle TO 'ISO, YMD';
END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Creation of ${db_name} database succeeded."
else
   echo "Creation of ${db_name} database failed. Exit."
	exit 1
fi

psql -f - -d ${db_name} $* <<END-OF-DATA
CREATE USER ${db_user} WITH LOGIN PASSWORD '${db_password}';
GRANT ALL ON DATABASE ${db_name} TO ${db_user};
END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Creation of db_user succeeded."
else
   echo "Creation of db_user failed."
fi

exit 0

