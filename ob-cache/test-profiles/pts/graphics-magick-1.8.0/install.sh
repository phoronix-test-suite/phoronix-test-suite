#!/bin/sh

tar -xf libpng-1.6.34.tar.xz
tar -xjf GraphicsMagick-1.3.30.tar.bz2

mkdir $HOME/gm_

cd libpng-1.6.34
./configure --prefix=$HOME/gm_ > /dev/null
make -j $NUM_CPU_CORES
make install
cd ..

cd GraphicsMagick-1.3.30/
LDFLAGS="-L$HOME/gm_/lib" CPPFLAGS="-I$HOME/gm_/include" ./configure --without-perl --prefix=$HOME/gm_ --with-png=yes > /dev/null
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ..
rm -rf GraphicsMagick-1.3.30/
cd libpng-1.6.34
rm -rf gm_/share/doc/GraphicsMagick/
rm -rf gm_/share/man/
cd ~
echo "#!/bin/sh
OMP_NUM_THREADS=\$NUM_CPU_CORES ./gm_/bin/gm benchmark -duration 60 convert \$TEST_EXTENDS/DSC_6782.png \$@ null: > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > graphics-magick
chmod +x graphics-magick
