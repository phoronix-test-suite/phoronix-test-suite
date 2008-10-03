#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/lzma_

tar -xzf lzma-4.32.6.tar.gz
cd lzma-4.32.6
./configure --prefix=$THIS_DIR/lzma_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf lzma-4.32.6

cat > compress-lzma <<EOT
#!/bin/sh
\$TIMER_START
./lzma_/bin/lzma -q -c ./compressfile > /dev/null 2>&1
\$TIMER_STOP
EOT
chmod +x compress-lzma
