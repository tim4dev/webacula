#!/bin/sh
#
# Script to create Webacula ACLs tables in Bacula database
#

db_name="bacula"

if mysql $* -f <<END-OF-DATA

USE bacula;


CREATE TABLE IF NOT EXISTS webacula_users (
    id       integer not null auto_increment,
    login    char(50) UNIQUE not null,
    pwd      char(50) not null,
    name     char(150),
    email    char(50),
    create_login DATETIME NOT NULL,
    last_login DATETIME,
    last_ip  char(15),
    active   integer,
    role_id  integer,
    PRIMARY KEY (id),
    INDEX (login)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS webacula_roles (
    id      integer not null auto_increment,
    order_role  integer not null DEFAULT 1,
    name    char(50) UNIQUE not null,
    description TEXT,
    inherit_id  integer,
    primary key (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;    


CREATE TABLE IF NOT EXISTS webacula_resources (
    id       integer not null auto_increment,
    dt_id    integer,
    role_id  integer,
    primary key (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS webacula_dt_resources (
    id      integer not null auto_increment,
    name    char(50) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO webacula_roles (id, name, description) VALUES (1, 'root_role', 'Default built-in superuser role');
INSERT INTO webacula_users (id, login, pwd, role_id) VALUES (1000, 'root', PASSWORD('1'), 1);

INSERT INTO webacula_roles (id, name, description) VALUES (2, 'operator_role', 'Typcal role for backup operator');
INSERT INTO webacula_resources (dt_id, role_id) VALUES (1,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (3,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (4,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (5,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (6,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (7,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (8,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (9,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (10,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (11,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (12,2);
INSERT INTO webacula_resources (dt_id, role_id) VALUES (13,2);

INSERT INTO webacula_dt_resources (id, name, description) VALUES (1, 'index',     'Home page');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (2, 'bconsole',  'Webacula bconsole');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (3, 'client',    'Menu Client');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (4, 'director',  'Menu Director');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (5, 'file',      'List Files for JobId');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (6, 'job',       'Menu Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (7, 'log',       'View console log for Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (8, 'pool',      'Menu Pool');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (9, 'restorejob','Menu Restore Job');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (10,'storage',   'Menu Storage');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (11,'volume',    'Menu Volume');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (12,'wbjobdesc', 'Menu Job Descriptions');
INSERT INTO webacula_dt_resources (id, name, description) VALUES (13,'wblogbook', 'Menu Logbook');



-- Bacula ACLs



CREATE TABLE IF NOT EXISTS webacula_storage_acl (
    id        integer not null auto_increment,
    name      char(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



CREATE TABLE IF NOT EXISTS webacula_pool_acl (
    id        integer not null auto_increment,
    name      char(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



CREATE TABLE IF NOT EXISTS webacula_command_acl (
    id        integer not null auto_increment,
    dt_id    integer,
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS webacula_dt_commands (
    id      integer not null auto_increment,
    name    char(127) UNIQUE not null,
    description TEXT NOT NULL,
    primary key (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- see src/dird/ua_cmds.c
INSERT INTO webacula_dt_commands (id, name, description) VALUES (1,   "*all*",       "All command");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (10,  "add",         "Add media to a pool");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (20,  "autodisplay", "Autodisplay console messages");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (30,  "automount",   "Automount after label");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (40,  "cancel",      "Cancel a job");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (50,  "create",      "Create DB Pool from resource");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (60,  "delete",      "Delete volume, pool or job");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (70,  "disable",     "Disable a job");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (80,  "enable",      "Enable a job");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (90,  "estimate",    "Performs FileSet estimate, listing gives full listing");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (100, "exit",        "Terminate Bconsole session");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (110, "gui",         "Non-interactive gui mode");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (120, "help",        "Print help on specific command");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (130, "label",       "Label a tape");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (140, "list",        "List objects from catalog");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (150, "llist",       "Full or long list like list command");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (160, "messages",    "Display pending messages");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (170, "memory",      "Print current memory usage");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (180, "mount",       "Mount storage");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (190, "prune",       "Prune expired records from catalog");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (200, "purge",       "Purge records from catalog");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (210, "python",      "Python control commands");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (220, "quit",        "Terminate Bconsole session");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (230, "query",       "Query catalog");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (240, "restore",     "Restore files");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (250, "relabel",     "Relabel a tape");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (260, "release",     "Release storage");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (270, "reload",      "Reload conf file");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (280, "run",         "Run a job");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (290, "status",      "Report status");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (300, "setdebug",    "Sets debug level");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (310, "setip",       "Sets new client address, if authorized");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (320, "show",        "Show resource records");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (330, "sqlquery",    "Use SQL to query catalog");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (340, "time",        "Print current time");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (350, "trace",       "Turn on/off trace to file");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (360, "unmount",     "Unmount storage");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (370, "umount",      "Umount - for old-time Unix guys, see unmount");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (380, "update",      "Update volume, pool or stats");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (390, "use",         "Use catalog xxx");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (400, "var",         "Does variable expansion");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (410, "version",     "Print Director version");
INSERT INTO webacula_dt_commands (id, name, description) VALUES (420, "wait",        "Wait until no jobs are running");



CREATE TABLE IF NOT EXISTS webacula_client_acl (
    id        integer not null auto_increment,
    name      char(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



CREATE TABLE IF NOT EXISTS webacula_fileset_acl (
    id        integer not null auto_increment,
    name      char(127),
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



CREATE TABLE IF NOT EXISTS webacula_where_acl (
    id        integer not null auto_increment,
    name      TEXT NOT NULL,
    order_acl integer,
    role_id   integer,
    PRIMARY KEY (id),
    INDEX (id, order_acl)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



-- 'root_role' Bacula ACLs
INSERT INTO webacula_storage_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 1); 
INSERT INTO webacula_pool_acl    (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_client_acl  (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_fileset_acl (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_where_acl   (name, order_acl, role_id)  VALUES ('*all*', 1, 1);
INSERT INTO webacula_command_acl (dt_id, order_acl, role_id) VALUES (1, 1, 1);



END-OF-DATA
then
   echo "Creation of Webacula ACLs MySQL tables succeeded."
else
   echo "Creation of Webacula ACLs MySQL tables failed."
fi
exit 0
