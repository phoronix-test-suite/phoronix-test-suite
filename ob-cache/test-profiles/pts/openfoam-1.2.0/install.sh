#!/bin/bash
rm -rf OpenFOAM-10
tar -xf OpenFOAM-10-20220831.tar.gz

mv OpenFOAM-10-20220831 OpenFOAM-10
cd OpenFOAM-10
source etc/bashrc
./Allwmake -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

cat>openfoam<<EOT
#!/bin/bash
cd OpenFOAM-10
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
