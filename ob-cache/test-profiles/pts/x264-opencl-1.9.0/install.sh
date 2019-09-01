#!/bin/sh

tar -xjf x264-snapshot-20140830-2245.tar.bz2
mkdir x264_/

cd x264-snapshot-20140830-2245/
./configure --prefix=$HOME/x264_/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ~
rm -rf x264-snapshot-20140830-2245/

echo "#!/bin/sh
./x264_/bin/x264 -o /dev/null --threads \$NUM_CPU_CORES --opencl soccer_4cif.y4m > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x264-opencl
chmod +x x264-opencl
