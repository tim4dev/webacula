#!/bin/sh
#
# Script to create Webacula ACLs tables in Bacula database
#

db_name="bacula"
db_password=""
host="localhost"

#if mysql $* -f <<END-OF-DATA

# !!! for debug only !!!
if mysql -u root -f <<END-OF-DATA

USE bacula;

-- !!! for debug only !!!
drop table if exists webacula_users;
drop table if exists webacula_roles;
drop table if exists webacula_resources;
drop table if exists webacula_dt_resources;



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

CREATE INDEX webacula_idx1 ON webacula_users(login);


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

-- !!! for debug only !!!
INSERT INTO webacula_users (id, login, pwd, role_id) VALUES (1001, 'user', PASSWORD('1'), 2);


END-OF-DATA
then
   echo "Creation of Webacula ACLs MySQL tables succeeded."
else
   echo "Creation of Webacula ACLs MySQL tables failed."
fi
exit 0
