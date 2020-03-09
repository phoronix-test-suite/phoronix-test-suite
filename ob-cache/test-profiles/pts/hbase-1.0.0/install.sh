#!/bin/sh

tar -xf hbase-2.2.3-bin.tar.gz

echo "#!/bin/bash
cd hbase-2.2.3
JAVA_HOME=/usr ./bin/hbase pe \$@ > output.log 2>&1
cat output.log | grep PerformanceEvaluation > \$LOG_FILE
" > hbase
chmod +x hbase
