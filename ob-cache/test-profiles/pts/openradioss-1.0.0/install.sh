#!/bin/bash
tar -xf OpenRadioss_linux64_20221013.tar.xz
echo $? > ~/install-exit-status
unzip -o BirdStrike.zip
unzip -o O-Ring_Model.zip
unzip -o Cell_Phone_Drop.zip
unzip -o Bumper_Beam.zip
unzip -o Drop_Container.zip

cat>openradioss<<EOT
#!/bin/sh
cd OpenRadioss_linux64_20221013/exec
export RAD_CFG_PATH=../hm_cfg_files/
export LD_LIBRARY_PATH=../extlib/hm_reader/linux64/:../extlib/h3d/lib/linux64/:\$LD_LIBRARY_PATH
export OMP_NUM_THREADS=1
./starter_linux64_gf -i ~/\$1 -np \$NUM_CPU_PHYSICAL_CORES
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./engine_linux64_gf_ompi -i ~/\$2 > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status

# Cleanup old files
rm -f *0*
rm -f *.h3d
EOT
chmod +x openradioss
