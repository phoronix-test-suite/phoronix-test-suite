#!/bin/sh

tar -xzvf lammps-22Jun07_v1.0.tgz
cd lammps-22Jun07/src/
make debian
echo $? > ~/install-exit-status
cp lmp_debian ../bench

cd ~/

cat>lammps<<EOT
#!/bin/sh
cd ~/lammps-22Jun07/bench/
mpirun -np \$NUM_CPU_CORES lmp_debian < \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x lammps

