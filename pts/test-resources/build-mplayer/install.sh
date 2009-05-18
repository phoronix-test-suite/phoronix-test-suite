#!/bin/sh

echo "#!/bin/sh

rm -rf MPlayer-1.0rc2/
tar -xjf MPlayer-1.0rc2.tar.bz2
cd MPlayer-1.0rc2/
./configure --disable-ivtv > /dev/null
sleep 3
\$TIMER_START
make -j \$NUM_CPU_JOBS 2>&1
\$TIMER_STOP" > time-compile-mplayer

chmod +x time-compile-mplayer

