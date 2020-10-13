#!/bin/sh

tar -xf mocassin-20190324.tar.xz
cd mocassin-master
make
make install
echo $? > ~/install-exit-status

cd ~/
cat>mocassin<<EOT
#!/bin/sh
cd mocassin-master
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./mocassin > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x mocassin

