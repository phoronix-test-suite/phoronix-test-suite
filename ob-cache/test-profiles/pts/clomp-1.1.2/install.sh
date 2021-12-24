#!/bin/sh

unzip -o clomp_v1.2.zip
cd clomp_v1.2/

cc -fopenmp -O3 $CFLAGS clomp.c -o clomp_build -lm
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd clomp_v1.2/
export OMP_NUM_THREADS=\$NUM_CPU_CORES
./clomp_build \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > clomp
chmod +x clomp
