#!/bin/sh

tar -xf mtdgemm-crossroads-v1.0.0.tgz


cc -O3 -march=native -fopenmp $CFLAGS -o mtdgemm mt-dgemm/src/mt-dgemm.c
echo $? > ~/install-exit-status
rm -rf mt-dgemm

echo "#!/bin/sh
export OMP_NUM_THREADS=\$NUM_CPU_CORES
export OMP_PLACES=cores
export OMP_PROC_BIND=close
./mtdgemm \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mt-dgemm
chmod +x mt-dgemm
