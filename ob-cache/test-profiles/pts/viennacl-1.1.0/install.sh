#!/bin/sh

tar -xf ViennaCL-1.7.1.tar.gz

cd ViennaCL-1.7.1/build/
cmake -DCMAKE_BUILD_TYPE=Release -DENABLE_OPENMP=ON ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
./ViennaCL-1.7.1/build/examples/benchmarks/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > viennacl
chmod +x viennacl
