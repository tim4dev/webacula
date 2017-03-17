#!/bin/bash
#
# Script to delete webacula tables
#

.   ../db.conf

if [ -n "$db_pwd" ]
then
    pwd="-p$db_pwd"
else
    pwd=""
fi


bindir=/usr/lib/postgresql/9.5/bin
db_name=bacula

$bindir/psql -f - -d ${db_name} $* <<END-OF-DATA

DROP TABLE IF EXISTS webacula_client_acl;
DROP TABLE IF EXISTS webacula_command_acl;
DROP TABLE IF EXISTS webacula_dt_commands;
DROP TABLE IF EXISTS webacula_dt_resources;
DROP TABLE IF EXISTS webacula_fileset_acl;
DROP TABLE IF EXISTS webacula_job_acl;
DROP TABLE IF EXISTS webacula_jobdesc;
DROP TABLE IF EXISTS webacula_logbook;
DROP TABLE IF EXISTS webacula_logtype;
DROP TABLE IF EXISTS webacula_php_session;
DROP TABLE IF EXISTS webacula_pool_acl;
DROP TABLE IF EXISTS webacula_resources;
DROP TABLE IF EXISTS webacula_roles;
DROP TABLE IF EXISTS webacula_schedule_acl;
DROP TABLE IF EXISTS webacula_storage_acl;
DROP TABLE IF EXISTS webacula_tmp_tablelist;
DROP TABLE IF EXISTS webacula_users;
DROP TABLE IF EXISTS webacula_version;
DROP TABLE IF EXISTS webacula_where_acl;

END-OF-DATA
pstat=$?
if test $pstat = 0; 
then
   echo "Deletion of Webacula PostgreSQL tables succeeded."
else
   echo "Deletion of Webacula PostgreSQL tables failed."
fi
exit $pstat
