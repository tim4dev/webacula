#!/bin/sh
#
# Copyright (C) 2017 Wanderlei Hüttel
# License: BSD 2-Clause; see file LICENSE-FOSS
#
# Shell Script to get the original size of jobs before compression
#
# It must be scheduled on crontab 


bconsole=`which bconsole`
JobId=$1
JobType=$2
JobStatus=$3

###########################################################################
# Check if JobStatus = OK and JobType = B
if [ "${JobStatus}" = "OK" ] && [ "${JobType}" = "Backup" ]; then
   command="$(cat <<EOF
sqlquery
INSERT INTO webacula_job_size (JobId) VALUES (${JobId});
UPDATE webacula_job_size SET JobSize=(SELECT SUM(JobBytes) FROM Job WHERE JobId=${JobId}), FileSize=COALESCE((SELECT SUM(base64_decode_lstat(8,LStat)) FROM File WHERE JobId=${JobId} and FileIndex >0),0), Status=1 WHERE JobId=${JobId};

   exit
EOF
)"
   echo "${command}" | ${bconsole} > /dev/null
   echo "The JobSize and FileSize of JobId ${JobId} were updated successfully!"

###########################################################################
else 
   command="$(cat <<EOF
sqlquery
INSERT INTO webacula_job_size (JobId, Status) VALUES (${JobId},1);

exit
EOF
)"
   echo "${command}" | ${bconsole} > /dev/null
   echo "The JobSize and FileSize of JobId ${JobId} were updated successfully!"
fi
exit 0
