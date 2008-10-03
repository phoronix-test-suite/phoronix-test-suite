#!/bin/sh

echo "#!/bin/sh

if [ ! -f MPlayer-1.0rc2.tar.bz2 ]
  then
	echo \"MPlayer Not Installed... Build Failed.\"
	exit
fi

rm -rf MPlayer-1.0rc2/
tar -xjf MPlayer-1.0rc2.tar.bz2
cd MPlayer-1.0rc2/
./configure > /dev/null
sleep 3
\$TIMER_START
make -j \$NUM_CPU_JOBS 2>&1
\$TIMER_STOP" > time-compile-mplayer

chmod +x time-compile-mplayer

