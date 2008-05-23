#!/bin/sh

cd $1

if [ ! -f ../pts-shared/pts-trondheim.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav.tar.gz -C ../pts-shared/
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/lame_

tar -xvf lame-3.97.tar.gz
cd lame-3.97/
./configure --prefix=$THIS_DIR/lame_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf lame-3.97/

echo "#!/bin/sh
/usr/bin/time -f \"WAV To MP3 Encode Time: %e Seconds\" ./lame_/bin/lame --silent -h ../pts-shared/pts-trondheim.wav /dev/null 2>&1" > lame
chmod +x lame
