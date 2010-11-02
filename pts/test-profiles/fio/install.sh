#!/bin/sh

tar -xjf fio-1.21.tar.bz2
cd fio/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd fio/
./fio \$@ 2>&1" > fio-run
chmod +x fio-run
