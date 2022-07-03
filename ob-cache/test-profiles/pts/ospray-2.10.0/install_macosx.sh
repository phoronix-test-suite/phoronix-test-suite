#!/bin/sh

unzip -o ospray-2.10.0.x86_64.macosx.zip

echo "#!/bin/sh
cd ospray-2.10.0.x86_64.macosx/bin
./ospBenchmark --benchmark_min_time=60 \$@ > \$LOG_FILE 2>&1
sed -i 's/=/ /' \$LOG_FILE
echo \$? > ~/test-exit-status" > ospray
chmod +x ospray
