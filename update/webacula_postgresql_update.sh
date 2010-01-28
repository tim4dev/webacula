#!/bin/sh
#
# Script to update webacula tables from v.3.x to 3.4
#

db_name="webacula"

# If necessary change db_user.
# See also application/config.ini

db_user="wbuser"

psql -f - -d ${db_name} $* <<END-OF-DATA

GRANT all ON wbJobDesc TO ${db_user};
GRANT SELECT, UPDATE ON wbjobdesc_desc_id_seq TO ${db_user};

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Update webacula PostgreSQL tables succeeded."
else
   echo "Update webacula PostgreSQL tables failed."
fi

exit 0

