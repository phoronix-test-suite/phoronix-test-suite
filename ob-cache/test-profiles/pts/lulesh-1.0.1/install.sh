#!/bin/sh

tar -xf lulesh2.0.3.tar.xz
cd lulesh2.0.3
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
cat>lulesh<<EOT
#!/bin/bash
cd lulesh2.0.3
if [ -z \${NUM_CPU_PHYSICAL_CORES_CUBE+x} ]; then NUM_CPU_PHYSICAL_CORES_CUBE=\$NUM_CPU_PHYSICAL_CORES; fi
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES_CUBE ./lulesh2.0 -s 36 -i 1 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x lulesh

