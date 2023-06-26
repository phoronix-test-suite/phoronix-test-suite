#!/bin/sh
rm -rf petsc-3.19.0
tar -xf petsc-3.19.0.tar.gz
cd petsc-3.19.0
./configure --download-fblaslapack=1 --with-debugging=0 COPTFLAGS='-O3' CXXOPTFLAGS='-O3' FOPTFLAGS='-O3 -march=native' --download-mpich
make PETSC_DIR=`pwd` PETSC_ARCH=arch-linux-c-opt all
echo $? > ~/install-exit-status
echo "#" > src/benchmarks/streams/process.py 
cd ~
echo "#!/bin/bash
cd petsc-3.19.0
make streams > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > petsc
chmod +x petsc
