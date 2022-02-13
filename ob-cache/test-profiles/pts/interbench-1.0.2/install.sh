#!/bin/sh

tar -jxvf interbench-0.31.tar.bz2
cd interbench-0.31/
make clean
cc -o interbench -O3 $CFLAGS interbench.c hackbench.c -lrt -lm -pthread
cd ~/

echo "#!/bin/sh
cd interbench-0.31/
rm -rf t/
mkdir t/
./interbench -L \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > interbench
chmod +x interbench
