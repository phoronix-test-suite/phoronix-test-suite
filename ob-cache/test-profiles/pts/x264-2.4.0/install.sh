#!/bin/sh

mkdir x264_/

tar -xjf nasm-2.13.01.tar.bz2
cd nasm-2.13.01
./autogen.sh
./configure --prefix=$HOME/x264_/

if [ $OS_TYPE = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	gmake install
	BASH_BIN=/usr/local/bin/bash
else
	make -j $NUM_CPU_CORES
	make install
	BASH_BIN=/bin/bash
fi

cd ~

tar -xjf x264-snapshot-20180728-2245.tar.bz2
cd x264-snapshot-20180728-2245
PATH="$HOME/x264_/bin:$PATH" $BASH_BIN configure --prefix=$HOME/x264_/ --disable-opencl  --enable-pic --enable-shared
if [ $OS_TYPE = "BSD" ]
then
	PATH="$HOME/x264_/bin:$PATH" gmake -j $NUM_CPU_CORES
	gmake install
else
	PATH="$HOME/x264_/bin:$PATH" make -j $NUM_CPU_CORES
	make install
fi
echo $? > ~/install-exit-status
cd ~
# rm -rf x264-snapshot-20180728-2245

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

echo "#!/bin/sh
./x264_/bin/x264 -o /dev/null --slow --threads \$NUM_CPU_CORES Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > x264
chmod +x x264
