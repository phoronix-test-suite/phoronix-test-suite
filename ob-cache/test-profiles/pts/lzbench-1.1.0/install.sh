#!/bin/sh

gunzip -k linux-5.5.tar.gz
tar -xf lzbench-1.8.tar.gz

cd lzbench-1.8/
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd lzbench-1.8/
./lzbench -t10,10 -v \$@ ../linux-5.5.tar > \$LOG_FILE
echo \$? > ~/test-exit-status" > lzbench
chmod +x lzbench
