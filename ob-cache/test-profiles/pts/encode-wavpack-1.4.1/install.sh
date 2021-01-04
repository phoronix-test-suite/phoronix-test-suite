#!/bin/sh

tar -xf WavPack-5.3.0.tar.gz
cd WavPack-5.3.0
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./WavPack-5.3.0/build/wavpack -q -r -hhx3 -o out.wv \$TEST_EXTENDS/pts-trondheim.wav > /dev/null 2>&1
echo \$? > ~/test-exit-status" > encode-wavpack
chmod +x encode-wavpack
