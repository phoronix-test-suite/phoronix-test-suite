#!/bin/sh

tar -xjf GraphicsMagick-1.3.3.tar.bz2

mkdir $HOME/gm_

cd GraphicsMagick-1.3.3/
./configure --without-perl --prefix=$HOME/gm_ > /dev/null
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf GraphicsMagick-1.3.3/

echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/bin/gm benchmark -duration 60 convert \$TEST_EXTENDS/DSC_4185.JPG \$@ null: > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > graphics-magick
chmod +x graphics-magick
