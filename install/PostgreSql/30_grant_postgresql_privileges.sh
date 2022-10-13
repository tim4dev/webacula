#!/bin/sh
#
# Script TO grant privileges TO the bacula database
#
db_user=${db_user:-bacula}
pg_config=`which pg_config`
bindir=`pg_config --bindir`
PATH="$bindir:$PATH"
db_name=${db_name:-bacula}


psql -q -f - -d ${db_name} $* <<END-OF-DATA

SET client_min_messages=WARNING;

-- Grants for the database
ALTER DATABASE ${db_name} OWNER TO ${db_user} ;



-- Grants for tables
GRANT ALL ON webacula_client_acl      TO ${db_user};
GRANT ALL ON webacula_command_acl     TO ${db_user};
GRANT ALL ON webacula_dt_commands     TO ${db_user};
GRANT ALL ON webacula_dt_resources    TO ${db_user};
GRANT ALL ON webacula_fileset_acl     TO ${db_user};
GRANT ALL ON webacula_job_acl         TO ${db_user};
GRANT ALL ON webacula_job_size        TO ${db_user};
GRANT ALL ON webacula_jobdesc         TO ${db_user};
GRANT ALL ON webacula_logbook         TO ${db_user};
GRANT ALL ON webacula_logtype         TO ${db_user};
GRANT ALL ON webacula_php_session     TO ${db_user};
GRANT ALL ON webacula_pool_acl        TO ${db_user};
GRANT ALL ON webacula_resources       TO ${db_user};
GRANT ALL ON webacula_roles           TO ${db_user};
GRANT ALL ON webacula_storage_acl     TO ${db_user};
GRANT ALL ON webacula_tmp_tablelist   TO ${db_user};
GRANT ALL ON webacula_users           TO ${db_user};
GRANT ALL ON webacula_version         TO ${db_user};
GRANT ALL ON webacula_where_acl       TO ${db_user};



-- Grants for sequences on those tables
GRANT SELECT, UPDATE ON webacula_client_acl_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_command_acl_id_seq  TO ${db_user};
GRANT SELECT, UPDATE ON webacula_dt_commands_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_dt_resources_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_fileset_acl_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_job_acl_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_jobdesc_desc_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_logbook_logid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_logtype_typeid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_pool_acl_id_seq  TO ${db_user};
GRANT SELECT, UPDATE ON webacula_resources_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_roles_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_storage_acl_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_tmp_tablelist_tmpid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_users_id_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_where_acl_id_seq TO ${db_user};



-- Grants for functions
GRANT EXECUTE ON FUNCTION webacula_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) TO ${db_user};
GRANT EXECUTE ON FUNCTION elt(pos int4, str VARIADIC text[]) TO ${db_user};
GRANT EXECUTE ON FUNCTION base64_decode_lstat(pos int4, str varchar) TO ${db_user};
GRANT EXECUTE ON FUNCTION human_size(bytes numeric) TO ${db_user};

END-OF-DATA

if [ $? -eq 0 ]
then
   echo "PostgreSQL: Privileges for user ${db_user} granted successfully on database ${db_name}."
else
   echo "PostgreSQL: Privileges for user ${db_user} granted failed on database ${db_name}!"
   exit 1
fi
exit 0
