#!/bin/sh

tar -xf qe-6.7-ReleasePack.tgz
tar -xf AUSURF112-14Oct2019.tar.xz
cd qe-6.7
./configure
make pw -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/

# Not using mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES due to issues and already being threaded at least for single node systems
cat>qe<<EOT
#!/bin/sh
cd AUSURF112/
../qe-6.7/bin/pw.x -inp ausurf.in > \$LOG_FILE 2>&1
sed -i 's/h /h/g' \$LOG_FILE
sed -i 's/m /m/g' \$LOG_FILE
sed -i 's/mWALL/m WALL/g' \$LOG_FILE
# The sed is needed otherwise soemtimes result output is "20m 8.66s"
EOT
chmod +x qe

