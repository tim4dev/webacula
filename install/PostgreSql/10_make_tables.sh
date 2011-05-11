#!/bin/bash
#
# Script to create webacula tables
#

.   ../db.conf



psql -q -f - -d $db_name $* <<END-OF-DATA



SET client_min_messages=WARNING;

CREATE TABLE webacula_logbook (
   logId    SERIAL NOT NULL,
   logDateCreate  timestamp without time zone,
   logDateLast timestamp without time zone,
   logTxt      TEXT NOT NULL,
   logTypeId   INTEGER NOT NULL,
   logIsDel SMALLINT,
   PRIMARY KEY (logId)
);
CREATE INDEX webacula_idx1 ON webacula_logbook (logDateCreate);
CREATE INDEX webacula_logtxt_idx ON webacula_logbook USING gin(to_tsvector('english', logtxt));

CREATE TABLE webacula_logtype (
   typeId   SERIAL,
   typeDesc VARCHAR(256) NOT NULL,
   PRIMARY KEY(typeId)
);

INSERT INTO webacula_logtype (typeId,typeDesc) VALUES
   (10, 'Info'),
   (20, 'OK'),
   (30, 'Warning'),
   (255, 'Error')
;

-- Job descriptions
CREATE TABLE webacula_jobdesc (
    desc_id  SERIAL,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);
CREATE INDEX webacula_idx2 ON webacula_jobdesc (name_job);

CREATE TABLE webacula_version (
   versionId INTEGER NOT NULL
);
INSERT INTO webacula_version (versionId) VALUES (5);

-- Eliminate "Unique violation: duplicate key value violates unique constraint"
-- file:///usr/share/doc/postgresql-8.3.7/html/plpgsql-control-structures.html
-- see also file:///usr/share/doc/postgresql-8.3.7/html/functions-string.html

CREATE LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION webacula_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) RETURNS VOID AS
\$\$
BEGIN
   BEGIN
      EXECUTE 'INSERT INTO ' || quote_ident(vTbl) || ' (FileId, PathId, FilenameId, LStat, MD5, FileSize, isMarked)
      VALUES (' || vFileId || ', ' || vPathId || ', ' || vFilenameId || ', ' ||
      quote_literal(vLStat) || ', ' || quote_literal(vMD5) || ', ' || vFileSize || ', ' || visMarked || ');' ;
      RETURN;
   EXCEPTION WHEN unique_violation THEN
      -- do nothing
   END;
END;
\$\$
LANGUAGE 'plpgsql';


-- grants

GRANT all ON webacula_logbook TO ${db_user};
GRANT all ON webacula_logtype TO ${db_user};
GRANT all ON webacula_jobdesc TO ${db_user};
GRANT SELECT, REFERENCES ON webacula_version TO ${db_user};

GRANT SELECT, UPDATE ON webacula_logbook_logid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_logtype_typeid_seq TO ${db_user};
GRANT SELECT, UPDATE ON webacula_jobdesc_desc_id_seq TO ${db_user};

-- execute access
GRANT EXECUTE ON FUNCTION webacula_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) TO ${db_user};

END-OF-DATA

res=$?
if test $res = 0;
then
   echo "PostgreSql : create of Webacula tables succeeded."
else
   echo "PostgreSql : create of Webacula tables failed!"
fi