#!/bin/sh

tar -xf mkl-dnn-20190416.tar.xz

cd mkl-dnn-master/
./scripts/prepare_mkl.sh 

mkdir build 
cd build 
cmake -DCMAKE_BUILD_TYPE=Release MKLDNN_ARCH_OPT_FLAGS="-O3 $CXXFLAGS" $CMAKE_OPTIONS ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/bash
export OMP_PLACES=cores
export OMP_PROC_BIND=close
cd mkl-dnn-master/build/tests/benchdnn
./benchdnn --mode=p \$1 \$3 \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mkl-dnn
chmod +x mkl-dnn
