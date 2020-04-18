#!/bin/sh

tar -xf nettle-3.5.1.tar.gz
cd nettle-3.5.1
./configure --prefix=$HOME/install
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install

cd ~/
echo "#!/bin/sh
cd nettle-3.5.1/examples/
LD_LIBRARY_PATH=\$HOME/install/lib64:\$HOME/install/lib:\$LD_LIBRARY_PATH ./nettle-benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nettle
chmod +x nettle
