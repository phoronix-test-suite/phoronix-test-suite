#!/bin/bash

rm -rf OpenFOAM-9
tar -xf OpenFOAM-9-20220602.tar.gz

mv OpenFOAM-9-20220602 OpenFOAM-9
cd OpenFOAM-9
source etc/bashrc
./Allwmake -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

cat>openfoam<<EOT
#!/bin/bash
cd OpenFOAM-9
source etc/bashrc

cd tutorials/\$1
./Allclean
# Pass any options, drivaerFastback also has a -c cores option
./Allrun \$2 \$3 -c \$NUM_CPU_PHYSICAL_CORES
echo \$? > ~/test-exit-status
cat log.snappyHexMesh > \$LOG_FILE 2>&1
cat log.simpleFoam >> \$LOG_FILE 2>&1
./Allclean
EOT
chmod +x openfoam
