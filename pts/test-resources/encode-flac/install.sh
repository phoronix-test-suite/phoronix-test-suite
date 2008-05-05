#!/bin/sh

cd $1

if [ ! -f trondheim-wav-sample.wav ]
  then
     tar -xvf ../pts-shared/trondheim-wav-sample.tar.gz
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/flac_

tar -xvf flac-1.2.1.tar.gz
cd flac-1.2.1/
./configure --prefix=$THIS_DIR/flac_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf flac-1.2.1/

echo "#!/bin/sh
/usr/bin/time -f \"WAV To FLAC Encode Time: %e Seconds\" ./flac_/bin/flac -s --best trondheim-wav-sample.wav 2>&1
rm -f trondheim-wav-sample.flac" > flac
chmod +x flac
