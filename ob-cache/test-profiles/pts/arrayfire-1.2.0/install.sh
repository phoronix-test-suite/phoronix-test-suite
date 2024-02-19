#!/bin/sh
tar -xf arrayfire-full-3.9.0.tar.bz2
cd arrayfire-full-v3.9.0/
mkdir build
cd build
cmake -DAF_BUILD_EXAMPLES=ON -DAF_BUILD_CUDA=OFF -DBUILD_TEST=OFF -DBUILD_TESTING=OFF -DCMAKE_BUILD_TYPE=Release ..
make -j $NUM_CPU_CORES
cd ~/
echo "#!/bin/sh
cd arrayfire-full-v3.9.0/build/examples/benchmarks
./\$@ > \$LOG_FILE 2>&1
" > arrayfire
chmod +x arrayfire
