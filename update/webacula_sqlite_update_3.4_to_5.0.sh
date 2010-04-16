#!/bin/sh
#
# Script to update webacula tables from v.3.4 to 5.0
#

db_name="/var/lib/sqlite/webacula.db"

if [ $# -eq 1 ]
then
   db_name="${1}"
fi

sqlite3 ${db_name} <<END-OF-DATA

DROP TABLE IF EXISTS wbtmptablelist;

END-OF-DATA

# access by apache
chgrp apache ${db_name}
chmod g+rw ${db_name}
exit 0
