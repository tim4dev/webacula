#!/bin/sh

.   ../db.conf


if [ $# -eq 1 ]
then
   db_name_sqlite="${1}"
fi

sqlite3 $db_name_sqlite <<END-OF-DATA

CREATE TABLE webacula_logbook (
	logId		INTEGER,
	logDateCreate	DATETIME NOT NULL,
	logDateLast	DATETIME,
	logTxt		TEXT NOT NULL,
	logTypeId	INTEGER UNSIGNED NOT NULL,
	logIsDel	INTEGER,
	PRIMARY KEY(logId)
);
CREATE INDEX webacula_idx1 ON webacula_logbook(logDateCreate);
CREATE INDEX webacula_idxTxt ON webacula_logbook(logTxt);


CREATE TABLE webacula_logtype (
	typeId	INTEGER,
	typeDesc VARCHAR(255) NOT NULL,
	PRIMARY KEY(typeId)
);

INSERT INTO webacula_logtype (typeId,typeDesc) VALUES (10, 'Info');
INSERT INTO webacula_logtype (typeId,typeDesc) VALUES (20, 'OK');
INSERT INTO webacula_logtype (typeId,typeDesc) VALUES (30, 'Warning');
INSERT INTO webacula_logtype (typeId,typeDesc) VALUES (255, 'Error');

/* Job descriptions */
CREATE TABLE webacula_jobdesc (
    desc_id     INTEGER,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description      TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);
CREATE INDEX webacula_idx2 ON webacula_jobdesc(name_job);


CREATE TABLE webacula_version (
   versionId INTEGER UNSIGNED NOT NULL
);

INSERT INTO webacula_version (versionId) VALUES (5);


END-OF-DATA

# access by apache
chgrp apache ${db_name_sqlite}
chmod g+rw ${db_name_sqlite}

echo "Sqlite : Webacula Logbook created"

exit 0
