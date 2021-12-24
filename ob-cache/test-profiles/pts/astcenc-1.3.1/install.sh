#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf astc-encoder-3.2.tar.gz
cd astc-encoder-3.2/

mkdir build
cd build

ASTCISA="-DISA_AVX2=on"
ASTCBIN="avx2"
if [ $OS_ARCH = "aarch64" ]
then
	ASTCISA="-DISA_NEON=on"
	ASTCBIN="neon"
fi

cmake -DCMAKE_BUILD_TYPE=Release $ASTCISA ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./astc-encoder-3.2/build/Source/astcenc-$ASTCBIN -tl sample-4.png 1.png 8x6 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
