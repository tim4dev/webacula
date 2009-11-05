#!/bin/sh
#
# Script to update webacula tables from v.3.x to 3.4
#

db_name="/var/lib/sqlite/webacula.db"

if [ $# -eq 1 ]
then
   db_name="${1}"
fi

/usr/bin/sqlite3 ${db_name} <<END-OF-DATA

/* Job descriptions */
CREATE TABLE wbJobDesc (
    desc_id     INTEGER,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description      TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);
CREATE INDEX wbidx2 ON wbJobDesc(name_job);

END-OF-DATA

# access by apache
chgrp apache ${db_name}
chmod g+rw ${db_name}
exit 0
