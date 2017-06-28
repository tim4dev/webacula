#!/bin/sh
#
# shell script to grant privileges to the bacula database
#
# Copyright (C) 2000-2015 Kern Sibbald
# License: BSD 2-Clause; see file LICENSE-FOSS
#
db_user=${db_user:-bacula}
bindir=/usr/bin
PATH="$bindir:$PATH"
db_name=${db_name:-bacula}
db_password=bacula
if [ "$db_password" != "" ]; then
   pass="password '$db_password'"
fi


psql -f - -d ${db_name} $* <<END-OF-DATA

create user ${db_user} ${pass};

-- for the database
alter database ${db_name} owner to ${db_user} ;

-- for tables
grant all on webacula_client_acl      to ${db_user};
grant all on webacula_command_acl     to ${db_user};
grant all on webacula_dt_commands     to ${db_user};
grant all on webacula_dt_resources    to ${db_user};
grant all on webacula_fileset_acl     to ${db_user};
grant all on webacula_job_acl         to ${db_user};
grant all on webacula_jobdesc         to ${db_user};
grant all on webacula_logbook         to ${db_user};
grant all on webacula_logtype         to ${db_user};
grant all on webacula_php_session     to ${db_user};
grant all on webacula_pool_acl        to ${db_user};
grant all on webacula_resources       to ${db_user};
grant all on webacula_roles           to ${db_user};
grant all on webacula_schedule_acl    to ${db_user};
grant all on webacula_storage_acl     to ${db_user};
grant all on webacula_tmp_tablelist   to ${db_user};
grant all on webacula_users           to ${db_user};
grant all on webacula_version         to ${db_user};
grant all on webacula_where_acl       to ${db_user};
grant all on webacula_schedule_acl    to ${db_user};


-- for sequences on those tables
GRANT select, update on webacula_client_acl_id_seq to ${db_user};
GRANT select, update on webacula_command_acl_id_seq  to ${db_user};
GRANT select, update on webacula_dt_commands_id_seq to ${db_user};
GRANT select, update on webacula_dt_resources_id_seq to ${db_user};
GRANT select, update on webacula_fileset_acl_id_seq to ${db_user};
GRANT select, update on webacula_job_acl_id_seq to ${db_user};
GRANT select, update on webacula_jobdesc_desc_id_seq to ${db_user};
GRANT select, update on webacula_logbook_logid_seq to ${db_user};
GRANT select, update on webacula_logtype_typeid_seq to ${db_user};
GRANT select, update on webacula_pool_acl_id_seq  to ${db_user};
GRANT select, update on webacula_resources_id_seq to ${db_user};
GRANT select, update on webacula_roles_id_seq to ${db_user};
GRANT select, update on webacula_storage_acl_id_seq to ${db_user};
GRANT select, update on webacula_tmp_tablelist_tmpid_seq to ${db_user};
GRANT select, update on webacula_users_id_seq to ${db_user};
GRANT select, update on webacula_where_acl_id_seq to ${db_user};
GRANT select, update on webacula_schedule_acl_id_seq to ${db_user};

END-OF-DATA
if [ $? -eq 0 ]
then
   echo "Privileges for user ${db_user} granted on database ${db_name}."
   exit 0
else
   echo "Error creating privileges."
   exit 1
fi
