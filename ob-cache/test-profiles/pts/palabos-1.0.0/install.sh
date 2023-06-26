#!/bin/sh
tar -xf palabos-3858237f99567fc100e3dbce47cbb2714b6bc67e.tar.gz
cd palabos-3858237f99567fc100e3dbce47cbb2714b6bc67e/build
cmake -DCMAKE_BUILD_TYPE=Release -DENABLE_MPI=ON -DBUILD_HDF5=ON ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd palabos-3858237f99567fc100e3dbce47cbb2714b6bc67e/build
OMP_NUM_THREADS=\$CPU_THREADS_PER_CORE mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ../examples/benchmarks/cavity3d/cavity3d_benchmark \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > palabos
chmod +x palabos
