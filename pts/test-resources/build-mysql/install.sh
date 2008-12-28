#!/bin/sh

echo "#!/bin/sh

rm -rf mysql-5.1.30/
tar -xvf mysql-5.1.30.tar.gz
cd mysql-5.1.30/
./configure > /dev/null 2>&1
\$TIMER_START
make -s -j \$NUM_CPU_JOBS 2>&1
\$TIMER_STOP" > build-mysql

chmod +x build-mysql
