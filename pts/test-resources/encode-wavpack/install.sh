#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/wavpack_

tar -xjf wavpack-4.41.0.tar.bz2
cd wavpack-4.41.0
./configure --prefix=$THIS_DIR/wavpack_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf wavpack-4.41.0/

echo "#!/bin/sh
./wavpack_/bin/wavpack -q -r -hhx2 -o - \$TEST_EXTENDS/pts-trondheim.wav >/dev/null" > wavpack_process
chmod +x wavpack_process

echo "#!/bin/sh
/usr/bin/time -f \"WAV To WavPack Encode Time: %e Seconds\" ./wavpack_process 2>&1" > encode-wavpack
chmod +x encode-wavpack
