#!/bin/sh

echo "#!/bin/sh
cd MPlayer-1.0rc3/
make -j \$NUM_CPU_JOBS 2>&1" > time-compile-mplayer

chmod +x time-compile-mplayer

