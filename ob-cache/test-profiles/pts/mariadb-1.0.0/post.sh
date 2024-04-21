#!/bin/sh
# STOP SERVER
cd mariadb_
./bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` -pphoronix shutdown
sleep 5
