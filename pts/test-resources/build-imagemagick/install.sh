#!/bin/sh

echo "#!/bin/sh
rm -rf ImageMagick-6.5.0-6/
tar -xjf ImageMagick-6.5.0-6.tar.bz2
cd ImageMagick-6.5.0-6/
./configure > /dev/null
sleep 3
\$TIMER_START
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status
\$TIMER_STOP" > time-compile-imagemagick

chmod +x time-compile-imagemagick
