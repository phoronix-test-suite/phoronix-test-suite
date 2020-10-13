#!/bin/sh

tar -xf gromacs-2020.3.tar.gz
tar -xf water_GMX50_bare.tar.gz
mkdir build
cd build
cmake ../gromacs-2020.3 -DGMX_OPENMP=OFF -DGMX_MPI=ON -DGMX_GPU=OFF -DGMX_BUILD_OWN_FFTW=ON -DGMX_PREFER_STATIC_LIBS=ON -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
unset OMP_NUM_THREADS
cd water-cut1.0_GMX50_bare/1536
rm -f *bench.tpr*
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/build/bin/gmx_mpi grompp -f pme.mdp  -o bench.tpr
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/build/bin/gmx_mpi mdrun -resethway -npme 0 -notunepme -noconfout -nsteps 1000 -v -s  bench.tpr > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gromacs
chmod +x gromacs
