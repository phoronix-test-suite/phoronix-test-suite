#!/bin/sh

cd $1

if [ ! -f libogg.tar.gz ]
  then
     wget http://downloads.xiph.org/releases/ogg/libogg-1.1.3.tar.gz -O libogg.tar.gz
fi

if [ ! -f libvorbis.tar.gz ]
  then
     wget http://downloads.xiph.org/releases/vorbis/libvorbis-1.2.0.tar.gz -O libvorbis.tar.gz
fi

if [ ! -f vorbis-tools.tar.gz ]
  then
     wget http://downloads.xiph.org/releases/vorbis/vorbis-tools-1.2.0.tar.gz -O vorbis-tools.tar.gz
fi

if [ ! -f ../pts-shared/pts-wav-sample-file.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/vorbis

tar -xvf libogg.tar.gz
tar -xvf libvorbis.tar.gz
tar -xvf vorbis-tools.tar.gz

cd libogg-1.1.3/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..

cd libvorbis-1.2.0/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..

cd vorbis-tools-1.2.0/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..

echo "#!/bin/sh\n/usr/bin/time -f \"WAV To OGG Encode Time: %e Seconds\" ./vorbis/bin/oggenc --quiet audio.wav -q 10 -o audio.ogg 2>&1" > oggenc
chmod +x oggenc

tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2
