#!/bin/sh

tar -xjf fio-1.21.tar.bz2
cd fio/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cd fio/
/usr/bin/time -f \"Total fio Run-Time: %e Seconds\" ./fio \$@ 2>&1" > fio-run
chmod +x fio-run
