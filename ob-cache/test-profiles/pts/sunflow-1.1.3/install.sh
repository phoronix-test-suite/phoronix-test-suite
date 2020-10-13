#!/bin/sh

unzip -o sunflow-bin-v0.07.2.zip

echo "#!/bin/sh
cd sunflow

java -jar sunflow.jar -bench > \$LOG_FILE 2>&1" > sunflow-benchmark
chmod +x sunflow-benchmark
