#!/bin/sh

tar -xf spark-3.3.0-bin-hadoop3.tgz
unzip -o pyspark-benchmark-3bbf3e521763517bc9aa73504dd66a5bdeb5b6af.zip
rm -rf pyspark-benchmark
mv pyspark-benchmark-3bbf3e521763517bc9aa73504dd66a5bdeb5b6af pyspark-benchmark

# Avoid out of memory errors
echo "spark.driver.memory              6g" > ~/spark-3.3.0-bin-hadoop3/conf/spark-defaults.conf

echo "#!/bin/bash
cd ~/spark-3.3.0-bin-hadoop3/bin
./spark-submit --name 'benchmark-shuffle' \$HOME/pyspark-benchmark/benchmark-shuffle.py \$HOME/test-data > \$LOG_FILE 2>&1
./spark-submit --name 'benchmark-cpu' \$HOME/pyspark-benchmark/benchmark-cpu.py \$HOME/test-data >> \$LOG_FILE 2>&1
" > spark
chmod +x spark
