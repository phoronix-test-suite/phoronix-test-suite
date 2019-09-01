#!/bin/sh

unzip -o OctaneBench_4_00b_linux.zip

echo "#!/bin/bash
cd OctaneBench_4_00b_linux/
export HOME=\${DEBUG_REAL_HOME%/}
./octane  --benchmark --no-gui -g 0 -a \$LOG_FILE
echo \$? > ~/test-exit-status" > octanebench
chmod +x octanebench
