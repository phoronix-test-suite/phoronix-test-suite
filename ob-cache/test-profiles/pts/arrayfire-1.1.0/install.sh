#!/bin/sh

chmod +x ArrayFire-v3.7.0_Linux_x86_64.sh
./ArrayFire-v3.7.0_Linux_x86_64.sh --prefix t --skip-license
cd share/ArrayFire/examples/
mkdir build
cd build
cmake -DArrayFire_DIR=$HOME/share/ArrayFire/cmake/ -DAF_BUILD_CUDA=OFF ..
make -j $NUM_CPU_THREADS

cd ~/
echo "#!/bin/sh
cd share/ArrayFire/examples/build
./benchmarks/\$1 > \$LOG_FILE 2>&1
" > arrayfire
chmod +x arrayfire
