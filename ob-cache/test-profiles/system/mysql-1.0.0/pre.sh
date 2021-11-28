#!/bin/sh
set -x
ps cax | grep mysqld
if [ $? -eq 0 ]; then
    echo "mysql server is running."
else
    echo "mysql server is not running."
    systemctl start mysqld.service
fi
# create random password
# PASSWDDB="$(openssl rand -base64 12)"
PASSWDDB="hellomysql123"

# replace "-" with "_" for database username
# MAINDB=${USER_NAME//[^a-zA-Z0-9]/_}
MAINDB="test"

mysql -u root <<MYSQL_SCRIPT
use mysql;
set global time_zone = '+8:00';
set time_zone = '+8:00';
set global max_connect_errors = 655350;
set global max_prepared_stmt_count=1000000;
SET GLOBAL max_connections = 10000;
CREATE DATABASE IF NOT EXISTS ${MAINDB} /*\!40100 DEFAULT CHARACTER SET utf8 */;
CREATE USER IF NOT EXISTS ${MAINDB}@localhost IDENTIFIED BY '${PASSWDDB}';
GRANT ALL PRIVILEGES ON ${MAINDB}.* TO '${MAINDB}'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT
