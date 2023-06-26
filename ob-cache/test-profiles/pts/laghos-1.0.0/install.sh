#!/bin/bash
tar -xf hypre-2.28.0.tar.gz
rm -rf hypre
mv hypre-2.28.0 hypre
cd hypre/src/
./configure --disable-fortran --enable-bigint
make -j $NUM_CPU_CORES
cd ~
tar -xf metis-4.0.3.tar.gz
mv metis-4.0.3 metis-4.0
cd metis-4.0
make
cd ~
tar -xf mfem-4.5.2.tar.gz
rm -rf mfem
mv mfem-4.5.2 mfem
cd mfem
make parallel -j
cd ~
tar -xf Laghos-3.1.tar.gz
cd Laghos-3.1
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
cat>laghos<<EOT
#!/bin/sh
cd Laghos-3.1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./laghos \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x laghos

