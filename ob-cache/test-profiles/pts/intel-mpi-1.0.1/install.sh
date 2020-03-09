#!/bin/sh

tar -xf mpi-benchmarks-IMB-v2019.3.tar.gz
cd mpi-benchmarks-IMB-v2019.3/
CC=mpicc CXX=mpic++ make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd mpi-benchmarks-IMB-v2019.3/
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$@ -iter 1000000 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > intel-mpi
chmod +x intel-mpi
