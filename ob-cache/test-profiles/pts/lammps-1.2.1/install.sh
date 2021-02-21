#!/bin/sh

tar -xf lammps-patch_24Aug2020.tar.gz
cd lammps-patch_24Aug2020/
mkdir b
cd b
cmake ../cmake/ -DCMAKE_BUILD_TYPE=Release -DPKG_MOLECULE=1 -DPKG_KSPACE=1 -DPKG_RIGID=1 -DPKG_GRANULAR=1 -DPKG_MANYBODY=1 -DBUILD_OMP=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/

tar -xf lammps-hecbiosim-1.tar.gz
mv lammps/20k-atoms/benchmark.data lammps-patch_24Aug2020/bench/benchmark.data
mv lammps/20k-atoms/benchmark.in lammps-patch_24Aug2020/bench/benchmark_20k_atoms.in
rm -rf lammps

cat>lammps<<EOT
#!/bin/sh
cd lammps-patch_24Aug2020/bench/
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ../b/lmp < \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x lammps

