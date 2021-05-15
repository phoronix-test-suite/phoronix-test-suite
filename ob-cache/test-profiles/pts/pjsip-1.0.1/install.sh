#!/bin/sh

tar -xf pjproject-2.11.tar.gz
cd pjproject-2.11
./configure
make dep -j $NUM_CPU_CORES
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/bash
cd pjproject-2.11
# pjsip has problems with more than 16 thread counts for now
THREADCOUNT=\$((\$NUM_CPU_CORES>16?16:\$NUM_CPU_CORES))
./pjsip-apps/bin/samples/*/pjsip-perf --thread-count=\$THREADCOUNT --window=10000 --count=2000000 --real-sdp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

sed -i 's/=/ /g' \$LOG_FILE" > pjsip
chmod +x pjsip
