#!/bin/sh

tar -jxvf dcraw-test-1.tar.bz2
CFLAGS += -O3
cc -o dcraw-bin $CFLAGS dcraw.c -lm -DNO_JPEG -DNO_LCMS
echo $? > ~/install-exit-status

echo "#!/bin/sh
./dcraw-bin -q 3 -4 -f -a *.NEF 2>&1
echo \$? > ~/test-exit-status" > dcraw
chmod +x dcraw
