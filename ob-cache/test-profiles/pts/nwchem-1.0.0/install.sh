#!/bin/sh

tar -xf nwchem-7.0.0-release.revision-2c9a1c7c-srconly.2020-02-26.tar.bz2
cd nwchem-7.0.0/src

echo "NWCHEM_LONG_PATHS = Y" >> config/makefile.h

NWCHEM_TOP=$HOME/nwchem-7.0.0 NWCHEM_LONG_PATHS=Y NWCHEM_TARGET=LINUX64 USE_MPI=y BLASOPT="-lopenblas -lpthread -lrt" LAPACK_LIB="-llapack" NWCHEM_MODULES="all" make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
cat>nwchem<<EOT
#!/bin/sh
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES \$HOME/nwchem-7.0.0/bin/LINUX64/nwchem \$HOME/Input_c240_pbe0.nw > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x nwchem

