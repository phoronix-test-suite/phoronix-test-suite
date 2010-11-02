#!/bin/sh

mkdir $HOME/wavpack_

tar -xjf wavpack-4.41.0.tar.bz2
cd wavpack-4.41.0
./configure --prefix=$HOME/wavpack_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf wavpack-4.41.0/

echo "#!/bin/sh
./wavpack_/bin/wavpack -q -r -hhx2 -o - \$TEST_EXTENDS/pts-trondheim.wav > /dev/null 2>&1
echo \$? > ~/test-exit-status" > encode-wavpack
chmod +x encode-wavpack
