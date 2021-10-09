#!/bin/sh

tar -xf libvpx-1.10.0.tar.gz

mkdir vpx
cd libvpx-1.10.0

./configure --disable-install-docs --disable-vp8 --enable-shared --prefix=$HOME/vpx
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
fi
make install
cd ~
rm -rf libvpx-1.10.0

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z -aoa
7z x Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z -aoa

echo "#!/bin/bash
cd vpx/bin
# libvpx has hung with more than 64 threads...
THREADCOUNT=\$((\$NUM_CPU_CORES>64?64:\$NUM_CPU_CORES))
LD_PRELOAD=../lib/libvpx.so  ./vpxenc \$@ -o /dev/null --passes=1  --row-mt=1 2> \$LOG_FILE
echo \$? > ~/test-exit-status" > vpxenc
chmod +x vpxenc
