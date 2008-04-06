#!/bin/sh

cd $1

if [ ! -f lame.tar.gz ]
  then
     wget http://superb-east.dl.sourceforge.net/sourceforge/lame/lame-3.97.tar.gz -O lame.tar.gz
fi

if [ ! -f ../pts-shared/pts-wav-sample-file.wav ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
     tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2 -C ../pts-shared/
     rm -f ../pts-shared/pts-wav-sample-file.tar.bz2
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/lame

tar -xvf lame.tar.gz
cd lame-3.97/
./configure --prefix=$THIS_DIR/lame
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf lame-3.97/

echo "#!/bin/sh
rm -f audio.mp3
/usr/bin/time -f \"WAV To MP3 Encode Time: %e Seconds\" ./lame/bin/lame --silent -h ../pts-shared/pts-wav-sample-file.wav audio.mp3 2>&1" > lame
chmod +x lame

