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

cat > lzma_process <<EOT
#!/bin/sh
./lzma_/bin/lzma -q -c ./compressfile > /dev/null
EOT
chmod +x lzma_process


cat > compress-lzma <<EOT
#!/bin/sh
/usr/bin/time -f "LZMA Compress Time: %e Seconds" ./lzma_process 2>&1
EOT
chmod +x compress-lzma
