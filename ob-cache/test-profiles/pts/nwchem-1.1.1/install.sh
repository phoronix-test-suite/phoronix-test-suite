#!/bin/sh

tar -xf nwchem-7.0.2-release.revision-b9985dfa-srconly.2020-10-12.tar.bz2
cd nwchem-7.0.2/src

echo "NWCHEM_LONG_PATHS = Y" >> config/makefile.h

NWCHEM_TOP=$HOME/nwchem-7.0.2 NWCHEM_LONG_PATHS=Y NWCHEM_TARGET=LINUX64 USE_MPI=y BLASOPT="-lopenblas -lpthread -lrt" LAPACK_LIB="-llapack" NWCHEM_MODULES="all" make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
cat>nwchem<<EOT
#!/bin/sh
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/nwchem-7.0.2/bin/LINUX64/nwchem \$HOME/Input_c240_pbe0.nw > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x nwchem

