#!/bin/sh

tar -xvf ViennaCL-1.4.2.tar.gz

cd ViennaCL-1.4.2/build/
cmake ..
make -j $NUM_CPU_CORES

cd ~/
echo "#!/bin/sh
./ViennaCL-1.4.2/build/examples/benchmarks/blas3bench-opencl > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > viennacl
chmod +x viennacl
