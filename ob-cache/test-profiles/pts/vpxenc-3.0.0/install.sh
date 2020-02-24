#!/bin/sh

tar -xzf libvpx-1.8.2.tar.gz

mkdir vpx
cd libvpx-1.8.2

./configure --disable-install-docs --disable-vp8 --enable-shared --prefix=$HOME/vpx
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf libvpx-1.8.2

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z

echo "#!/bin/bash
cd vpx/bin
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
LD_PRELOAD=../lib/libvpx.so  ./vpxenc \$@ -o /dev/null ~/Bosphorus_1920x1080_120fps_420_8bit_YUV.yuv --passes=1 --end-usage=cq --cq-level=30 --width=1920 --height=1080 2> \$LOG_FILE
echo \$? > ~/test-exit-status" > vpxenc
chmod +x vpxenc
