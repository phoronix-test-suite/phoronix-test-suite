#!/bin/sh

cd $1

if [ ! -f fract-1.07b.tar.bz2 ]
  then
     wget http://www.fbench.com/fract-1.07b.tar.bz2 -O fract-1.07b.tar.bz2
fi
if [ ! -f fract-scene.h.patch ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/fract-scene.h.patch -O fract-scene.h.patch
fi

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

