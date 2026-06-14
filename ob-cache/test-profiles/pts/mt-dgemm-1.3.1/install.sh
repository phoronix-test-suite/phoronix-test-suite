#!/bin/sh
tar -xf mtdgemm-crossroads-v1.0.0.tgz
mkdir $HOME/openblas_
unzip -o OpenBLAS-2e2f952bfbe92074db50ae1191afabc63deb510a.zip
cd OpenBLAS-2e2f952bfbe92074db50ae1191afabc63deb510a
make NUM_THREADS=1024 USE_THREAD=1 USE_OPENMP=1 DYNAMIC_ARCH=1 -j $NUM_CPU_CORES
make PREFIX=$HOME/openblas_ NUM_THREADS=1024 USE_THREAD=1 USE_OPENMP=1 DYNAMIC_ARCH=1 install
cd ~
cc -march=native -ffast-math $CFLAGS -ftree-vectorizer-verbose=3 -O3 -fopenmp -DUSE_CBLAS -I./openblas_/include -o mtdgemm mt-dgemm/src/mt-dgemm.c -L./openblas_/lib -lopenblas
echo $? > ~/install-exit-status
rm -rf mt-dgemm
cat <<'EOF' > mt-dgemm
#!/bin/sh
export OMP_NUM_THREADS=$NUM_CPU_PHYSICAL_CORES
export OMP_PLACES=cores
export OMP_PROC_BIND=close
export OMP_SCHEDULE=STATIC
export OMP_WAIT_POLICY=ACTIVE
export LD_LIBRARY_PATH=./openblas_/lib:$LD_LIBRARY_PATH
./mtdgemm $@ > $LOG_FILE 2>&1
echo $? > ~/test-exit-status
EOF
chmod +x mt-dgemm
