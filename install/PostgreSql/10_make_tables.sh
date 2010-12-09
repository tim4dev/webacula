#!/bin/sh
#
# Script to create webacula tables
#

# If necessary change db_user.
# See also application/config.ini

db_user="root"

psql -f make_tables.sql  -d bacula $*
res=$?
if test $res = 0;
then
   echo "Create of Webacula PostgreSQL tables succeeded."
else
   echo "Create of Webacula PostgreSQL tables failed. Exit."
   exit 1
fi



psql -f - -d bacula $* <<END-OF-DATA

GRANT all ON webacula_logbook TO ${db_user};
GRANT all ON webacula_logtype TO ${db_user};
GRANT all ON webacula_jobdesc TO ${db_user};
GRANT SELECT, REFERENCES ON webacula_version TO ${db_user};

GRANT SELECT, UPDATE ON webacula_logbook_logid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_logtype_typeid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_jobdesc_desc_id_seq TO ${db_user};

-- execute access
GRANT EXECUTE ON FUNCTION webacula_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) TO ${db_user};

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "Grant of Webacula PostgreSQL tables succeeded."
else
   echo "Grant of Webacula PostgreSQL tables failed."
fi



psql -f make_acl_tables.sql  -d bacula $*
res=$?
if test $res = 0;
then
   echo "Create of Webacula ACL PostgreSQL tables succeeded."
else
   echo "Create of Webacula ACL PostgreSQL tables failed. Exit."
   exit 1
fi

exit 0
