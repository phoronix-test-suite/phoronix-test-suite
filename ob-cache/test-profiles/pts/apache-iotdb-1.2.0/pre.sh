#!/bin/bash
cd ~/apache-iotdb-1.2.0-all-bin/
rm -rf data
rm -rf logs
cd ~/apache-iotdb-1.2.0-all-bin/sbin
./start-standalone.sh
sleep 3
