#!/bin/sh

tar -zxvf tiobench-0.3.3.tar.gz
cd tiobench-0.3.3/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd tiobench-0.3.3/
./tiotest \$@ > \$LOG_FILE 2>&1" > tiobench
chmod +x tiobench
