#!/bin/sh
tar -xf png-samples-1.tar.xz
tar -xf astc-encoder-4.7.0.tar.gz
cd astc-encoder-4.7.0
mkdir build
cd build
ASTCISA="-DASTCENC_ISA_AVX2=on"
ASTCBIN="avx2"
if [ $OS_ARCH = "aarch64" ]
then
	ASTCISA="-DASTCENC_ISA_NEON=on"
	ASTCBIN="neon"
fi
cmake -DCMAKE_BUILD_TYPE=Release -DASTCENC_ISA_NATIVE=ON $ASTCISA -DCMAKE_CXX_FLAGS="-O3 -DNDEBUG" ..
make -j $NUM_CPU_CORES
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ];
then
	# GCC 14 fix
	cmake -DCMAKE_BUILD_TYPE=Release -DASTCENC_ISA_NATIVE=ON $ASTCISA -DCMAKE_CXX_FLAGS="-O3 -DNDEBUG -Wno-error=calloc-transposed-args" ..
	make -j $NUM_CPU_CORES
	EXIT_STATUS=$?
fi
echo $EXIT_STATUS > ~/install-exit-status
cd ~
echo "#!/bin/sh
./astc-encoder-4.7.0/build/Source/astcenc-$ASTCBIN \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
