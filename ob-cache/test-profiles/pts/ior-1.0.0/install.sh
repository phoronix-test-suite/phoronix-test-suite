#!/bin/sh

tar -xf ior-3.2.1.tar.gz
cd ior-3.2.1
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ior-3.2.1
./src/ior \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/ior
chmod +x ~/ior
