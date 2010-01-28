#!/bin/sh
#
# Script to create webacula tables
#

db_name="webacula"

if mysql $* -f <<END-OF-DATA

USE webacula;

CREATE TABLE IF NOT EXISTS wbLogBook (
	logId		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	logDateCreate	DATETIME NOT NULL,
	logDateLast	DATETIME,
	logTxt		TEXT NOT NULL,
	logTypeId	INTEGER UNSIGNED NOT NULL,
	logIsDel	INTEGER,

	PRIMARY KEY(logId),
	INDEX (logDateCreate)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

CREATE INDEX wbidx1 ON wbLogBook(logDateCreate);
CREATE FULLTEXT INDEX idxTxt ON wbLogBook(logTxt);


CREATE TABLE IF NOT EXISTS wbLogType (
	typeId	INTEGER UNSIGNED NOT NULL,
	typeDesc TINYBLOB NOT NULL,

	PRIMARY KEY(typeId)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

INSERT INTO wbLogType (typeId,typeDesc) VALUES
	(10, 'Info'),
	(20, 'OK'),
	(30, 'Warning'),
	(255, 'Error')
;


/* Job descriptions */
CREATE TABLE IF NOT EXISTS wbJobDesc (
    desc_id  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id),
    INDEX (name_job)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;


CREATE TABLE IF NOT EXISTS wbVersion (
   versionId INTEGER UNSIGNED NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

INSERT INTO wbVersion (versionId) VALUES (3);


/* list of temporary tables */
DROP TABLE IF EXISTS wbTmpTable;
DROP TABLE IF EXISTS wbTmpTableList;
DROP TABLE IF EXISTS wbtmptablelist; 

CREATE TABLE wbtmptablelist (
        tmpId    INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        tmpName  CHAR(64) UNIQUE NOT NULL,              /* name temporary table */
        tmpJobIdHash CHAR(64) NOT NULL,
        tmpCreate   TIMESTAMP NOT NULL,
        tmpIsCloneOk INTEGER DEFAULT 0,					/* is clone bacula tables OK */
        PRIMARY KEY(tmpId)
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

END-OF-DATA
then
   echo "Creation of webacula MySQL tables succeeded."
else
   echo "Creation of webacula MySQL tables failed."
fi
exit 0

