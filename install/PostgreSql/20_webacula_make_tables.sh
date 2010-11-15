#!/bin/sh
#
# Script to create webacula tables
#

db_name="webacula"

# If necessary change db_user.
# See also application/config.ini

db_user="wbuser"

psql -f webacula_postgresql_make_tables.sql  -d ${db_name} $*
res=$?
if test $res = 0;
then
   echo "Create of webacula PostgreSQL tables succeeded."
else
   echo "Create of webacula PostgreSQL tables failed. Exit."
	exit 1
fi



psql -f - -d ${db_name} $* <<END-OF-DATA

GRANT all ON wbLogBook TO ${db_user};
GRANT all ON wbLogType TO ${db_user};
GRANT all ON wbJobDesc TO ${db_user};
GRANT SELECT, REFERENCES ON wbVersion TO ${db_user};

GRANT SELECT, UPDATE ON wblogbook_logid_seq TO ${db_user};
GRANT SELECT, UPDATE ON wblogtype_typeid_seq TO ${db_user};
GRANT SELECT, UPDATE ON wbjobdesc_desc_id_seq TO ${db_user};

-- execute access
GRANT EXECUTE ON FUNCTION my_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) TO ${db_user};

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Grant of webacula PostgreSQL tables succeeded."
else
   echo "Grant of webacula PostgreSQL tables failed."
fi

exit 0

