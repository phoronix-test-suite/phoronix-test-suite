#!/bin/bash
cd yugabyte-2.19.0.0
python3 ./bin/yugabyted stop
sleep 3
rm -rf ~/var
