#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf astc-encoder-2.0.tar.gz
cd astc-encoder-2.0/Source
make -j $NUM_CPU_CORES
echo \$? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./astc-encoder-2.0/Source/astcenc-avx2 -tl sample-4.png 1.png 8x6 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > astcenc
chmod +x astcenc
