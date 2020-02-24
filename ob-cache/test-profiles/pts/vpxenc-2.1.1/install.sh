#!/bin/sh

tar -xzf libvpx-1.7.0.tar.gz

mkdir vpx
cd libvpx-1.7.0

./configure --disable-install-docs --enable-shared --prefix=$HOME/vpx
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf libvpx-1.7.0

7z e Jockey_1920x1080_120fps_420_8bit_YUV_Y4M.7z

echo "#!/bin/bash
cd vpx/bin
THREADCOUNT=$(($NUM_CPU_CORES>64?64:$NUM_CPU_CORES))
LD_PRELOAD=../lib/libvpx.so.5  ./vpxenc --good --codec=vp9 -v --threads=\$THREADCOUNT --tile-columns=4 -o /dev/null ~/Jockey_1920x1080_120fps_420_8bit_YUV.y4m --width=1920 --height=1080 2> \$LOG_FILE 
echo \$? > ~/test-exit-status" > vpxenc
chmod +x vpxenc
