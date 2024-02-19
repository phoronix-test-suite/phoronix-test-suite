#!/bin/sh
tar -xf spark-3.5.0-bin-hadoop3.tgz
unzip -o tpch-spark-7d64aa57368f092969a855fdd10781c00813c9c4.zip
unzip -o sbt-1.9.7.zip
export PATH=$HOME/spark-3.5.0-bin-hadoop3/bin:$HOME/sbt/bin:$PATH
# Avoid out of memory errors
echo "spark.driver.memory              16g
spark.executor.memory              16g" > ~/spark-3.5.0-bin-hadoop3/conf/spark-defaults.conf
cd tpch-spark-7d64aa57368f092969a855fdd10781c00813c9c4/dbgen/
make
cd ~/tpch-spark-7d64aa57368f092969a855fdd10781c00813c9c4/
sbt package
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
export PATH=\$HOME/spark-3.5.0-bin-hadoop3/bin:\$PATH
cd tpch-spark-7d64aa57368f092969a855fdd10781c00813c9c4/
rm -f tpch_execution_times.txt
spark-submit --class \"main.scala.TpchQuery\" target/scala-2.12/spark-tpc-h-queries_2.12-1.0.jar > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
cat tpch_execution_times.txt >> \$LOG_FILE
" > spark-tpch
chmod +x spark-tpch
