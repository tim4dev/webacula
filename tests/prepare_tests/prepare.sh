#!/bin/bash
#
# Prepare Bacula environment and run simple backups
#
# @author Yuriy Timofeev <tim4dev@gmail.com>
# @package webacula
# @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License 
#

if [ "$UID" -ne 0 ]
then
	echo -e "\nYou must be root to run this test.\nExit.\n"
	exit
fi

BCONSOLE="/opt/bacula/sbin/bconsole"
BACULADIR="opt/bacula/etc"
TMPDIR="/tmp/webacula"
BASEDIR=`pwd`
INSTALL_DIR="../../install/"
DELAYJ=3
LINE1="*********************************************************************************************"

#########################################################
# Function
#
my_log() {
	echo -e "\n\n${LINE1}"
	echo "${1}"
	echo -e "${LINE1}\n\n"
}

# 1 - bacula log
# 2 - msg
my_check_log()
{
    grep "^  Termination: *Backup OK" ${1} 2>&1 >/dev/null
    if test $? -eq 0; then
        echo -e "\n=== ${2} OK ===\n"
        return 0
    fi
    grep "^  Termination: .*Backup Error" ${1} 2>&1 >/dev/null
    if test $? -eq 0; then
        echo -e "\n=== ${2} ERROR!!! ===\n"
        exit
    fi
    echo -e "\n=== ${2} UNKNOWN error or other nonsense ! ===\n"
    exit
}


# check return code
# exit if != 0
my_check_rc() {
    rc=$?
    if test $rc -ne 0; then
        echo "ERROR! Return code = ${rc}"
        exit $rc
    fi
}


my_on_exit() {
    cd $BASEDIR
    echo "$0 output:"
    echo "clean and exit"
    cp -f ../../application/config.ini.original  ../../application/config.ini
    cp -f ../../html/.htaccess_original  ../../html/.htaccess
    rm -f cookies.txt
    rm -f  ../../data/cache/zend_cache*
    rm -f  ../../data/tmp/webacula*
    rm -f  ../../data/session/ses*
}





#########################################################
# Main program
#########################################################

trap my_on_exit  0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15

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
    # clean all other connections
    /sbin/service postgresql restart
    echo "Connect PostgreSql OK"
fi

chmod a+rx ${BCONSOLE}



my_log "Make test"



my_log "Create directory structure ..."
mkdir "${TMPDIR}"
mkdir "${TMPDIR}/tmp"
mkdir "${TMPDIR}/log"
mkdir "${TMPDIR}/restore"
mkdir "${TMPDIR}/dev"
mkdir "${TMPDIR}/dev/devchanger"
mkdir "${TMPDIR}/test"
mkdir "${TMPDIR}/test/1"
mkdir "${TMPDIR}/test/2"
mkdir "${TMPDIR}/test/3"
mkdir "${TMPDIR}/test/3/subdir"
mkdir "${TMPDIR}/test/4"
echo "Done."



my_log "Backup exesting Bacula config ..."
mv -f "/${BACULADIR}/bacula-dir.conf" "/${BACULADIR}/bacula-dir.conf.original"
mv -f "/${BACULADIR}/bacula-sd.conf"  "/${BACULADIR}/bacula-sd.conf.original"
mv -f "/${BACULADIR}/bacula-fd.conf"  "/${BACULADIR}/bacula-fd.conf.original"
mv -f "/${BACULADIR}/bconsole.conf"   "/${BACULADIR}/bconsole.conf.original"

my_log "Create Bacula conf ..."
# copy config files
SRC="${BACULADIR}/*.conf"
DST="/${BACULADIR}"
echo "src = ${SRC}"
echo "dst = ${DST}"
cp -f $SRC $DST

# copy scripts
SRC="${BACULADIR}/scripts/*.sh"
DST="/${BACULADIR}/scripts/"
mkdir  "${DST}"
cp -f $SRC      $DST



my_log "Create tables ..."

cd ${BASEDIR}
sh ./MySql/10_bacula_make_tables
cd ${INSTALL_DIR}/MySql
my_log "MySQL"
sh ./10_make_tables.sh
sh ./20_acl_make_tables.sh



my_log "Create fake autochanger..."
cd ${BASEDIR}
my_check_rc
cp -f dev/devchanger/*  "${TMPDIR}/dev/devchanger"
my_check_rc
cd ${TMPDIR}/dev/devchanger
my_check_rc
# create 5 volumes
for i in `seq 1 5`; do
    echo $i:vol$i >> barcodes
    cp /dev/null slot$i
done
# make a cleaning tape
echo 6:CLN01 >> barcodes
cp /dev/null slot6
# keep other empty
for i in `seq 7 9`; do
    echo $i:  >> barcodes
done

echo "Done."



cd ${BASEDIR}

my_log "Testing Configuration Files ..."

/opt/bacula/sbin/bacula-dir -t -c /${BACULADIR}/bacula-dir.conf
my_check_rc
/opt/bacula/sbin/bacula-fd  -t -c /${BACULADIR}/bacula-fd.conf
my_check_rc
/opt/bacula/sbin/bacula-sd  -t -c /${BACULADIR}/bacula-sd.conf
my_check_rc
${BCONSOLE}   -t -c /${BACULADIR}/bconsole.conf
my_check_rc

rm -r -f ${TMPDIR}/tmp/*
echo "Done."


my_log "Create dir tree ..."

# Usage : <dir> <max files>
cd ${BASEDIR}
php ./create_dir_tree.php "${TMPDIR}/test/1" 1000 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/2/file21.dat"  bs=1024 count=1000 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/2/file22.dat"  bs=1024 count=500 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/3/file31.dat"  bs=1024 count=300 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/3/subdir/file_test41.dat" bs=1024 count=600 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/3/subdir/file_test42.dat" bs=1024 count=500 > /dev/null 2>&1
my_check_rc
dd if=/dev/zero of="${TMPDIR}/test/4/test41.txt" bs=1024 count=5 > /dev/null 2>&1
my_check_rc
echo "Done."


my_log "Import data for Win32 backup ..."
cp -f dev/pool.file.7d.0001 "${TMPDIR}/dev/"
mysql -u root bacula < catalog/bacula.mysql.dump
my_check_rc
echo "OK."

my_log "bacula start ..."
cd "/${BACULADIR}"
./bacula start
sleep 3



my_log "Run backup 1 ..."

${BCONSOLE} -c /$BACULADIR/bconsole.conf<<END_OF_DATA
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

echo "Wait ${DELAYJ} sec..."
sleep ${DELAYJ}


my_log "Run backup 2 ..."

dd if=/dev/zero of="${TMPDIR}/test/1/file_new11.dat"  bs=1024 count=300 > /dev/null 2>&1
dd if=/dev/zero of="${TMPDIR}/test/2/file_new23.dat"  bs=1024 count=400 > /dev/null 2>&1
dd if=/dev/zero of="${TMPDIR}/test/3/file_new31.dat"  bs=1024 count=500 > /dev/null 2>&1


${BCONSOLE} -c /$BACULADIR/bconsole.conf<<END_OF_DATA
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

echo "Wait ${DELAYJ} sec..."
sleep ${DELAYJ}


my_log "Run backup 3 ..."

dd if=/dev/zero of="${TMPDIR}/test/1/file_new12.dat"  bs=1024 count=350 > /dev/null 2>&1
dd if=/dev/zero of="${TMPDIR}/test/2/file_new24.dat"  bs=1024 count=450 > /dev/null 2>&1
dd if=/dev/zero of="${TMPDIR}/test/3/file_new32.dat"  bs=1024 count=550 > /dev/null 2>&1

${BCONSOLE} -c /$BACULADIR/bconsole.conf<<END_OF_DATA
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
@sleep 1
run job="job.name.test.5.single" level=Full yes
wait
messages
@output ${TMPDIR}/log/volumes.log
list volumes
@output ${TMPDIR}/log/jobs.log
list jobs
quit
END_OF_DATA

my_check_log "${TMPDIR}/log/03.log" "backup 3"


my_log "Run backup 4 to autochanger ..."
${BCONSOLE} -c /$BACULADIR/bconsole.conf<<END_OF_DATA
@output /dev/null
messages
@output ${TMPDIR}/log/04.log
label barcodes pool=Default slots=1-4 storage=LTO1 drive=0
yes
messages
@sleep 1
run storage=LTO1 job="job.name.test.autochanger.1" level=Full    yes
wait
messages
quit
END_OF_DATA

my_check_log "${TMPDIR}/log/04.log" "backup 4 autochanger"


# rm -r -f  ${TMPDIR}/log/*


my_log "Make Job with errors"
if mysql -f <<END-OF-DATA
USE bacula;
update Job set JobErrors=99 where JobId=3;
update Job set JobErrors=9  where JobId=11;
update Job set JobStatus='R'  where JobId=12;
END-OF-DATA
then
	echo "Succeeded."
else
   echo "Failed."
fi


my_log "MySQL : fill webacula logbook"
cd ${BASEDIR}
sh ./MySql/20_webacula_fill_logbook
sh ./MySql/25_webacula_fill_jobdesc
sh ./MySql/30_webacula_fill_acl
sh ./MySql/40_bacula_fill_log


echo -e "\nPREPARE all done OK.\n"
