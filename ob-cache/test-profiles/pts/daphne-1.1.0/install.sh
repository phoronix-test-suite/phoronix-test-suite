#!/bin/sh
unzip -o daphne-benchmark-6df0108efa43971e89ad105a86a28c0349f5d54d.zip
cd ~/daphne-benchmark-6df0108efa43971e89ad105a86a28c0349f5d54d/data
unzip -o ../../daphne-data-full.zip
tar -xf testcases_full.tgz
rm -f testcases_full.tgz
cd ~/daphne-benchmark-6df0108efa43971e89ad105a86a28c0349f5d54d
cp src/OpenCl/include/benchmark.h src/OpenCl/include/benchmark.h.orig
echo "#include <cstdint>" > src/OpenCl/include/benchmark.h
cat src/OpenCl/include/benchmark.h.orig >> src/OpenCl/include/benchmark.h
cp src/OpenMP/include/benchmark.h src/OpenMP/include/benchmark.h.orig
echo "#include <cstdint>" > src/OpenMP/include/benchmark.h
cat src/OpenMP/include/benchmark.h.orig >> src/OpenMP/include/benchmark.h
cp src/Cuda/include/benchmark.h src/Cuda/include/benchmark.h.orig
echo "#include <cstdint>" > src/Cuda/include/benchmark.h
cat src/Cuda/include/benchmark.h.orig >> src/Cuda/include/benchmark.h
make opencl
make openmp
echo $? > ~/install-exit-status
export PATH=/usr/local/cuda/bin:$PATH
make cuda
cd ~/
echo "#!/bin/sh
cd daphne-benchmark-6df0108efa43971e89ad105a86a28c0349f5d54d/src/\$1/\$2
./kernel -p 100 > \$LOG_FILE 2>&1" > daphne
chmod +x daphne
