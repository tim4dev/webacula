#!/bin/sh
#
# Script to create Webacula ACLs tables in Bacula database
#

.   ../db.conf



if [ $# -eq 1 ]
then
   db_name_sqlite="${1}"
fi

sqlite3 $db_name_sqlite <<END-OF-DATA


CREATE TABLE webacula_users (
    id       integer not null,
    login    varchar(50) UNIQUE not null,
    pwd      varchar(50) not null,
    name     varchar(150),
    email    varchar(50),
    create_login DATETIME NOT NULL,
    last_login DATETIME,
    last_ip  varchar(15),
    active   integer,
    role_id  integer NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX webacula_users_idx1 ON webacula_users(login);


CREATE TABLE webacula_roles (
    id      integer not null,
    order_role  integer not null DEFAULT 1,
    name    varchar(50) UNIQUE not null,
    description TEXT,
    inherit_id  integer,
    primary key (id)
);    


CREATE TABLE webacula_resources (
    id       integer not null,
    dt_id    integer,
    role_id  integer,
    primary key (id)
);


CREATE TABLE webacula_dt_resources (
    id      integer not null,
    name    varchar(50) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
);


INSERT INTO webacula_roles (id, name, description) VALUES (1, 'root_role', 'Default built-in superuser role');
INSERT INTO webacula_users (id, login, pwd, name, active, create_login, role_id)
    VALUES (1000, 'root', '$webacula_root_pwd', 'root', 1, datetime('now'), 1);

INSERT INTO webacula_roles (id, name, description) VALUES (2, 'operator_role', 'Typical built-in role for backup operator');

INSERT INTO webacula_resources (dt_id, role_id) VALUES (10,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (20,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (30,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (40,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (50,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (60,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (70,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (80,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (90,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (100,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (110,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (120,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (130,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (140,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (150,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (160,2);

-- Controller names only
INSERT INTO webacula_dt_resources (id, name, description) VALUES (10, 'index',     'Home page');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (20, 'bconsole',  'Webacula bconsole');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (30, 'client',    'Menu Client');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (40, 'director',  'Menu Director');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (50, 'file',      'List Files for JobId');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (60, 'job',       'Menu Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (70, 'log',       'View console log for Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (80, 'pool',      'Menu Pool');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (90, 'restorejob','Menu Restore Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (100,'storage',   'Menu Storage');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (110,'volume',    'Menu Volume');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (120,'wbjobdesc', 'Menu Job Descriptions');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (130,'wblogbook', 'Menu Logbook');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (140,'help',      'Menu Help');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (150,'feed',      'RSS feed');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (160,'chart',     'Timeline chart');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (500,'admin',     'Menu Administrator');



-- Bacula ACLs



CREATE TABLE webacula_client_acl (
    id        integer not null,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_client_acl_idx1 ON webacula_client_acl(name, role_id);
CREATE        INDEX webacula_client_acl_idx2 ON webacula_client_acl(id, order_acl);



CREATE TABLE webacula_command_acl (
    id        integer not null,
    dt_id    integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_command_acl_idx1 ON webacula_command_acl(dt_id, role_id);



CREATE TABLE webacula_dt_commands (
    id      integer not null,
    name    varchar(127) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
);

-- see src/dird/ua_cmds.c
INSERT INTO webacula_dt_commands (id, name, description) VALUES (1,   '*all*',       'All commands');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (10,  'add',         'Add media to a pool');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (20,  'autodisplay', 'Autodisplay console messages');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (30,  'automount',   'Automount after label');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (40,  'cancel',      'Cancel a job');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (50,  'create',      'Create DB Pool from resource');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (60,  'delete',      'Delete volume, pool or job');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (70,  'disable',     'Disable a job');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (80,  'enable',      'Enable a job');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (90,  'estimate',    'Performs FileSet estimate, listing gives full listing');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (100, 'exit',        'Terminate Bconsole session');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (110, 'gui',         'Non-interactive gui mode');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (120, 'help',        'Print help on specific command');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (130, 'label',       'Label a tape');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (140, 'list',        'List objects from catalog');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (150, 'llist',       'Full or long list like list command');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (160, 'messages',    'Display pending messages');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (170, 'memory',      'Print current memory usage');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (180, 'mount',       'Mount storage');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (190, 'prune',       'Prune expired records from catalog');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (200, 'purge',       'Purge records from catalog');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (210, 'python',      'Python control commands');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (220, 'quit',        'Terminate Bconsole session');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (230, 'query',       'Query catalog');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (240, 'restore',     'Restore files');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (250, 'relabel',     'Relabel a tape');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (260, 'release',     'Release storage');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (270, 'reload',      'Reload conf file');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (280, 'run',         'Run a job');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (290, 'status',      'Report status');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (300, 'setdebug',    'Sets debug level');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (310, 'setip',       'Sets new client address, if authorized');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (320, 'show',        'Show resource records');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (330, 'sqlquery',    'Use SQL to query catalog');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (340, 'time',        'Print current time');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (350, 'trace',       'Turn on/off trace to file');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (360, 'unmount',     'Unmount storage');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (370, 'umount',      'Umount - for old-time Unix guys, see unmount');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (380, 'update',      'Update volume, pool or stats');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (390, 'use',         'Use catalog xxx');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (400, 'var',         'Does variable expansion');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (410, 'version',     'Print Director version');
INSERT INTO webacula_dt_commands (id, name, description) VALUES (420, 'wait',        'Wait until no jobs are running');



CREATE TABLE webacula_fileset_acl (
    id        integer not null,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_fileset_acl_idx1 ON webacula_fileset_acl(name, role_id);
CREATE        INDEX webacula_fileset_acl_idx2 ON webacula_fileset_acl(id, order_acl);



CREATE TABLE webacula_job_acl (
    id        integer not null,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_job_acl_idx1 ON webacula_job_acl(name, role_id);
CREATE        INDEX webacula_job_acl_idx2 ON webacula_job_acl(id, order_acl);



CREATE TABLE webacula_pool_acl (
    id        integer not null,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_pool_acl_idx1 ON webacula_pool_acl(name, role_id);
CREATE        INDEX webacula_pool_acl_idx2 ON webacula_pool_acl(id, order_acl);



CREATE TABLE webacula_storage_acl (
    id        integer not null,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_storage_acl_idx1 ON webacula_storage_acl(name, role_id);
CREATE        INDEX webacula_storage_acl_idx2 ON webacula_storage_acl(id, order_acl);



CREATE TABLE webacula_where_acl (
    id        integer not null,
    name      TEXT NOT NULL,
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id)
);
CREATE UNIQUE INDEX webacula_where_acl_idx1 ON webacula_where_acl(name, role_id);
CREATE        INDEX webacula_where_acl_idx2 ON webacula_where_acl(id, order_acl);



-- 'root_role' Bacula ACLs
INSERT INTO webacula_storage_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 1); 
INSERT INTO webacula_pool_acl    (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_client_acl  (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_fileset_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_where_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_command_acl (dt_id,role_id) VALUES (1, 1);
INSERT INTO webacula_job_acl     (name, order_acl, role_id)  VALUES ('*all*', 1, 1);

-- 'operator_role' Bacula ACLs
INSERT INTO webacula_storage_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_pool_acl    (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_client_acl  (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_fileset_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_where_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 2);
INSERT INTO webacula_command_acl (dt_id,role_id) VALUES (1, 2);
INSERT INTO webacula_job_acl     (name, order_acl, role_id)  VALUES ('*all*', 1, 2);


-- PHP session storage
CREATE TABLE webacula_php_session (
    id       char(32),
    modified integer,
    lifetime integer,
    data_session TEXT,
    login    varchar(50),
    PRIMARY KEY (id)
);


END-OF-DATA

# access by apache
chgrp apache ${db_name_sqlite}
chmod g+rw ${db_name_sqlite}

echo "Sqlite : Webacula ACLs created"

exit 0
