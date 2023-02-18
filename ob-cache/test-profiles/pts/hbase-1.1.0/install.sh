#!/bin/sh
tar -xf hbase-2.5.0-bin.tar.gz
echo "#!/bin/bash
cd hbase-2.5.0
JAVA_HOME=/usr ./bin/hbase pe \$@ > output.log 2>&1
echo \$? > ~/test-exit-stats
cat output.log | grep PerformanceEvaluation > \$LOG_FILE" > hbase
chmod +x hbase
