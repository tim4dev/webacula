#!/bin/sh

db_name="/var/lib/sqlite/webacula.db"

if [ $# -eq 1 ]
then
   db_name="${1}"
fi

/usr/bin/sqlite3 ${db_name} <<END-OF-DATA

CREATE TABLE wbLogBook (
	logId		INTEGER,
	logDateCreate	DATETIME NOT NULL,
	logDateLast	DATETIME,
	logTxt		TEXT NOT NULL,
	logTypeId	INTEGER UNSIGNED NOT NULL,
	logIsDel	INTEGER,
	PRIMARY KEY(logId)
);

CREATE INDEX wbidx1 ON wbLogBook(logDateCreate);
CREATE INDEX idxTxt ON wbLogBook(logTxt);


CREATE TABLE wbLogType (
	typeId	INTEGER,
	typeDesc VARCHAR(255) NOT NULL,
	PRIMARY KEY(typeId)
);

INSERT INTO wbLogType (typeId,typeDesc) VALUES (10, 'Info');
INSERT INTO wbLogType (typeId,typeDesc) VALUES (20, 'OK');
INSERT INTO wbLogType (typeId,typeDesc) VALUES (30, 'Warning');
INSERT INTO wbLogType (typeId,typeDesc) VALUES (255, 'Error');

/* Job descriptions */
CREATE TABLE wbJobDesc (
    desc_id     INTEGER,
    name_job    CHAR(64) NOT NULL,
    retention_period CHAR(32),
    description      TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);


CREATE TABLE wbVersion (
   versionId INTEGER UNSIGNED NOT NULL
);

INSERT INTO wbVersion (versionId) VALUES (3);


/* list of temporary tables */
DROP TABLE IF EXISTS wbTmpTable;
DROP TABLE IF EXISTS wbTmpTableList;
DROP TABLE IF EXISTS wbtmptablelist; 

CREATE TABLE wbtmptablelist (
        tmpId    INTEGER,
    	  tmpName  CHAR(64) UNIQUE NOT NULL,              /* name temporary table */
        tmpJobIdHash CHAR(64) NOT NULL,
        tmpCreate   TIMESTAMP NOT NULL,
        tmpIsCloneOk INTEGER DEFAULT 0,					/* is clone bacula tables OK */
        PRIMARY KEY(tmpId)
);

END-OF-DATA

# access by apache
chgrp apache ${db_name}
chmod g+rw ${db_name}
exit 0
