#!/bin/sh

unzip -o XSBench-20170808.zip
cd XSBench-master/src
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd XSBench-master/src
./XSBench -t \$NUM_CPU_CORES -s large -l 30000000 \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > xsbench
chmod +x xsbench
