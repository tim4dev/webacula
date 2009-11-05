#!/bin/sh
#
# Script to update webacula tables from v.3.x to 3.4
#

bindir="/usr/bin"
db_name="webacula"

if $bindir/mysql $* -f <<END-OF-DATA

USE webacula;

/* Job descriptions */
CREATE TABLE IF NOT EXISTS wbJobDesc (
    desc_id  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id),
    INDEX (name_job)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

END-OF-DATA
then
   echo "Update webacula MySQL tables succeeded."
else
   echo "Update webacula MySQL tables failed."
fi
exit 0

