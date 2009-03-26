#!/bin/sh

tar -xjf povray-3.6.1.tar.bz2

THIS_DIR=$(pwd)
mkdir $THIS_DIR/pov_

cd povray-3.6.1/
./configure --prefix=$THIS_DIR/pov_ COMPILED_BY="PhoronixTestSuite"
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf povray-3.6.1/

echo "#!/bin/sh
echo 1 | ./pov_/bin/povray -benchmark > \$LOG_FILE 2>&1" > povray
chmod +x povray
