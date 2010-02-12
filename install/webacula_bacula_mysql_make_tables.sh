#!/bin/sh
#
# Script to create Webacula tables in Bacula Catalog
#

db_name="bacula"

if mysql $* -f <<END-OF-DATA

USE bacula;

/* list of temporary tables */
CREATE TABLE webacula_tmptablelist (
    tmpId    INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    tmpName  CHAR(64) UNIQUE NOT NULL,      /* name temporary table */
    tmpJobIdHash CHAR(64) NOT NULL,
    tmpCreate   TIMESTAMP NOT NULL,
    tmpIsCloneOk INTEGER DEFAULT 0,         /* is clone bacula tables OK */
    PRIMARY KEY(tmpId)
)  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ENGINE=MyISAM;

END-OF-DATA
then
   echo "Creation of Webacula MySQL tables succeeded."
else
   echo "Creation of Webacula MySQL tables failed."
fi
exit 0

