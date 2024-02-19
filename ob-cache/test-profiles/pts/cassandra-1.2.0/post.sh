#!/bin/sh
CASSANDRA_SERVER_PID=`cat ~/cassandra-server-pid`
kill -9 $CASSANDRA_SERVER_PID
sleep 3
cd apache-cassandra-4.1.3/
rm -rf data/
