#!/bin/bash
#
# Prepare Bacula environment and run simple backups
#

if [ "$UID" -ne 0 ]
then
	echo -e "\nYou must be root to run this test.\nExit.\n"
	exit
fi

BACULADIR="etc/bacula"
TMPDIR="/tmp/webacula"
BASEDIR=`pwd`

#########################################################
# Function
#
my_log() {
	echo -e "\n\n***********************************************************\n"
	echo "${1}"
	echo -e "***********************************************************\n\n"
}

# 1 - bacula log
# 2 - msg
my_check_log()
{
	grep "^  Termination: *Backup OK" ${1} 2>&1 >/dev/null
	if test $? -eq 0; then
		echo -e "\n${2} OK\n"
		#echo "Press Enter..."
		#read
		#sleep 10
	fi
	grep "^  Termination: .*Backup Error" ${1} 2>&1 >/dev/null
	if test $? -eq 0; then
		echo -e "\n${2} ERROR!!!\n"
		exit
		#echo "Press Enter..."
		#read
		#sleep 10
	fi
}



#########################################################
# Main program
#

my_log "Check PostgreSql..."
/usr/bin/psql -l
if test $? -ne 0; then
	echo "Can't connect to postgresql."
	/sbin/service postgresql start
fi

my_log "Check MySql..."
/usr/bin/mysqlshow mysql
if test $? -ne 0; then
	echo "Can't connect to mysqld."
	/sbin/service mysqld start
fi





my_log "Make test"



my_log "Create directory structure ..."
mkdir "${TMPDIR}"
mkdir "${TMPDIR}/tmp"
mkdir "${TMPDIR}/log"
mkdir "${TMPDIR}/restore"
mkdir "${TMPDIR}/dev"
mkdir "${TMPDIR}/test"
mkdir "${TMPDIR}/test/1"
mkdir "${TMPDIR}/test/2"
mkdir "${TMPDIR}/test/3"
mkdir "${TMPDIR}/test/3/subdir"



my_log "Create Bacula conf ..."
mv -f "/${BACULADIR}/bacula-dir.conf" "/${BACULADIR}/bacula-dir.conf.original"
mv -f "/${BACULADIR}/bacula-sd.conf"  "/${BACULADIR}/bacula-sd.conf.original"
mv -f "/${BACULADIR}/bacula-fd.conf"  "/${BACULADIR}/bacula-fd.conf.original"
mv -f "/${BACULADIR}/bconsole.conf"   "/${BACULADIR}/bconsole.conf.original"

SRC="${BACULADIR}/*.conf"
DST="/${BACULADIR}"
echo "${SRC}"
echo "${DST}"

cp -f $SRC $DST

SRC="${BACULADIR}/scripts/*.sh"
DST="/${BACULADIR}/scripts/"
mkdir  "${DST}"
cp -f $SRC      $DST



my_log "Create Bacula and Webacula databases ..."

cd ${BASEDIR}
sh ./bacula_mysql_make_tables
sh ./bacula_sqlite_make_tables

cd ../install/

sh ./webacula_mysql_create_database.sh
sh ./webacula_mysql_make_tables.sh
sh ./webacula_postgresql_create_database.sh
sh ./webacula_postgresql_make_tables.sh
sh ./webacula_sqlite_create_database.sh "/tmp/webacula/sqlite/webacula.db"
chmod a+rwx /tmp/webacula/sqlite

cd -


my_log "Testing Configuration Files ..."

/sbin/bacula-dir -t -c /${BACULADIR}/bacula-dir.conf
/sbin/bacula-fd  -t -c /${BACULADIR}/bacula-fd.conf
/sbin/bacula-sd  -t -c /${BACULADIR}/bacula-sd.conf
/sbin/bconsole   -t -c /${BACULADIR}/bconsole.conf

rm -r -f ${TMPDIR}/tmp/*



my_log "Create dir tree ..."

# Usage : <dir> <max files>
cd ${BASEDIR}
php ./create_dir_tree.php "${TMPDIR}/test/1" 3000
dd if=/dev/zero of="${TMPDIR}/test/2/file21.dat"  bs=1024 count=1000
dd if=/dev/zero of="${TMPDIR}/test/2/file22.dat"  bs=1024 count=500
dd if=/dev/zero of="${TMPDIR}/test/3/file31.dat"  bs=1024 count=300
dd if=/dev/zero of="${TMPDIR}/test/3/subdir/file_test41.dat" bs=1024 count=600
dd if=/dev/zero of="${TMPDIR}/test/3/subdir/file_test42.dat" bs=1024 count=500



my_log "Run backup 1 ..."

cd "/${BACULADIR}"
./bacula start

/sbin/bconsole -c /etc/bacula/bconsole.conf<<END_OF_DATA
@output /dev/null
messages
@output ${TMPDIR}/log/01.log
run job="job.name.test.1" level=Full yes
@sleep 1
run job="job name test 2" level=Full yes
@sleep 1
run job="job-name-test-3" level=Full yes
@sleep 1
wait
messages
quit
END_OF_DATA

my_check_log "${TMPDIR}/log/01.log" "backup 1"

echo "Wait 2 min..."
sleep 130


my_log "Run backup 2 ..."

dd if=/dev/zero of="${TMPDIR}/test/1/file_new11.dat"  bs=1024 count=300
dd if=/dev/zero of="${TMPDIR}/test/2/file_new23.dat"  bs=1024 count=400
dd if=/dev/zero of="${TMPDIR}/test/3/file_new31.dat"  bs=1024 count=500


/sbin/bconsole -c /etc/bacula/bconsole.conf<<END_OF_DATA
@output /dev/null
messages
@output ${TMPDIR}/log/02.log
run job="job.name.test.1" level=Incremental yes
@sleep 1
run job="job name test 2" level=Differential yes
@sleep 1
run job="job-name-test-3" level=Incremental yes
@sleep 1
wait
messages
quit
END_OF_DATA

my_check_log "${TMPDIR}/log/02.log" "backup 2"

echo "Wait 2 min..."
sleep 130


my_log "Run backup 3 ..."

dd if=/dev/zero of="${TMPDIR}/test/1/file_new12.dat"  bs=1024 count=350
dd if=/dev/zero of="${TMPDIR}/test/2/file_new24.dat"  bs=1024 count=450
dd if=/dev/zero of="${TMPDIR}/test/3/file_new32.dat"  bs=1024 count=550

/sbin/bconsole -c /etc/bacula/bconsole.conf<<END_OF_DATA
@output /dev/null
messages
@output ${TMPDIR}/log/03.log
@sleep 1
run job="job.name.test.1" level=Differential yes
@sleep 1
run job="job name test 2" level=Incremental yes
@sleep 1
run job="job-name-test-3" level=Incremental yes
@sleep 1
run job="job.name.test.4" level=Incremental yes
@sleep 1
run job="job.name.test.4" level=Incremental yes
wait
messages
@output ${TMPDIR}/log/volumes.log
list volumes
@output ${TMPDIR}/log/jobs.log
list jobs
quit
END_OF_DATA

my_check_log "${TMPDIR}/log/03.log" "backup 3"


#echo "Press Enter..."
#read
rm -r -f  ${TMPDIR}/log/*


my_log "Make Job with errors"
if mysql -f <<END-OF-DATA
USE bacula;
update Job set JobErrors=99 where JobId=2;
update Job set JobErrors=9  where JobId=10;
update Job set JobStatus='R'  where JobId=11;
END-OF-DATA
then
	echo "Succeeded."
else
   echo "Failed."
fi



my_log "Create Bacula PostgreSQL tables"

# ENCODING 'UTF8'

if /usr/bin/psql -f - -d template1 <<END-OF-DATA
CREATE DATABASE bacula ENCODING 'SQL_ASCII';
ALTER DATABASE bacula SET datestyle TO 'ISO, YMD';
END-OF-DATA
then
   echo "Creation of bacula database succeeded."
else
   echo "Creation of bacula database failed."
fi

cd ${BASEDIR}
sh ./bacula_postgresql_make_tables
sh ./bacula_postgresql_grant_privileges



my_log "Copy DB from MySQL to PGSQL ..."
cd ${BASEDIR}
php ./bacula_DBcopy_MySQL2PGSQL.php

my_log "Copy DB from MySQL to Sqlite ..."
cd ${BASEDIR}
php ./bacula_DBcopy_MySQL2sqlite.php

my_log "MySQL : fill webacula logbook"
cd ${BASEDIR}
sh ./webacula_mysql_fill_logbook

my_log "PostgreSQL : fill webacula logbook"
cd ${BASEDIR}
sh ./webacula_postgresql_fill_logbook

my_log "Sqlite : fill webacula logbook"
cd ${BASEDIR}
sh ./webacula_sqlite_fill_logbook


