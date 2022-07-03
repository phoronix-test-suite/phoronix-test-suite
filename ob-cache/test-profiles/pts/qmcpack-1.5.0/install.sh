#!/bin/sh

tar -xf qmcpack-3.13.0.tar.gz
cd qmcpack-3.13.0/build/

ADD_TO_CMAKE=""
if [ -x /usr/bin/python3 ]
then
	ADD_TO_CMAKE="$ADD_TO_CMAKE -DPython3_EXECUTABLE=/usr/bin/python3"
elif [ -x /usr/bin/python ]
then
	ADD_TO_CMAKE="$ADD_TO_CMAKE -DPython3_EXECUTABLE=/usr/bin/python"
fi

cmake .. -DQMC_OMP=0 -DCMAKE_BUILD_TYPE=Release $ADD_TO_CMAKE

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
cd qmcpack-3.13.0/build/examples/molecules/\$1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ~/qmcpack-3.13.0/build/bin/qmcpack \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x qmcpack


