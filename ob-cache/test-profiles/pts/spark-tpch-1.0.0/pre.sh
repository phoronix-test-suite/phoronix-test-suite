#!/bin/sh
export PATH=$HOME/spark-3.5.0-bin-hadoop3/bin:$PATH
cd tpch-spark-7d64aa57368f092969a855fdd10781c00813c9c4/
rm -f tpch_execution_times.txt
rm -f dbgen/*.tbl
cd dbgen
./dbgen $@
