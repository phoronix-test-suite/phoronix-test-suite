#!/bin/sh
# STOP SERVER
cd mysql_
./bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` -pphoronix shutdown
sleep 5
