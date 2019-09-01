#!/bin/sh

tar -xf gromacs-2018.3.tar.gz
tar -xf water_GMX50_bare.tar.gz
mkdir build
cd build
cmake ../gromacs-2018.3 -DGMX_OPENMP=ON -DGMX_GPU=ON -DGMX_BUILD_OWN_FFTW=ON -DGMX_PREFER_STATIC_LIBS=ON -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh

cd water-cut1.0_GMX50_bare/1536
\$HOME/build/bin/gmx grompp -f pme.mdp 
\$HOME/build/bin/gmx mdrun -resethway -noconfout -nsteps 4000 -v -pin on -nb gpu > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gromacs-gpu
chmod +x gromacs-gpu
