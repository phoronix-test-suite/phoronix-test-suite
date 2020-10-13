#!/bin/sh

unzip -o OctaneBench_2020_1_5_linux.zip

echo "#!/bin/bash
cd OctaneBench_2020_1_5_linux/
export HOME=\${DEBUG_REAL_HOME%/}
./octane  --benchmark -g 0 -a \$LOG_FILE
echo \$? > ~/test-exit-status" > octanebench
chmod +x octanebench
