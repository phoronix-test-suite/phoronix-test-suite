#!/bin/sh

cd $1

tar -jxvf fract-1.07b.tar.bz2
cp fract-scene.h.patch fract-1.07b/
cd fract-1.07b/
patch -p0 < fract-scene.h.patch
./configure
make
cd ..

echo "#!/bin/sh
cd fract-1.07b/
./src/fract \$@" > fract
chmod +x fract

