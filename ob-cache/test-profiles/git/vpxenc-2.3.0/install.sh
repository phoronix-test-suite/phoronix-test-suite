#!/bin/sh

git clone https://chromium.googlesource.com/webm/libvpx libvpx-master

mkdir vpx
cd libvpx-master

./configure --disable-install-docs --enable-shared --prefix=$HOME/vpx
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf libvpx-master

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

echo "#!/bin/bash
cd vpx/bin
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
LD_PRELOAD=../lib/libvpx.so  ./vpxenc --codec=vp9 -v --threads=\$THREADCOUNT --tile-columns=6 -o /dev/null ~/Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv --width=1920 --height=1080 2> \$LOG_FILE 
echo \$? > ~/test-exit-status" > vpxenc
chmod +x vpxenc
