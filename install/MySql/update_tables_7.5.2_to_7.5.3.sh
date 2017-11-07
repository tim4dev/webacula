#!/bin/bash
#
# Script to create Webacula tables in Bacula database
#
db_name=${db_name:-bacula}


mysql $* -f <<END-OF-DATA
use ${db_name};

UPDATE webacula_version SET versionId = 16;



CREATE TABLE IF NOT EXISTS webacula_job_size (
	JobId       int(11) unsigned NOT NULL,
	JobSize     bigint NOT NULL DEFAULT 0,
	FileSize    bigint NOT NULL DEFAULT 0,
	Status      int(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (JobId)
);



-- Function base64_decode_lstat (Decode field LStat from table File)
DELIMITER ;;
CREATE FUNCTION base64_decode_lstat(vField INTEGER, vInput BLOB) RETURNS bigint(20)
   DETERMINISTIC
   SQL SECURITY INVOKER
BEGIN
   DECLARE first_char BINARY(1);
   DECLARE accum_value BIGINT UNSIGNED DEFAULT 0;
   SET vInput = SUBSTRING_INDEX(SUBSTRING_INDEX(vInput, ' ', vField), ' ', -1);
   WHILE LENGTH(vInput) > 0 DO
      SET first_char = SUBSTRING(vInput, 1, 1);
      SET vInput = SUBSTRING(vInput, 2);
     SET accum_value = (accum_value << 6) + INSTR('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',first_char)-1;
   END WHILE;
   RETURN accum_value;
END ;;
DELIMITER ;



-- Function human_size (Convert bytes in more human readable format)
DELIMITER ;;
CREATE FUNCTION human_size(vBytes FLOAT) RETURNS varchar(20) CHARSET utf8
BEGIN
DECLARE i INT DEFAULT 1;
LOOP
   IF vBytes < 1024 THEN
       RETURN lpad(concat(round(vBytes, 2), ' ', elt(i, 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB')),12,' ');
   END IF;
   SET vBytes = vBytes / 1024;
   SET i = i + 1;
END LOOP;

END
;;
DELIMITER ;

END-OF-DATA

if [ $? -eq 0 ]
then
   echo "MySQL: updating Webacula tables succeeded."
else
   echo "MySQL: updating Webacula tables failed!"
   exit 1
fi
exit 0
