#!/bin/sh
unzip -o oidn-2.2.0.x64.windows.zip
echo "#!/bin/sh
cd oidn-2.2.0.x64.windows/bin/
./oidnBenchmark.exe \$@ --threads \$NUM_CPU_CORES  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oidn
chmod +x oidn
