#!/bin/bash
#
# Script to grant privileges 
#
db_name=bacula

mysql $* -f <<END-OF-DATA
USE ${db_name};

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
DROP TABLE IF EXISTS webacula_schedule_acl;
DROP TABLE IF EXISTS webacula_storage_acl;
DROP TABLE IF EXISTS webacula_tmp_tablelist;
DROP TABLE IF EXISTS webacula_users;
DROP TABLE IF EXISTS webacula_version;
DROP TABLE IF EXISTS webacula_where_acl;
DROP FUNCTION IF EXISTS base64_decode_lstat;
DROP FUNCTION IF EXISTS human_size;

END-OF-DATA

if [ $? -eq 0 ]
then
   echo "MySQL: Exclusion of Webacula tables were successful."
else
   echo "MySQL: Exclusion of Webacula tables were failed!"
   exit 1
fi
exit 0
