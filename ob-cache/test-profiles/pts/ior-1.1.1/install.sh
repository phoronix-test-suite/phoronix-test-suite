#!/bin/sh

tar -xf ior-3.3.0.tar.gz
cd ior-3.3.0
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ior-3.3.0
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./src/ior \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/ior
chmod +x ~/ior
