#!/bin/sh
#
# Script to create webacula database(s)
# $Id: webacula_mysql_create_database.sh 402 2009-08-14 22:29:40Z tim4dev $
#

db_name="webacula"

# If necessary change db_user and db_password.
# See also application/config.ini

db_user="wbuser"
db_password="wbpass"
host="localhost"

if mysql $* -f <<END-OF-DATA
CREATE DATABASE ${db_name};

GRANT ALL PRIVILEGES ON ${db_name}.* TO ${db_user}@${host} IDENTIFIED BY '${db_password}';
FLUSH PRIVILEGES;
END-OF-DATA
then
   echo "Creation of ${db_name} database succeeded."
else
   echo "Creation of ${db_name} database failed."
fi
exit 0

