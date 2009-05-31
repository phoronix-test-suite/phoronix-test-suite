#!/bin/sh

tar -xvf dbench-4.0.tar.gz
cd dbench-4.0/
./autogen.sh
./configure
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

echo "#!/bin/sh
cd dbench-4.0/
./dbench \$@ -c client.txt > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ../dbench
chmod +x ../dbench
