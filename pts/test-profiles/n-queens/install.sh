#!/bin/sh

tar -xvf qn24b-version1.0.tgz
cd version1.0/omp/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ../../

echo "#!/bin/sh
cd version1.0/omp/
OMP_NUM_THREADS=\$NUM_CPU_CORES ./qn24b_openmp \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > n-queens
chmod +x n-queens
