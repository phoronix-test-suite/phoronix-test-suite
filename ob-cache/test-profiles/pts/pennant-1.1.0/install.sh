#!/bin/sh

tar -xvf pennant-1.0.1-makefile-update.tar.xz

cd PENNANT-1.0.1/
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/

cat>pennant<<EOT
#!/bin/sh
cd PENNANT-1.0.1/
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./build/pennant test/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x pennant


