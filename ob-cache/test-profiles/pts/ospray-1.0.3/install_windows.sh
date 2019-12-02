#!/bin/sh

unzip -o ospray-1.8.5.windows.zip
unzip -o embree-3.6.1.x64.vc14.windows.zip
unzip -o sanm.zip
tar -xf magnetic-512-volume.tar.bz2
tar -xf xfrog-forest.tar.bz2

echo "#!/bin/sh
./ospray-1.8.5.windows/bin/ospBenchmark.exe \$@ --osp:numthreads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray
chmod +x ospray
