#!/bin/sh

tar -xf gromacs-2022.1.tar.gz
tar -xf water_GMX50_bare.tar.gz

mkdir mpi-build
cd mpi-build
cmake ../gromacs-2022.1 -DGMX_OPENMP=OFF -DGMX_MPI=ON -DGMX_GPU=OFF -DGMX_BUILD_OWN_FFTW=ON -DCMAKE_BUILD_TYPE=Release -DBUILD_SHARED_LIBS=ON
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
echo "#!/bin/sh
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/mpi-build/bin/gmx_mpi grompp -f pme.mdp  -o bench.tpr
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/mpi-build/bin/gmx_mpi mdrun -resethway -npme 0 -notunepme -noconfout -nsteps 1000 -v -s  bench.tpr
" >  run-gromacs
chmod +x run-gromacs
cd ~

mkdir cuda-build
if ! which nvcc >/dev/null 2>&1 ;
then
	if [ -d /usr/local/cuda ]
	then
		export LD_LIBRARY_PATH=/usr/local/cuda/lib64/:$LD_LIBRARY_PATH
		export PATH=/usr/local/cuda/bin/:$PATH
	fi
fi

if which nvcc >/dev/null 2>&1 ;
then
	cd cuda-build
	cmake ../gromacs-2022.1 -DGMX_MPI=OFF -DGMX_OPENMP=ON -DGMX_GPU=CUDA -DGMX_BUILD_OWN_FFTW=ON -DCMAKE_BUILD_TYPE=Release -DBUILD_SHARED_LIBS=ON
	make -j $NUM_CPU_CORES
	
	echo "#!/bin/sh
	\$HOME/cuda-build/bin/gmx grompp -f pme.mdp 
	\$HOME/cuda-build/bin/gmx mdrun -resethway -noconfout -nsteps 4000 -v -pin on -nb gpu
	" >  run-gromacs
	chmod +x run-gromacs
	cd ~
fi

echo "#!/bin/sh
unset OMP_NUM_THREADS
cd \$2
rm -f *bench.tpr*
\$HOME/\$1/run-gromacs > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gromacs
chmod +x gromacs
