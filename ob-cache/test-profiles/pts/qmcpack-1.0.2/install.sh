#!/bin/sh

tar -xvf qmcpack-3.8.0.tar.gz
cd qmcpack-3.8.0/build/
cmake ..

# Run make twice as seems to hit errors on first build but completes fine on second time
make -j $NUM_CPU_CORES
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/

cat>qmcpack<<EOT
#!/bin/sh
cd qmcpack-3.8.0/build/examples/molecules/H2O/example_H2O-1-1
mpirun -np \$NUM_CPU_PHYSICAL_CORES ~/qmcpack-3.8.0/build/bin/qmcpack simple-H2O.xml > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x qmcpack


