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
	logId		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	logDateCreate	DATETIME NOT NULL,
	logDateLast	DATETIME,
	logTxt		TEXT NOT NULL,
	logTypeId	INTEGER UNSIGNED NOT NULL,
	logIsDel	INTEGER,

	PRIMARY KEY(logId),
	INDEX (logDateCreate)
) ENGINE=MyISAM;

CREATE INDEX wbidx1 ON webacula_logbook(logDateCreate);
CREATE FULLTEXT INDEX idxTxt ON webacula_logbook(logTxt);


DROP TABLE IF EXISTS webacula_logtype;
CREATE TABLE webacula_logtype (
	typeId	INTEGER UNSIGNED NOT NULL,
	typeDesc TINYBLOB NOT NULL,

	PRIMARY KEY(typeId)
);

INSERT INTO webacula_logtype (typeId,typeDesc) VALUES
	(10, 'Info'),
	(20, 'OK'),
	(30, 'Warning'),
	(255, 'Error')
;


/* Job descriptions */
CREATE TABLE IF NOT EXISTS webacula_jobdesc (
    desc_id  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id),
    INDEX (name_job)
);


DROP TABLE IF EXISTS webacula_version;
CREATE TABLE webacula_version (
   versionId INTEGER UNSIGNED NOT NULL
);

INSERT INTO webacula_version (versionId) VALUES (5);


END-OF-DATA
then
   echo "Creation of webacula MySQL tables succeeded."
else
   echo "Creation of webacula MySQL tables failed."
fi
exit 0
