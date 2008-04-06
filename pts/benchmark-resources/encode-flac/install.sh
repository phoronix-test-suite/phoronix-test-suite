#!/bin/sh

cd $1

if [ ! -f flac.tar.gz ]
  then
     wget http://internap.dl.sourceforge.net/sourceforge/flac/flac-1.2.1.tar.gz -O flac.tar.gz
fi

if [ ! -f ../pts-shared/pts-wav-sample-file.wav ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
     tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2 -C ../pts-shared/
     rm -f ../pts-shared/pts-wav-sample-file.tar.bz2
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/flac_

tar -xvf flac.tar.gz
cd flac-1.2.1/
./configure --prefix=$THIS_DIR/flac_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf flac-1.2.1/

echo "#!/bin/sh
/usr/bin/time -f \"WAV To FLAC Encode Time: %e Seconds\" ./flac_/bin/flac -s --best ../pts-shared/pts-wav-sample-file.wav 2>&1
rm -f ../pts-shared/pts-wav-sample-file.flac" > flac
chmod +x flac
