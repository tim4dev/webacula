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


UPDATE webacula_version SET VersionId = 16;



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
   echo "PostgreSQL: updating Webacula tables succeeded."
else
   echo "PostgreSQL: updating Webacula tables failed!"
   exit 1
fi
exit 0
