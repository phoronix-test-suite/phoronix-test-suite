#!/bin/sh

tar -xjf mplayer-2009-11-28.tar.bz2

mkdir $HOME/mplayer_

cd mplayer-2009-11-28/

if [ -f "/usr/include/vdpau/vdpau.h" ]
then
	VDPAU_STATUS="--enable-vdpau"
else
	VDPAU_STATUS=""
fi

./configure --enable-xv --enable-xvmc $VDPAU_STATUS --disable-ivtv --prefix=$HOME/mplayer_ > /dev/null

case $OS_TYPE in
	"Solaris")
	"BSD")
		gmake -j $NUM_CPU_JOBS
		gmake install
	;;
	*)
		make -j $NUM_CPU_JOBS
		make install
	;;
esac

cd ..

rm -rf mplayer-2009-11-28/
rm -rf mplayer_/share/

ln -s mplayer_/bin/mplayer mplayer
ln -s mplayer_/bin/mencoder mencoder
