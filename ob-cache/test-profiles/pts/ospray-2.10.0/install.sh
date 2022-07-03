#!/bin/sh

tar -xf ospray-2.10.0.x86_64.linux.tar.gz

echo "#!/bin/sh
cd ospray-2.10.0.x86_64.linux/bin
./ospBenchmark --benchmark_min_time=60 \$@ > \$LOG_FILE 2>&1
sed -i 's/=/ /' \$LOG_FILE
echo \$? > ~/test-exit-status" > ospray
chmod +x ospray
