#!/bin/sh
tar -xf oidn-2.0.0.x86_64.macos.tar.gz
echo "#!/bin/sh
cd oidn-2.0.0.x86_64.macos/bin/
./oidnBenchmark \$@ --threads \$NUM_CPU_CORES  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oidn
chmod +x oidn
