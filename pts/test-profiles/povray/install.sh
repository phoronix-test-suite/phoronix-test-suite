#!/bin/sh

tar -xjf povray-3.6.1.tar.bz2

mkdir $HOME/pov_

cd povray-3.6.1/
./configure --prefix=$HOME/pov_ COMPILED_BY="PhoronixTestSuite"
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf povray-3.6.1/
rm -rf pov_/share/doc/

echo "#!/bin/sh
echo 1 | ./pov_/bin/povray -benchmark > \$LOG_FILE 2>&1" > povray
chmod +x povray
