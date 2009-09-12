#!/bin/sh
#
# Script to create webacula tables
# $Id: webacula_postgresql_make_tables.sh 405 2009-08-17 19:51:14Z tim4dev $
#

bindir="/usr/bin"
db_name="webacula"

# If necessary change db_user.
# See also application/config.ini

db_user="wbuser"

$bindir/psql -f webacula_postgresql_make_tables.sql  -d ${db_name} $*
res=$?
if test $res = 0;
then
   echo "Create of webacula PostgreSQL tables succeeded."
else
   echo "Create of webacula PostgreSQL tables failed. Exit."
	exit 1
fi



$bindir/psql -f - -d ${db_name} $* <<END-OF-DATA

GRANT all ON wbLogBook TO ${db_user};
GRANT all ON wbLogType TO ${db_user};
GRANT SELECT, REFERENCES ON wbVersion TO ${db_user};
GRANT all ON wbtmptablelist TO ${db_user};

GRANT SELECT, UPDATE ON wblogbook_logid_seq TO ${db_user};
GRANT SELECT, UPDATE ON wblogtype_typeid_seq TO ${db_user};
GRANT SELECT, UPDATE ON wbtmptablelist_tmpid_seq TO ${db_user};

-- execute access
GRANT EXECUTE ON FUNCTION my_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) TO ${db_user};
GRANT EXECUTE ON FUNCTION my_clone_filename(vTbl TEXT, vFilenameId INT, vName TEXT) TO ${db_user};
GRANT EXECUTE ON FUNCTION my_clone_path(vTbl TEXT, vPathId INT, vPath TEXT) TO ${db_user};

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Grant of webacula PostgreSQL tables succeeded."
else
   echo "Grant of webacula PostgreSQL tables failed."
fi

exit 0

