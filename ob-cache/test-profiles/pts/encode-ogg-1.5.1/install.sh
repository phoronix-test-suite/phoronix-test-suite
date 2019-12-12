#!/bin/sh

mkdir $HOME/vorbis

# Force use of new libs.
CPPFLAGS="-I$HOME/vorbis/include" 
LDFLAGS="-L$HOME/vorbis/lib"

tar -zxvf libogg-1.3.3.tar.gz
tar -zxvf libvorbis-1.3.5.tar.gz
tar -zxvf vorbis-tools-1.4.0.tar.gz

cd libogg-1.3.3/
./configure --prefix=$HOME/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
#rm -rf libogg-1.3.3/

cd libvorbis-1.3.5/
./configure --prefix=$HOME/vorbis --with-ogg-includes=$HOME/vorbis/include/ogg --with-ogg-libraries=$HOME/vorbis/lib
make -j $NUM_CPU_JOBS
make install
cd ..
#rm -rf libvorbis-1.3.5/

cd vorbis-tools-1.4.0/
./configure --prefix=$HOME/vorbis --with-ogg-includes=$HOME/vorbis/include/ogg --with-ogg-libraries=$HOME/vorbis/lib --with-vorbis-includes=$HOME/vorbis/include/vorbis --with-vorbis-libraries=$HOME/vorbis/lib
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
#rm -rf vorbis-tools-1.4.0/

echo "#!/bin/sh
./vorbis/bin/oggenc \$TEST_EXTENDS/pts-trondheim.wav -q 10 -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oggenc
chmod +x oggenc
