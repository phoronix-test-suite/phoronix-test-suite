#!/bin/sh

cd ~/spark-3.3.0-bin-hadoop3/bin

rm -rf $HOME/test-data

./spark-submit --name 'generate-benchmark-test-data' $HOME/pyspark-benchmark/generate-data.py $HOME/test-data $@

