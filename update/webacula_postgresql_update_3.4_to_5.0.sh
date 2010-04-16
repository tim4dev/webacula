#!/bin/sh
#
# Script to update webacula tables from v.3.4 to 5.0
#

db_name="webacula"

# If necessary change db_user.
# See also application/config.ini

db_user="wbuser"

psql -f - -d ${db_name} $* <<END-OF-DATA

DROP TABLE IF EXISTS wbtmptablelist;

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Update webacula PostgreSQL tables succeeded."
else
   echo "Update webacula PostgreSQL tables failed."
fi

exit 0

