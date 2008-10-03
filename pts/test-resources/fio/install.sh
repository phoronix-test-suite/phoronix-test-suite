#!/bin/sh

tar -xjf fio-1.21.tar.bz2
cd fio/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
cd fio/
\$TIMER_START
./fio \$@ 2>&1
\$TIMER_STOP
rm -f iometer.1.0" > fio-run
chmod +x fio-run
