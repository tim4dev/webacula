-- $Id: webacula_postgresql_make_tables.sql 405 2009-08-17 19:51:14Z tim4dev $

CREATE TABLE wbLogBook (
   logId    SERIAL NOT NULL,
   logDateCreate  timestamp without time zone,
   logDateLast timestamp without time zone,
   logTxt      TEXT NOT NULL,
   logTypeId   INTEGER NOT NULL,
   logIsDel SMALLINT,
   PRIMARY KEY (logId)
);
CREATE INDEX wbidx1 ON wbLogBook (logDateCreate);
CREATE INDEX logtxt_idx ON wblogbook USING gin(to_tsvector('english', logtxt));

CREATE TABLE wbLogType (
   typeId   SERIAL,
   typeDesc VARCHAR(256) NOT NULL,
   PRIMARY KEY(typeId)
);

INSERT INTO wbLogType (typeId,typeDesc) VALUES
   (10, 'Info'),
   (20, 'OK'),
   (30, 'Warning'),
   (255, 'Error')
;

CREATE TABLE  wbVersion (
   versionId INTEGER NOT NULL
);
INSERT INTO wbVersion (versionId) VALUES (3);

-- list of temporary tables
DROP TABLE IF EXISTS wbTmpTable;
DROP TABLE IF EXISTS wbTmpTableList;
DROP TABLE IF EXISTS wbtmptablelist;

CREATE TABLE wbtmptablelist (
        tmpId    SERIAL NOT NULL,
        tmpName  CHAR(64) UNIQUE NOT NULL,
        tmpJobIdHash CHAR(64) NOT NULL,
        tmpCreate   timestamp without time zone NOT NULL,
        tmpIsCloneOk SMALLINT DEFAULT 0,
        PRIMARY KEY(tmpId)
);




-- Eliminate "Unique violation: duplicate key value violates unique constraint"
-- file:///usr/share/doc/postgresql-8.3.7/html/plpgsql-control-structures.html
-- see also file:///usr/share/doc/postgresql-8.3.7/html/functions-string.html

CREATE LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION my_clone_file(vTbl TEXT, vFileId INT, vPathId INT, vFilenameId INT, vLStat TEXT, vMD5 TEXT, visMarked INT, vFileSize INT) RETURNS VOID AS
$$
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
$$
LANGUAGE 'plpgsql';


CREATE OR REPLACE FUNCTION my_clone_filename(vTbl TEXT, vFilenameId INT, vName TEXT) RETURNS VOID AS
$$
BEGIN
   BEGIN
      EXECUTE 'INSERT INTO '|| quote_ident(vTbl) || ' (FilenameId, Name) 
      VALUES (' || vFilenameId || ', ' || quote_literal(vName) || ');' ;
      RETURN;
   EXCEPTION WHEN unique_violation THEN
      -- do nothing
   END;
END;
$$
LANGUAGE 'plpgsql';


CREATE OR REPLACE FUNCTION my_clone_path(vTbl TEXT, vPathId INT, vPath TEXT) RETURNS VOID AS
$$
BEGIN
   BEGIN
      EXECUTE 'INSERT INTO '|| quote_ident(vTbl) || ' (PathId, Path) VALUES (' || 
      vPathId || ', ' || quote_literal(vPath) || ');' ;
      RETURN;
   EXCEPTION WHEN unique_violation THEN
      -- do nothing
   END;
END;
$$
LANGUAGE 'plpgsql';

