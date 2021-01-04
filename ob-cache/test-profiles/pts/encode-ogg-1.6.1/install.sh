#!/bin/sh

mkdir $HOME/vorbis

# Force use of new libs.
CPPFLAGS="-I$HOME/vorbis/include $CPPFLAGS" 
LDFLAGS="-L$HOME/vorbis/lib $LDFLAGS"

tar -xf libogg-1.3.4.tar.gz
tar -xf libvorbis-1.3.7.tar.gz
tar -xf vorbis-tools-1.4.0.tar.gz

cd libogg-1.3.4/
./configure --prefix=$HOME/vorbis
make -j $NUM_CPU_CORES
make install
cd ~

export PKG_CONFIG_PATH=$HOME/vorbis/lib/pkgconfig:$PKG_CONFIG_PATH

cd libvorbis-1.3.7/
./configure --prefix=$HOME/vorbis --with-ogg-includes=$HOME/vorbis/include/ogg --with-ogg-libraries=$HOME/vorbis/lib
make -j $NUM_CPU_CORES
make install
cd ~
#rm -rf libvorbis-1.3.7/

cd vorbis-tools-1.4.0/
./configure --prefix=$HOME/vorbis --with-ogg-includes=$HOME/vorbis/include/ogg --with-ogg-libraries=$HOME/vorbis/lib --with-vorbis-includes=$HOME/vorbis/include/vorbis --with-vorbis-libraries=$HOME/vorbis/lib
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
#rm -rf vorbis-tools-1.4.0/

echo "#!/bin/sh
./vorbis/bin/oggenc 2L38_01_192kHz.flac -q 10 -o /dev/null > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oggenc
chmod +x oggenc
