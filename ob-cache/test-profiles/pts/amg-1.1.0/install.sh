#!/bin/sh

tar -xf AMG-20200304.tar.xz
rm -rf AMG-bin
mv AMG AMG-bin
cd AMG-bin
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
cat>amg<<EOT
#!/bin/sh
cd AMG-bin

if [ "\$NUM_CPU_CORES" -gt "\$NUM_CPU_PHYSICAL_CORES" ]; then
	export OMP_NUM_THREADS=2
else
	export OMP_NUM_THREADS=1
fi
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./test/amg -n 96 96 96 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x amg

