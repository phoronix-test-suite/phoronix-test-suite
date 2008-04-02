#!/bin/sh

cd $1

if [ ! -f mplayer.tar.bz2 ]
  then
     wget http://www3.mplayerhq.hu/MPlayer/releases/MPlayer-1.0rc2.tar.bz2 -O mplayer.tar.bz2
fi

echo "#!/bin/sh

if [ ! -f mplayer.tar.bz2 ]
  then
	echo \"MPlayer Not Installed... Build Failed.\"
	exit
fi

rm -rf MPlayer-1.0rc2/
tar -xjf mplayer.tar.bz2
cd MPlayer-1.0rc2/
./configure > /dev/null
sleep 3
/usr/bin/time -f \"MPlayer Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-mplayer

chmod +x time-compile-mplayer

