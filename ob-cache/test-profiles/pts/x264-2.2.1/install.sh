#!/bin/sh

mkdir x264_/

tar -xjf nasm-2.13.01.tar.bz2
cd nasm-2.13.01
./autogen.sh
./configure --prefix=$HOME/x264_/
make -j $NUM_CPU_JOBS
make install
cd ~

tar -xjf x264-snapshot-20170908-2245.tar.bz2
cd x264-snapshot-20170908-2245
PATH="$HOME/x264_/bin:$PATH" ./configure --prefix=$HOME/x264_/ --disable-opencl  --enable-pic --enable-shared
PATH="$HOME/x264_/bin:$PATH" make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ~
# rm -rf x264-snapshot-20170908-2245

echo "#!/bin/sh
./x264_/bin/x264 -o /dev/null --threads \$NUM_CPU_CORES soccer_4cif.y4m > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x264
chmod +x x264
