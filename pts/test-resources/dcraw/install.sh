#!/bin/sh

tar -jxvf dcraw-test-1.tar.bz2
cc -o dcraw-bin -O4 dcraw.c -lm -DNO_JPEG -DNO_LCMS

echo "#!/bin/sh
rm -f *.ppm
\$TIMER_START
./dcraw-bin -q 3 -4 -f -a *.NEF 2>&1
\$TIMER_STOP
rm -f *.ppm" > dcraw
chmod +x dcraw
