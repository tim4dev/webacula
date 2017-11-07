#!/bin/bash
#
# Script to create Webacula tables in Bacula database
#
db_name=${db_name:-bacula}


mysql $* -f <<END-OF-DATA
use ${db_name};

CREATE TABLE IF NOT EXISTS webacula_logbook (
    logId           int(10) unsigned NOT NULL AUTO_INCREMENT,
    logDateCreate   datetime NOT NULL,
    logDateLast     datetime DEFAULT NULL,
    logTxt          text NOT NULL,
    logTypeId       int(10) unsigned NOT NULL,
    logIsDel        int(11) DEFAULT NULL,
    PRIMARY KEY (logId),
    KEY idx_logDateCreate (logDateCreate),
    KEY idx_logTxt (logTxt(127))
);



DROP TABLE IF EXISTS webacula_logtype;
CREATE TABLE IF NOT EXISTS webacula_logtype (
    typeId          int(10) unsigned NOT NULL,
    typeDesc        tinyblob NOT NULL,
    PRIMARY KEY (typeId)
);



INSERT INTO webacula_logtype (typeId,typeDesc) VALUES
       (10, 'Info'),
       (20, 'OK'),
       (30, 'Warning'),
       (255, 'Error')
;



CREATE TABLE IF NOT EXISTS webacula_jobdesc (
    desc_id         int(10) unsigned NOT NULL AUTO_INCREMENT,
    name_job        char(64) NOT NULL,
    retention_period char(32) DEFAULT NULL,
    short_desc      varchar(128) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (desc_id),
    UNIQUE KEY name_job (name_job),
    KEY idx_short_desc (short_desc)
);



CREATE TABLE IF NOT EXISTS webacula_tmp_tablelist (
    tmpId           int(10) unsigned NOT NULL AUTO_INCREMENT,
    tmpName         char(64) NOT NULL,
    tmpJobIdHash    char(64) NOT NULL,
    tmpCreate       timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tmpIsCloneOk    int(11) DEFAULT '0',
    PRIMARY KEY (tmpId),
    UNIQUE KEY idx_tmpName (tmpName)
);



DROP TABLE IF EXISTS webacula_version;
CREATE TABLE IF NOT EXISTS webacula_version (
    versionId       int(10) unsigned NOT NULL
);



INSERT INTO webacula_version (versionId) VALUES (16);



CREATE TABLE webacula_job_size (
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
   echo "MySQL: creation of Webacula tables succeeded."
else
   echo "MySQL: creation of Webacula tables failed!"
   exit 1
fi
exit 0
