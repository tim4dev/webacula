#!/bin/bash
#
# Script to create webacula tables
#

.   ../db.conf

if [ -n "$db_pwd" ]
then
    pwd="-p$db_pwd"
else
    pwd=""
fi


if mysql $* -u $db_user $pwd  $db_name -f <<END-OF-DATA

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
then
   echo "Exclusion of webacula MySQL tables succeeded."
else
   echo "Exclusion of webacula MySQL tables failed."
fi
exit 0
