#!/bin/bash

rm -rf OpenFOAM-8
tar -xf OpenFOAM-8-20201114.tar.gz
tar -xf OpenFOAM-Intel-20170512.tar.xz

mv OpenFOAM-8-20201114 OpenFOAM-8
cd OpenFOAM-8
source etc/bashrc
./Allwmake -j
echo $? > ~/install-exit-status
cd ~/

# Not using mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES due to issues and already being threaded at least for single node systems
cat>openfoam<<EOT
#!/bin/bash
cd OpenFOAM-8
source etc/bashrc

# \$1 is the test in case of multiple inputs
cd ~/OpenFOAM-Intel-master/benchmarks/motorbike/
./Clean

./Mesh \$2 \$3 \$4
./Setup \$NUM_CPU_PHYSICAL_CORES
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES simpleFoam -parallel > \$LOG_FILE 2>&1
EOT
chmod +x openfoam
