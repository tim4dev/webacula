#!/bin/bash
#
# Script to create Webacula tables in Bacula database
#
db_user=${db_user:-bacula}
db_name=${db_name:-bacula}
pg_config=`which pg_config`
bindir=`pg_config --bindir`
PATH="$bindir:$PATH"



psql -q -f - -d ${db_name} $* <<END-OF-DATA

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
   (255, 'Error');



CREATE TABLE webacula_jobdesc (
    desc_id  SERIAL,
    name_job    CHAR(64) UNIQUE NOT NULL,
    retention_period CHAR(32),
    short_desc      VARCHAR(128) NOT NULL,
    description     TEXT NOT NULL,
    PRIMARY KEY(desc_id)
);
CREATE INDEX webacula_idx2 ON webacula_jobdesc (name_job);
CREATE INDEX webacula_idx3 ON webacula_jobdesc (short_desc);



CREATE TABLE webacula_tmp_tablelist (
    tmpId    SERIAL NOT NULL,
    tmpName  CHAR(64) UNIQUE NOT NULL,
    tmpJobIdHash CHAR(64) NOT NULL,
    tmpCreate   timestamp without time zone NOT NULL,
    tmpIsCloneOk SMALLINT DEFAULT 0,
    PRIMARY KEY(tmpId)
);



CREATE TABLE webacula_version (
   versionId INTEGER NOT NULL
);
INSERT INTO webacula_version (versionId) VALUES (16);



CREATE TABLE webacula_job_size (
    JobId      INT NOT NULL,
    JobSize    BIGINT NOT NULL DEFAULT 0,
    FileSize   BIGINT NOT NULL DEFAULT 0,
    Status     INT NOT NULL DEFAULT 0,
    PRIMARY KEY(JobId)
);



-- If PostgreSQL >= 9
CREATE OR REPLACE LANGUAGE 'plpgsql';

-- Else PostgreSQL < 9
-- CREATE 'plpgsql';



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


-- Function ELT (Returns the string at the index number specified in the list of arguments)
CREATE OR REPLACE FUNCTION elt(pos int4, str VARIADIC text[]) RETURNS text AS
\$\$
  SELECT str[pos];
\$\$
LANGUAGE sql;



-- Function base64_decode_lstat (Decode field LStat from table File)
CREATE OR REPLACE FUNCTION base64_decode_lstat(pos int4, str varchar) RETURNS int8 IMMUTABLE STRICT AS
\$\$
DECLARE
   val int8;
   len int8;
   b64 varchar(64);
   size varchar(64);
   i int;
BEGIN
   size := split_part(str, ' ', pos);
   b64 := 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
   val := 0;
   len=length(size);
   FOR i IN 1..len LOOP
      val := val + (strpos(b64, substr(size, i, 1))-1) * (64^(len-i));
   END LOOP;
   RETURN val;
END;
\$\$
language 'plpgsql';



-- Function human_size (Convert bytes in more human readable format)
CREATE OR REPLACE FUNCTION human_size(bytes numeric) RETURNS varchar AS
\$\$
DECLARE n int;
BEGIN
   n := 1;
   LOOP
    IF bytes < 1024 THEN
        RETURN lpad(concat(round(bytes, 2), ' ', elt(n, 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB')),12,' ');
    END IF;
    bytes := bytes / 1024;
    n := n + 1;
  END LOOP;
END;
\$\$
LANGUAGE 'plpgsql';

END-OF-DATA



if [ $? -eq 0 ]
then
   echo "PostgreSQL: creation of Webacula tables succeeded."
else
   echo "PostgreSQL: creation of Webacula tables failed!"
   exit 1
fi
exit 0
