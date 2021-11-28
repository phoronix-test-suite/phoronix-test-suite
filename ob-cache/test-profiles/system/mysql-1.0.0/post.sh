#!/bin/sh
set -x
# create random password
# PASSWDDB="$(openssl rand -base64 12)"
PASSWDDB="hellomysql123"

# replace "-" with "_" for database username
# MAINDB=${USER_NAME//[^a-zA-Z0-9]/_}
MAINDB="test"

mysql -u root <<MYSQL_SCRIPT
DROP DATABASE IF EXISTS ${MAINDB} /*\!40100 DEFAULT CHARACTER SET utf8 */;
MYSQL_SCRIPT

ps cax | grep mysqld
if [ $? -eq 0 ]; then
    echo "mysql server is running."
    systemctl stop mysqld.service
else
    echo "mysql server is not running."
fi
rm -rf /var/lib/mysql/*
