#!/bin/sh

if [ ! -f ../pts-shared/pts-trondheim-3.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav-3.tar.gz -C ../pts-shared/
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/wavpack_

tar -xjf wavpack-4.41.0.tar.bz2
cd wavpack-4.41.0
./configure --prefix=$THIS_DIR/wavpack_ --enable-mmx
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf wavpack-4.41.0/

echo "#!/bin/sh
./wavpack_/bin/wavpack -q -r -hhx2 -o - ../pts-shared/pts-trondheim-3.wav >/dev/null" > wavpack_process
chmod +x wavpack_process

echo "#!/bin/sh
/usr/bin/time -f \"WAV To WavPack Encode Time: %e Seconds\" ./wavpack_process 2>&1" > encode-wavpack
chmod +x encode-wavpack
