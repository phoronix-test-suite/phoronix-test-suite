#!/bin/sh

cd $1

if [ ! -f flac.tar.gz ]
  then
     wget http://internap.dl.sourceforge.net/sourceforge/flac/flac-1.2.1.tar.gz -O flac.tar.gz
fi

if [ ! -f ../pts-shared/pts-wav-sample-file.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
fi

tar -xvf flac.tar.gz
cd flac-1.2.1/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh\n/usr/bin/time -f \"WAV To FLAC Encode Time: %e Seconds\" ./flac-1.2.1/src/flac/flac -s --best audio.wav 2>&1
rm -f audio.flac" > flac
chmod +x flac

tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2

