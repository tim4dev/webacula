#!/bin/sh
#
# Copyright (C) 2017 Wanderlei Hüttel
# License: BSD 2-Clause; see file LICENSE-FOSS
#
# Shell Script to get the original size of jobs before compression
#
# Is necessary to run only once


bconsole=`which bconsole`

#############################################################
# Get all JobId's JobStatus=T and Type=B
command="$(cat <<EOF
sqlquery
INSERT INTO webacula_job_size (JobId) SELECT JobId FROM Job WHERE JobStatus='T' AND TYPE='B';
SELECT JobId FROM Job WHERE JobStatus='T' AND TYPE='B';

exit
EOF
)"

# Update table webacula_job_size with the real size of Jobs before compression.
Jobs=`echo "${command}" | ${bconsole} | grep "|" | grep -iv "JobId" | sed 's/[ |,]//g'`
for JobId in ${Jobs}; do 
   echo "Updating JobSize and FileSize of JobId ${JobId} ..."
   command="$(cat <<EOF
sqlquery
UPDATE webacula_job_size SET JobSize=(SELECT JobBytes FROM Job WHERE JobId=${JobId}), FileSize=COALESCE((SELECT SUM(base64_decode_lstat(8,LStat)) FROM File WHERE JobId=${JobId} and FileIndex > 0),0), Status=1 WHERE JobId=${JobId};

exit
EOF
)"
   echo "${command}" | ${bconsole} > /dev/null
done
exit 0
