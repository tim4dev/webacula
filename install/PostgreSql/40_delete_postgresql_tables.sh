#!/bin/bash
#
# Script to grant privileges
#
db_user=${db_user:-bacula}
db_name=${db_name:-bacula}
pg_config=`which pg_config`
bindir=`pg_config --bindir`
PATH="$bindir:$PATH"



psql -q -f - -d ${db_name} $* <<END-OF-DATA

SET client_min_messages=WARNING;

DROP TABLE IF EXISTS webacula_client_acl;
DROP TABLE IF EXISTS webacula_command_acl;
DROP TABLE IF EXISTS webacula_dt_commands;
DROP TABLE IF EXISTS webacula_dt_resources;
DROP TABLE IF EXISTS webacula_fileset_acl;
DROP TABLE IF EXISTS webacula_job_acl;
DROP TABLE IF EXISTS webacula_job_size;
DROP TABLE IF EXISTS webacula_jobdesc;
DROP TABLE IF EXISTS webacula_logbook;
DROP TABLE IF EXISTS webacula_logtype;
DROP TABLE IF EXISTS webacula_php_session;
DROP TABLE IF EXISTS webacula_pool_acl;
DROP TABLE IF EXISTS webacula_resources;
DROP TABLE IF EXISTS webacula_roles;
DROP TABLE IF EXISTS webacula_storage_acl;
DROP TABLE IF EXISTS webacula_tmp_tablelist;
DROP TABLE IF EXISTS webacula_users;
DROP TABLE IF EXISTS webacula_version;
DROP TABLE IF EXISTS webacula_where_acl;
DROP FUNCTION IF EXISTS webacula_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT);
DROP FUNCTION IF EXISTS elt(int, VARIADIC text[]);
DROP FUNCTION IF EXISTS base64_decode_lstat(int4, varchar);
DROP FUNCTION IF EXISTS human_size(bytes numeric);

END-OF-DATA

if [ $? -eq 0 ]
then
   echo "PostgreSQL: Exclusion of Webacula tables were successful."
else
   echo "PostgreSQL: Exclusion of Webacula tables were failed!"
   exit 1
fi
exit 0
