#!/bin/sh

mkdir $HOME/lzma_

tar -zxvf lzma-4.32.6.tar.gz
cd lzma-4.32.6
./configure --prefix=$HOME/lzma_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
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
