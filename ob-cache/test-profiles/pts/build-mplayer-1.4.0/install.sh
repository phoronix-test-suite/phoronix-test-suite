#!/bin/sh

echo "#!/bin/sh
cd MPlayer-1.4/
make -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > time-compile-mplayer

chmod +x time-compile-mplayer

