#!/bin/sh
#
# Script to create webacula tables
#

if mysql $* -f <<END-OF-DATA

USE bacula;

CREATE TABLE IF NOT EXISTS webacula_logbook (
	logId		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	logDateCreate	DATETIME NOT NULL,
	logDateLast	DATETIME,
	logTxt		TEXT NOT NULL,
	logTypeId	INTEGER UNSIGNED NOT NULL,
	logIsDel	INTEGER,

	PRIMARY KEY(logId),
	INDEX (logDateCreate)
);

CREATE INDEX wbidx1 ON webacula_logbook(logDateCreate);
CREATE FULLTEXT INDEX idxTxt ON webacula_logbook(logTxt);


CREATE TABLE IF NOT EXISTS webacula_logtype (
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


CREATE TABLE IF NOT EXISTS webacula_version (
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
