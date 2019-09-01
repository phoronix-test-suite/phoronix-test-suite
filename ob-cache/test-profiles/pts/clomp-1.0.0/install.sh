#!/bin/sh

tar -zxvf clomp_v1.0.tar.gz
cd clomp_v1.0/

gcc --openmp -O3 clomp.c -o clomp_build -lm
echo \$? > ~/test-exit-status

cd ~/
echo "#!/bin/sh
cd clomp_v1.0/
export OMP_NUM_THREADS=\$NUM_CPU_CORES
export KMP_BLOCKTIME=10000
./clomp_build \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > clomp
chmod +x clomp
