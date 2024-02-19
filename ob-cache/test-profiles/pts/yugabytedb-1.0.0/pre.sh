#!/bin/bash
rm -rf ~/var
cd yugabyte-2.19.0.0
python3 ./bin/yugabyted start
sleep 10
