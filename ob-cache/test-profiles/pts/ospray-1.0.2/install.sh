#!/bin/sh

tar -xf ospray-1.8.5.x86_64.linux.tar.gz
unzip -o sanm.zip
tar -xf magnetic-512-volume.tar.bz2
tar -xf xfrog-forest.tar.bz2

echo "#!/bin/sh
./ospray-1.8.5.x86_64.linux/bin/ospBenchmark \$@ --osp:numthreads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray
chmod +x ospray
