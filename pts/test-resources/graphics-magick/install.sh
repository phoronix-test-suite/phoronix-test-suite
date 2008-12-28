#!/bin/sh

tar -xjf GraphicsMagick-1.3.3.tar.bz2

THIS_DIR=$(pwd)
mkdir $THIS_DIR/gm_

cd GraphicsMagick-1.3.3/
./configure --prefix=$THIS_DIR/gm_ > /dev/null
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf GraphicsMagick-1.3.3/

echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/bin/gm benchmark -duration 60 convert \$TEST_EXTENDS/DSC_4185.JPG \$@ null: > \$LOG_FILE 2>&1" > graphics-magick
chmod +x graphics-magick
