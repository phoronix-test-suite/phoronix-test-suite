#!/bin/sh

tar -xf LibRaw-0.20.0.tar.gz
cd LibRaw-0.20.0
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
tar -xf darktable-bench-assets-1.tar.bz2

echo "#!/bin/sh
./LibRaw-0.20.0/bin/postprocessing_benchmark -R 50 server_room.NEF > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > libraw
chmod +x libraw
