#!/bin/sh

rm -rf q-e-qe-7.0/

tar -xf qe-7.0.tar.gz
tar -xf AUSURF112-14Oct2019.tar.xz
cd q-e-qe-7.0/
./configure --enable-openmp
make pw -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/

cat>qe<<EOT
#!/bin/sh
cd AUSURF112/
OMP_NUM_THREADS=\$CPU_THREADS_PER_CORE mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ../q-e-qe-7.0/bin/pw.x -inp ausurf.in > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
sed -i 's/h /h/g' \$LOG_FILE
sed -i 's/m /m/g' \$LOG_FILE
sed -i 's/mWALL/m WALL/g' \$LOG_FILE
# The sed is needed otherwise soemtimes result output is "20m 8.66s"
EOT
chmod +x qe
