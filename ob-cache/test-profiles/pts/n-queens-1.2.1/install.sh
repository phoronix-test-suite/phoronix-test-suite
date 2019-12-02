#!/bin/sh

tar -xvf qn24b-version1.0.tgz
cd version1.0/omp/

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

cc -Wall -static -fopenmp $CFLAGS main.c -o qn24b_openmp
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd version1.0/omp/
OMP_NUM_THREADS=\$NUM_CPU_CORES ./qn24b_openmp \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > n-queens
chmod +x n-queens
