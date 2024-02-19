#!/bin/sh
tar -xf heffte-2.4.0.tar.gz
cd heffte-2.4.0
mkdir build
cd build
CMAKE_FLAGS="-DHeffte_ENABLE_FFTW=ON "
if grep avx /proc/cpuinfo > /dev/null
then
	CMAKE_FLAGS="$CMAKE_FLAGS -DHeffte_ENABLE_AVX=ON"
fi
if grep avx512 /proc/cpuinfo > /dev/null
then
	CMAKE_FLAGS="$CMAKE_FLAGS -DHeffte_ENABLE_AVX512=ON"
fi
cmake .. -DCMAKE_BUILD_TYPE=Release $CMAKE_FLAGS
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd heffte-2.4.0/build/benchmarks
OMP_NUM_THREADS=\$CPU_THREADS_PER_CORE mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES speed3d_\$@ -n200 > \$LOG_FILE
echo \$? > ~/test-exit-status" > heffte
chmod +x heffte
