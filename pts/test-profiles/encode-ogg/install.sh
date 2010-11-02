#!/bin/sh

mkdir $HOME/vorbis

tar -zxvf libogg-1.1.3.tar.gz
tar -zxvf libvorbis-1.2.0.tar.gz
tar -zxvf vorbis-tools-1.2.0.tar.gz

cd libogg-1.1.3/
./configure --prefix=$HOME/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libogg-1.1.3/

cd libvorbis-1.2.0/
./configure --prefix=$HOME/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libvorbis-1.2.0/

cd vorbis-tools-1.2.0/
./configure --prefix=$HOME/vorbis
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf vorbis-tools-1.2.0/

echo "#!/bin/sh
./vorbis/bin/oggenc \$TEST_EXTENDS/pts-trondheim.wav -q 10 -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oggenc
chmod +x oggenc
