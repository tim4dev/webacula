#!/bin/bash
#
# Script to create webacula tables
#

.   ../db.conf

if [ -n "$db_pwd" ]
then
    pwd="-p$db_pwd"
else
    pwd=""
fi


if mysql $* -u $db_user $pwd  $db_name -f <<END-OF-DATA


CREATE TABLE IF NOT EXISTS webacula_logbook (
    logId           int(10) unsigned NOT NULL AUTO_INCREMENT,
    logDateCreate   datetime NOT NULL,
    logDateLast     datetime DEFAULT NULL,
    logTxt          text NOT NULL,
    logTypeId       int(10) unsigned NOT NULL,
    logIsDel        int(11) DEFAULT NULL,
    PRIMARY KEY (logId),
    KEY idx_logDateCreate (logDateCreate),
    KEY idx_logTxt (logTxt(127))
);


DROP TABLE IF EXISTS webacula_logtype;
CREATE TABLE IF NOT EXISTS webacula_logtype (
    typeId          int(10) unsigned NOT NULL,
    typeDesc        tinyblob NOT NULL,
    PRIMARY KEY (typeId)
);


INSERT INTO webacula_logtype (typeId,typeDesc) VALUES
       (10, 'Info'),
       (20, 'OK'),
       (30, 'Warning'),
       (255, 'Error')
;


/* Job descriptions */
CREATE TABLE IF NOT EXISTS webacula_jobdesc (
    desc_id         int(10) unsigned NOT NULL AUTO_INCREMENT,
    name_job        char(64) NOT NULL,
    retention_period char(32) DEFAULT NULL,
    short_desc      varchar(128) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (desc_id),
    UNIQUE KEY name_job (name_job),
    KEY idx_short_desc (short_desc)
);


CREATE TABLE IF NOT EXISTS webacula_tmp_tablelist (
    tmpId           int(10) unsigned NOT NULL AUTO_INCREMENT,
    tmpName         char(64) NOT NULL,
    tmpJobIdHash    char(64) NOT NULL,
    tmpCreate       timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tmpIsCloneOk    int(11) DEFAULT '0',
    PRIMARY KEY (tmpId),
    UNIQUE KEY idx_tmpName (tmpName)
);


DROP TABLE IF EXISTS webacula_version;
CREATE TABLE IF NOT EXISTS webacula_version (
    versionId       int(10) unsigned NOT NULL
);


INSERT INTO webacula_version (versionId) VALUES (15);


END-OF-DATA
then
   echo "Creation of webacula MySQL tables succeeded."
else
   echo "Creation of webacula MySQL tables failed."
fi
exit 0
