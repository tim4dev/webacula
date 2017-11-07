#!/bin/bash
#
# Script to create Webacula ACLs tables in Bacula database
#
db_name=${db_name:-bacula}
# The default password is 'bacula'
# Do not modify the string below
wb_pwd='$P$BWvNstbpxxsvvnFkE90C6OfZxFS61P1'

mysql $* -f <<END-OF-DATA
use ${db_name};

CREATE TABLE IF NOT EXISTS webacula_users (
    id              int(11) NOT NULL AUTO_INCREMENT,
    login           varchar(50) NOT NULL,
    pwd             varchar(256) NOT NULL,
    name            varchar(150) DEFAULT NULL,
    email           varchar(50) DEFAULT NULL,
    create_login    datetime NOT NULL,
    last_login      datetime DEFAULT NULL,
    last_ip         varchar(40) DEFAULT NULL,
    active          int(11) DEFAULT NULL,
    role_id         int(11) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_login (login)
);



CREATE TABLE IF NOT EXISTS webacula_roles (
    id              int(11) NOT NULL AUTO_INCREMENT,
    order_role      int(11) NOT NULL DEFAULT '1',
    name            varchar(50) NOT NULL,
    description     text,
    inherit_id      int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name)
);



CREATE TABLE IF NOT EXISTS webacula_resources (
    id              int(11) NOT NULL AUTO_INCREMENT,
    dt_id           int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id)
);



CREATE TABLE IF NOT EXISTS webacula_dt_resources (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(50) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name)
);



INSERT INTO webacula_roles (id, name, description) VALUES (1, 'root_role', 'Default built-in superuser role');
INSERT INTO webacula_users (id, login, pwd, name, active, create_login, role_id) VALUES (1000, 'root', '$wb_pwd', 'root', 1, NOW(), 1);
INSERT INTO webacula_roles (id, name, description) VALUES (2, 'operator_role', 'Typical built-in role for backup operator');
INSERT INTO webacula_resources (dt_id, role_id) VALUES
    (10,2),
    (20,2),
    (30,2),
    (40,2),
    (50,2),
    (60,2),
    (70,2),
    (80,2),
    (90,2),
    (100,2),
    (105,2),
    (110,2),
    (120,2),
    (130,2),
    (140,2),
    (150,2),
    (160,2),
    (500,2);



-- Controller names only
INSERT INTO webacula_dt_resources (id, name, description) VALUES
    (10, 'index',     'Home page'),
    (20, 'bconsole',  'Webacula bconsole'),
    (30, 'client',    'Menu Client'),
    (40, 'director',  'Menu Director'),
    (50, 'file',      'List Files for JobId'),
    (60, 'job',       'Menu Job'),
    (70, 'log',       'View console log for Job'),
    (80, 'pool',      'Menu Pool'),
    (90, 'restorejob','Menu Restore Job'),
    (100,'storage',   'Menu Storage'),
    (105,'schedule',  'Menu Schedule'),
    (110,'volume',    'Menu Volume'),
    (120,'wbjobdesc', 'Menu Job Descriptions'),
    (130,'wblogbook', 'Menu Logbook'),
    (140,'help',      'Menu Help'),
    (150,'feed',      'RSS feed'),
    (160,'chart',     'Timeline chart'),
    (500,'admin',     'Menu Administrator');



-- Bacula ACLs
CREATE TABLE IF NOT EXISTS webacula_client_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) DEFAULT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name,role_id),
    KEY idx_id (id,order_acl)
);



CREATE TABLE IF NOT EXISTS webacula_command_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    dt_id           int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_dt_id (dt_id,role_id)
);



CREATE TABLE IF NOT EXISTS webacula_dt_commands (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) NOT NULL,
    description     text NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name)
);



-- see src/dird/ua_cmds.c
INSERT INTO webacula_dt_commands (id, name, description) VALUES
    (1,   '*all*',       'All commands'),
    (10,  'add',         'Add media to a pool'),
    (20,  'autodisplay', 'Autodisplay console messages'),
    (30,  'automount',   'Automount after label'),
    (40,  'cancel',      'Cancel a job'),
    (50,  'create',      'Create DB Pool from resource'),
    (60,  'delete',      'Delete volume, pool or job'),
    (70,  'disable',     'Disable a job'),
    (80,  'enable',      'Enable a job'),
    (90,  'estimate',    'Performs FileSet estimate, listing gives full listing'),
    (100, 'exit',        'Terminate Bconsole session'),
    (110, 'gui',         'Non-interactive gui mode'),
    (120, 'help',        'Print help on specific command'),
    (130, 'label',       'Label a tape'),
    (140, 'list',        'List objects from catalog'),
    (150, 'llist',       'Full or long list like list command'),
    (160, 'messages',    'Display pending messages'),
    (170, 'memory',      'Print current memory usage'),
    (180, 'mount',       'Mount storage'),
    (190, 'prune',       'Prune expired records from catalog'),
    (200, 'purge',       'Purge records from catalog'),
    (210, 'python',      'Python control commands'),
    (220, 'quit',        'Terminate Bconsole session'),
    (230, 'query',       'Query catalog'),
    (240, 'restore',     'Restore files'),
    (250, 'relabel',     'Relabel a tape'),
    (260, 'release',     'Release storage'),
    (270, 'reload',      'Reload conf file'),
    (280, 'run',         'Run a job'),
    (290, 'status',      'Report status'),
    (295, 'stop',        'Stop a job'),
    (300, 'setdebug',    'Sets debug level'),
    (305, 'setbandwidth','Sets bandwidth'),
    (310, 'setip',       'Sets new client address, if authorized'),
    (320, 'show',        'Show resource records'),
    (330, 'sqlquery',    'Use SQL to query catalog'),
    (340, 'time',        'Print current time'),
    (350, 'trace',       'Turn on/off trace to file'),
    (355, 'truncate',    'Truncate one or more Volumes'),
    (360, 'unmount',     'Unmount storage'),
    (370, 'umount',      'Umount - for old-time Unix guys, see unmount'),
    (380, 'update',      'Update volume, pool or stats'),
    (390, 'use',         'Use catalog xxx'),
    (400, 'var',         'Does variable expansion'),
    (410, 'version',     'Print Director version'),
    (420, 'wait',        'Wait until no jobs are running');



CREATE TABLE IF NOT EXISTS webacula_fileset_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) DEFAULT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name,role_id),
    KEY idx_id (id,order_acl)
);



CREATE TABLE IF NOT EXISTS webacula_job_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) DEFAULT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name,role_id),
    KEY idx_id (id,order_acl)
);



CREATE TABLE IF NOT EXISTS webacula_pool_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) DEFAULT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name,role_id),
    KEY idx_id (id,order_acl)
);



CREATE TABLE IF NOT EXISTS webacula_storage_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            varchar(127) DEFAULT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name,role_id),
    KEY idx_id (id,order_acl)
);



CREATE TABLE IF NOT EXISTS webacula_where_acl (
    id              int(11) NOT NULL AUTO_INCREMENT,
    name            text NOT NULL,
    order_acl       int(11) DEFAULT NULL,
    role_id         int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_name (name(127),role_id),
    KEY idx_id (id,order_acl)
);



-- 'root_role' Bacula ACLs
INSERT INTO webacula_storage_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_pool_acl      (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_client_acl    (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_fileset_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_where_acl     (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_command_acl   (dt_id,role_id) VALUES (1, 1);
INSERT INTO webacula_job_acl       (name, order_acl, role_id)  VALUES ('*all*', 1, 1);



-- 'operator_role' Bacula ACLs
INSERT INTO webacula_storage_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_pool_acl      (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_client_acl    (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_fileset_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_where_acl     (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_command_acl   (dt_id,role_id) VALUES (1, 2);
INSERT INTO webacula_job_acl       (name, order_acl, role_id)  VALUES ('*all*', 1, 2);



-- PHP session storage
CREATE TABLE IF NOT EXISTS webacula_php_session (
    id              char(64) NOT NULL DEFAULT '',
    modified        int(11) DEFAULT NULL,
    lifetime        int(11) DEFAULT NULL,
    data_session    text,
    login           varchar(50) DEFAULT NULL,
    PRIMARY KEY (id)
);

END-OF-DATA

if [ $? -eq 0 ]
then
   echo "MySQL: creation of Webacula ACL tables succeeded."
else
   echo "MySQL: creation of Webacula ACL tables failed!"
   exit 1
fi
exit 0
