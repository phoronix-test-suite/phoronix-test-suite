#!/bin/sh

tar -xf kripke-v1.2.4-d85c6bc.tar.gz
cd kripke-v1.2.4-d85c6bc
mkdir build
cd build
cmake -DENABLE_OPENMP=TRUE ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd kripke-v1.2.4-d85c6bc/build/
OMP_NUM_THREADS=\$NUM_CPU_CORES ./bin/kripke.exe \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
" > kripke

chmod +x kripke
