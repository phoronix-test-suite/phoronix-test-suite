#!/bin/sh
cd apache-cassandra-4.1.3/
rm -rf data/
cd bin/
./cassandra -R -p ~/cassandra-server-pid
sleep 10
# Prep  fill
cd ~/apache-cassandra-4.1.3/tools/bin
./cassandra-stress write -rate threads=\$NUM_CPU_CORES
