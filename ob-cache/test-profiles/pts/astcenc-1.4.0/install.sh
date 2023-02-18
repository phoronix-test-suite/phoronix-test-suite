#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf astc-encoder-4.0.0.tar.gz
cd astc-encoder-4.0.0

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
./astc-encoder-4.0.0/build/Source/astcenc-$ASTCBIN \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
