#!/bin/sh

tar -xf Incompact3d-20200917.tar.xz
cd Incompact3d-20200917
make
echo $? > ~/install-exit-status

cd ~/
cat>incompact3d<<EOT
#!/bin/sh
cd Incompact3d-20200917
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES xcompact3d \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x incompact3d

