#!/bin/bash
# 
# @author Yuriy Timofeev <tim4dev@gmail.com>
# @package webacula
# @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License 
#

BACULADIR="opt/bacula/etc"
TMPDIR="/tmp/webacula"
BASEDIR=`pwd`
INSTALL_DIR="../../install/"
LINE1="*********************************************************************************************"

if [ "$UID" -ne 0 ]
then
    echo -en '\E[37;41m You must be root to run this test. Exit.'
    tput sgr0
    echo -e "\n"
    exit
fi

#echo -e "\n\n*** WARNING!!! Postgresql, Sqlite  Bacula database will be dropped!!!\n"
#echo -e "\n*** Press Enter to continue ...\n\n"
# read


#########################################################
# Function
#
my_log() {
   echo -e "\n\n${LINE1}"
   echo "${1}"
   echo -e "${LINE1}\n\n"
}







#########################################################
# Main program
#########################################################

/usr/bin/mysqlshow -u root mysql > /dev/null
if test $? -ne 0; then
	echo "Can't connect to mysqld."
	/sbin/service mysqld start
   sleep 7
else
    echo "Connect MySql OK"
fi

/usr/bin/psql -l > /dev/null
if test $? -ne 0; then
    echo "Can't connect to postgresql."
    /sbin/service postgresql start
    sleep 7
else
    echo "Connect PostgreSql OK"
fi



createdb --username=root -T template0 -E SQL_ASCII bacula
if test $? -ne 0; then
   echo -en '\E[37;41m PostgreSql : creation of bacula database failed.'
   tput sgr0
   echo -e "\n"
   exit 2
fi

if psql --username=root -q -f - -d bacula <<END-OF-DATA
ALTER DATABASE bacula SET datestyle TO 'ISO, YMD';
END-OF-DATA
then
   echo "PostgreSql : creation of Bacula database succeeded."
else
   echo -en '\E[37;41m PostgreSql : creation of Bacula database failed.'
   tput sgr0
   echo -e "\n"
   exit 2
fi

cd ${BASEDIR}
sh ./PostgreSql/10_bacula_make_tables
sh ./PostgreSql/20_bacula_grant_privileges
cd ${INSTALL_DIR}/PostgreSql
sh ./10_make_tables.sh
sh ./20_acl_make_tables.sh
cd ${BASEDIR}
sh ./PostgreSql/30_webacula_fill_logbook
sh ./PostgreSql/35_webacula_fill_jobdesc
sh ./PostgreSql/40_webacula_fill_acl

cd ${BASEDIR}
sh ./SqLite/10_bacula_make_tables
cd ${INSTALL_DIR}/SqLite
sh ./10_make_tables.sh      /tmp/webacula/sqlite/bacula.db
sh ./20_acl_make_tables.sh  /tmp/webacula/sqlite/bacula.db
cd ${BASEDIR}
sh ./SqLite/20_webacula_fill_logbook
sh ./SqLite/25_webacula_fill_jobdesc
sh ./SqLite/30_webacula_fill_acl

my_log "Copy DB from MySQL to PGSQL ..."
cd ${BASEDIR}
php ./bacula_DBcopy_MySQL2PGSQL.php

my_log "fix PostgreSQL sequence ids"
psql -q -f - -d bacula <<END-OF-DATA
    SET client_min_messages=WARNING;
    select setval('job_jobid_seq', (select max(jobid) + 1 from job));
    select setval('log_logid_seq', (select max(logid) + 1 from log));
END-OF-DATA


my_log "Copy DB from MySQL to Sqlite ..."
cd ${BASEDIR}
php ./bacula_DBcopy_MySQL2sqlite.php

chmod -R a+rwx /tmp/webacula/sqlite
