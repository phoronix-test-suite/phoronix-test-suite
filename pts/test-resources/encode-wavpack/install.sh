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
\$TIMER_START
./wavpack_/bin/wavpack -q -r -hhx2 -o - \$TEST_EXTENDS/pts-trondheim.wav > /dev/null 2>&1
\$TIMER_STOP" > encode-wavpack
chmod +x encode-wavpack
