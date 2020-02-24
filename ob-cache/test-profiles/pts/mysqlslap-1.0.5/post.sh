#!/bin/sh

# STOP SERVER
cd mysql_
./bin/mysqladmin -u root -pphoronix shutdown
sleep 5
