#!/bin/sh

tar -xf qmcpack-3.11.0.tar.gz
cd qmcpack-3.11.0/build/
cmake .. -DCMAKE_BUILD_TYPE=Release

# Run make twice as seems to hit errors on first build but completes fine on second time
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~/

cat>qmcpack<<EOT
#!/bin/sh
cd qmcpack-3.11.0/build/examples/molecules/\$1
OMP_NUM_THREADS=1 mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ~/qmcpack-3.11.0/build/bin/qmcpack \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x qmcpack


