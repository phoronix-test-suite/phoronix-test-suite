#!/bin/sh

tar -jxvf fract-1.07b.tar.bz2
cp fract-scene.h.patch fract-1.07b/
cd fract-1.07b/
patch -p0 < fract-scene.h.patch
./configure
make
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd fract-1.07b/
./src/fract \$@
echo \$? > ~/test-exit-status" > fract
chmod +x fract

