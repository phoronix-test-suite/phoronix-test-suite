#!/bin/sh

mkdir $HOME/ape_

tar -xf monkeys-audio-release-3.99.6.tar.gz
cd monkeys-audio-release-3.99.6
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd monkeys-audio-release-3.99.6/build/
./mac \$TEST_EXTENDS/pts-trondheim.wav out.ape -c5000 > \$LOG_FILE 2>&1" > encode-ape
chmod +x encode-ape
