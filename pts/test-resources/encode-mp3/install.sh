#!/bin/sh

if [ ! -f ../pts-shared/pts-trondheim-3.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav-3.tar.gz -C ../pts-shared/
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/lame_

tar -xvf lame-398.tar.gz
cd lame-398/
./configure --prefix=$THIS_DIR/lame_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf lame-398/

echo "#!/bin/sh
/usr/bin/time -f \"WAV To MP3 Encode Time: %e Seconds\" ./lame_/bin/lame --silent -h ../pts-shared/pts-trondheim-3.wav /dev/null 2>&1" > lame
chmod +x lame
