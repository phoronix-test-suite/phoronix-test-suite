#!/bin/sh

tar -xf c-blosc2-2.0.0.beta.5.tar.gz

cd c-blosc2-2.0.0.beta.5
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd c-blosc2-2.0.0.beta.5/build/bench
./b2bench \$1 noshuffle suite \$NUM_CPU_CORES > \$LOG_FILE
echo \$? > ~/test-exit-status" > blosc
chmod +x blosc
