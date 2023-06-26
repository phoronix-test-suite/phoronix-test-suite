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
unzip -o Remhos-57d44084239b42a5e7374d46c793d94102dc939c.zip
cd Remhos-57d44084239b42a5e7374d46c793d94102dc939c
make
echo $? > ~/install-exit-status
cd ~
cat>remhos<<EOT
#!/bin/sh
cd Remhos-57d44084239b42a5e7374d46c793d94102dc939c
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./remhos \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x remhos

