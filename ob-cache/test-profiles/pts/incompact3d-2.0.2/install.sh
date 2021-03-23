#!/bin/sh

tar -xf Incompact3d-20210311.tar.xz
cd Incompact3d-20210311

# Current Xcompact3d code runs into errors on Ubuntu with default -O3 level for now...
sed -i 's/-O3/-O2/g' Makefile

# Multiple make jobs yields errors for this test...
make
echo $? > ~/install-exit-status

# Avoid possible memory issues
cp input.i3d input_129_nodes.i3d
sed -i 's/nx=513/nx=129/g' input_129_nodes.i3d
sed -i 's/ny=513/ny=129/g' input_129_nodes.i3d
sed -i 's/nz=513/nz=129/g' input_129_nodes.i3d

# Avoid possible memory issues
cp input.i3d input_193_nodes.i3d
sed -i 's/nx=513/nx=193/g' input_193_nodes.i3d
sed -i 's/ny=513/ny=193/g' input_193_nodes.i3d
sed -i 's/nz=513/nz=193/g' input_193_nodes.i3d

cd ~/
cat>incompact3d<<EOT
#!/bin/sh
cd Incompact3d-20210311
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES xcompact3d \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x incompact3d

