#!/bin/sh

cd $1

if [ ! -f lame.tar.gz ]
  then
     wget http://superb-east.dl.sourceforge.net/sourceforge/lame/lame-3.97.tar.gz -O lame.tar.gz
fi

if [ ! -f ../pts-shared/pts-wav-sample-file.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
fi

tar -xvf lame.tar.gz
cd lame-3.97/
./configure
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh\n/usr/bin/time -f \"WAV To MP3 Encode Time: %e Seconds\" ./lame-3.97/frontend/lame --silent -h audio.wav audio.mp3 2>&1" > lame
chmod +x lame

tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2

