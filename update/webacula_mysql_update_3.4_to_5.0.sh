#!/bin/sh
#
# Script to update webacula tables from v.3.4 to 5.0
#

db_name="webacula"

if mysql $* -f <<END-OF-DATA

USE webacula;

DROP TABLE IF EXISTS wbtmptablelist;

END-OF-DATA
then
   echo "Update webacula MySQL tables succeeded."
else
   echo "Update webacula MySQL tables failed."
fi
exit 0

