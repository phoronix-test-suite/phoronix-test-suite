#!/bin/sh
tar -xf gpuowl-7.5.tar.gz
cd gpuowl-7.5
echo '"c"' > src/version.inc
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd gpuowl-7.5/build
./src/gpuowl \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > gpuowl
chmod +x gpuowl
