#!/bin/bash
cd ~/apache-iotdb-1.2.0-all-bin/sbin
./stop-standalone.sh
sleep 3
cd ~/apache-iotdb-1.2.0-all-bin/
rm -rf data
rm -rf logs
