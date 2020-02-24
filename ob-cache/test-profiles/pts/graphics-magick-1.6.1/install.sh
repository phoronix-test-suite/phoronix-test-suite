#!/bin/sh

tar -xjf libpng-1.2.49.tar.bz2
tar -xjf GraphicsMagick-1.3.19.tar.bz2

mkdir $HOME/gm_

cd libpng-1.2.49/
./configure --prefix=$HOME/gm_ > /dev/null
make -j $NUM_CPU_JOBS
make install
cd ..

cd GraphicsMagick-1.3.19/
LDFLAGS="-L$HOME/gm_/lib" CPPFLAGS="-I$HOME/gm_/include" ./configure --without-perl --prefix=$HOME/gm_ --with-png=yes > /dev/null
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf GraphicsMagick-1.3.19/
rm -rf libpng-1.2.49/
rm -rf gm_/share/doc/GraphicsMagick/
rm -rf gm_/share/man/

echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/bin/gm benchmark -duration 60 convert \$TEST_EXTENDS/DSC_6782.png \$@ null: > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > graphics-magick
chmod +x graphics-magick
