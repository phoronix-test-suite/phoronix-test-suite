#!/bin/sh

tar -xf mkl-dnn-1.1.tar.gz

cd mkl-dnn-1.1/
#./scripts/prepare_mkl.sh 

mkdir build 
cd build 
CFLAGS="-O3 -march=native" CXXFLAGS="-O3 -march=native" cmake -DCMAKE_BUILD_TYPE=Release MKLDNN_ARCH_OPT_FLAGS="-O3 -march=native" $CMAKE_OPTIONS ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/bash
export OMP_PLACES=cores
export OMP_PROC_BIND=close
cd mkl-dnn-1.1/build/tests/benchdnn
./benchdnn --mode=p \$1 \$3 \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mkl-dnn
chmod +x mkl-dnn
