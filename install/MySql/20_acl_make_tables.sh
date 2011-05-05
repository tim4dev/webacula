#!/bin/bash
#
# Script to create Webacula ACLs tables in Bacula database
#

.   ../db.conf


if [ -n "$db_pwd" ]
then
    pwd="-p$db_pwd"
else
    pwd=""
fi


if mysql $* -u $db_user $pwd  $db_name -f <<END-OF-DATA

CREATE TABLE IF NOT EXISTS webacula_users (
    id       integer not null auto_increment,
    login    varchar(50) UNIQUE not null,
    pwd      varchar(50) not null,
    name     varchar(150),
    email    varchar(50),
    create_login DATETIME NOT NULL,
    last_login DATETIME,
    last_ip  varchar(15),
    active   integer,
    role_id  integer NOT NULL,
    PRIMARY KEY (id),
    INDEX (login)
);


CREATE TABLE IF NOT EXISTS webacula_roles (
    id      integer not null auto_increment,
    order_role  integer not null DEFAULT 1,
    name    varchar(50) UNIQUE not null,
    description TEXT,
    inherit_id  integer,
    primary key (id)
);    


CREATE TABLE IF NOT EXISTS webacula_resources (
    id       integer not null auto_increment,
    dt_id    integer,
    role_id  integer,
    primary key (id)
);


CREATE TABLE IF NOT EXISTS webacula_dt_resources (
    id      integer not null auto_increment,
    name    varchar(50) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
);


INSERT INTO webacula_roles (id, name, description) VALUES (1, 'root_role', 'Default built-in superuser role');
INSERT INTO webacula_users (id, login, pwd, name, active, create_login, role_id)
    VALUES (1000, 'root', MD5('$webacula_root_pwd'), 'root', 1, NOW(), 1);

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
    (110,2),
    (120,2),
    (130,2),
    (140,2),
    (150,2),
    (160,2);

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
    (110,'volume',    'Menu Volume'),
    (120,'wbjobdesc', 'Menu Job Descriptions'),
    (130,'wblogbook', 'Menu Logbook'),
    (140,'help',      'Menu Help'),
    (150,'feed',      'RSS feed'),
    (160,'chart',     'Timeline chart'),
    (500,'admin',     'Menu Administrator');



-- Bacula ACLs



CREATE TABLE IF NOT EXISTS webacula_client_acl (
    id        integer not null auto_increment,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name, role_id)
);



CREATE TABLE IF NOT EXISTS webacula_command_acl (
    id        integer not null auto_increment,
    dt_id    integer,
    role_id   integer,
    PRIMARY KEY (id),
    UNIQUE INDEX (dt_id, role_id)
);

CREATE TABLE IF NOT EXISTS webacula_dt_commands (
    id      integer not null auto_increment,
    name    varchar(127) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
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
    (300, 'setdebug',    'Sets debug level'),
    (310, 'setip',       'Sets new client address, if authorized'),
    (320, 'show',        'Show resource records'),
    (330, 'sqlquery',    'Use SQL to query catalog'),
    (340, 'time',        'Print current time'),
    (350, 'trace',       'Turn on/off trace to file'),
    (360, 'unmount',     'Unmount storage'),
    (370, 'umount',      'Umount - for old-time Unix guys, see unmount'),
    (380, 'update',      'Update volume, pool or stats'),
    (390, 'use',         'Use catalog xxx'),
    (400, 'var',         'Does variable expansion'),
    (410, 'version',     'Print Director version'),
    (420, 'wait',        'Wait until no jobs are running');



CREATE TABLE IF NOT EXISTS webacula_fileset_acl (
    id        integer not null auto_increment,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name, role_id)
);



CREATE TABLE IF NOT EXISTS webacula_job_acl (
    id        integer not null auto_increment,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name, role_id)
);



CREATE TABLE IF NOT EXISTS webacula_pool_acl (
    id        integer not null auto_increment,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name, role_id)
);



CREATE TABLE IF NOT EXISTS webacula_storage_acl (
    id        integer not null auto_increment,
    name      varchar(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name, role_id)
);



CREATE TABLE IF NOT EXISTS webacula_where_acl (
    id        integer not null auto_increment,
    name      TEXT NOT NULL,
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl),
    UNIQUE INDEX (name(256), role_id)
);



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
then
   echo "Creation of Webacula ACLs MySQL tables succeeded."
else
   echo "Creation of Webacula ACLs MySQL tables failed."
fi
exit 0
