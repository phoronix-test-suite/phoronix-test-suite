#!/bin/sh

if [ ! -f ../pts-shared/pts-trondheim-2.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav-2.tar.gz -C ../pts-shared/
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
/usr/bin/time -f \"WAV To FLAC Encode Time: %e Seconds\" ./flac_/bin/flac -s --best --totally-silent ../pts-shared/pts-trondheim.wav -f -o /dev/null 2>&1" > flac
chmod +x flac
